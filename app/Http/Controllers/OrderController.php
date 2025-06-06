<?php

namespace App\Http\Controllers;

// App Models
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Facility;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Inventory;
use App\Models\InventoryAllocation;
use App\Models\FacilityBackorder;
use App\Models\FacilityInventory;
// App Events and Resources
use App\Events\OrderEvent;
use App\Http\Resources\OrderResource;

// Laravel Core
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

// App Facades
use App\Facades\Kafka;

class OrderController extends Controller
{
    /**
     * Reject an entire order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $request->validate([
                'order_id' => 'required|exists:orders,id',
            ]);
            
            $order = Order::findOrFail($request->order_id);
            
            // Update order status to rejected
            $order->status = 'rejected';
            $order->rejected_by = auth()->id();
            $order->rejected_at = now();
            $order->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order has been rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Order::with(['facility', 'user'])
            ->where('facility_id', auth()->user()->facility_id)
            ->when($request->dateFrom && $request->dateTo, function ($query) use ($request) {
                $query->whereBetween('order_date', [$request->dateFrom, $request->dateTo]);
            })
            ->when($request->orderType, function ($query, $orderType) {
                $query->where('order_type', $orderType);
            })
            ->when($request->currentStatus, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest();

        // Get order statistics from orders table
        $stats = DB::table('orders')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            })
            ->toArray();

        // Ensure all statuses have a value
        $defaultStats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'in_process' => 0,
            'dispatched' => 0,
            'delivery_pending' => 0,
            'delivered' => 0,
            'received' => 0
        ];

        $stats = array_merge($defaultStats, $stats);

        $orders = $query->paginate(500);

        return Inertia::render('Order/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page', 'orderType', 'dateFrom', 'dateTo', 'currentStatus'),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'order_type' => 'required',
                'order_date' => 'required|date',
                'expected_date' => 'required|date|after_or_equal:order_date',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
            ],[
                'items.*.product_id.required' => 'Product is required',
                'items.*.quantity.min' => 'Required quantity should be at least 1',
            ]);
            return DB::transaction(function () use ($request) {
                // Generate order number
                $orderNumber = $this->generateOrderNumber();

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'facility_id' => auth()->user()->facility_id,
                    'user_id' => auth()->user()->id,
                    'order_type' => $request->order_type,
                    'order_date' => $request->order_date,
                    'expected_date' => $request->expected_date,
                    'note' => $request->notes,
                    'status' => 'pending',
                ]);

                foreach ($request->items as $item) {
                    if($item['product_id'] == null){
                        continue;
                    }
                    // Create order item
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'quantity_on_order' => $item['quantity_on_order'],
                        'quantity_to_release' => (int) $item['quantity'] - (int) $item['quantity_on_order'],
                        'no_of_days' => $item['no_of_days'],
                        'amc' => $item['amc'],
                        'no_of_days' => $item['no_of_days'],
                        'status' => 'pending',
                    ]);

                    // Calculate the quantity to deduct from inventory (quantity - quantity_on_order)
                    $quantityToDeduct = (float) $item['quantity'] - (float) ($item['quantity_on_order'] ?? 0);
                    
                    // Get available inventory for this product
                    $availableInventory = Inventory::where('product_id', $item['product_id'])
                        ->where('quantity', '>', 0)
                        ->where('expiry_date', '>=', Carbon::now()->addMonths(3)->toDateString()) // Only use inventory that doesn't expire in next 3 months
                        ->orderBy('created_at', 'asc') // FIFO principle
                        ->get();

                    $remainingQuantity = $quantityToDeduct; // Only deduct the difference
                    $allocations = [];

                    // Allocate inventory from different batches
                    foreach ($availableInventory as $inventory) {
                        if ($remainingQuantity <= 0) break;

                        $quantityToAllocate = min($remainingQuantity, $inventory->quantity);
                        
                        // Create inventory allocation
                        InventoryAllocation::create([
                            'order_item_id' => $orderItem->id,
                            'product_id' => $item['product_id'],
                            'inventory_id' => $inventory->id,
                            'allocated_quantity' => $quantityToAllocate,
                            'batch_number' => $inventory->batch_number,
                            'barcode' => $inventory->barcode,
                            'warehouse_id' => $inventory->warehouse_id,
                            'location_id' => $inventory->location_id,
                            'expiry_date' => $inventory->expiry_date,
                        ]);

                        // Update inventory quantity
                        $inventory->quantity -= $quantityToAllocate;
                        $inventory->save();

                        $remainingQuantity -= $quantityToAllocate;
                    }

                    // Check if we couldn't allocate all requested quantity
                    if ($remainingQuantity > 0) {
                        return response()->json("Insufficient inventory for product ID {$item['product_id']}", 500);
                    }
                }

                return response()->json("Order created successfully", 200);
            });
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function destroy(Order $order)
    {
        try {
            if ($order->status !== 'pending') {
                return back()->with('error', 'Only pending orders can be deleted.');
            }

            $order->items()->delete();
            $order->delete();

            return back()->with('success', 'Order deleted successfully.');
        } catch (\Throwable $th) {
            return back()->with($th->getMessage(), 500);
        }
    }

    public function bulk(Request $request)
    {
        try {
            $orderIds = $request->input('orderIds');

            // Validate that at least one order is selected
            if (empty($orderIds)) {
                return response()->json('Please select at least one order', 400);
            }

            // Get all selected orders
            $orders = Order::whereIn('id', $orderIds)->get();

            // Check if any order has non-pending items and collect their IDs
            $nonPendingOrders = [];
            foreach ($orders as $order) {
                if ($order->status !== 'pending') {
                    $nonPendingOrders[] = $order->id;
                }
            }

            if (!empty($nonPendingOrders)) {
                return response()->json('Cannot delete orders that are not in pending status', 500);
            }

            // Delete orders if all are pending
            $orders->each(function ($order) {
                $order->items()->delete();
                $order->delete();
            });

            return response()->json('Selected orders deleted successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function searchProduct(Request $request)
    {
        try {
            $search = $request->input('search');
            $products = Product::where('name', 'like', '%' . $search . '%')
                ->orWhere('barcode', 'like', '%' . $search . '%')
                ->select('id', 'name', 'barcode')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                    ];
                });

            return response()->json($products, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            DB::beginTransaction();
            // Validate request
            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'status' => ['required', Rule::in(['delivered'])]
            ]);

            $order = Order::findOrFail($request->order_id);

            // Define allowed transitions
            $allowedTransitions = [
                'pending' => ['approved'],
                'approved' => ['in_process'],
                'in_process' => ['dispatched'],
                'dispatched' => ['delivered'],
                'rejected' => ['approved'] // Allow rejected orders to be approved
            ];

            // Check if the transition is allowed
            if (
                !isset($allowedTransitions[$order->status]) ||
                !in_array($request->status, $allowedTransitions[$order->status])
            ) {
                return response()->json("Status transition not allowed", 422);
            }

            $userId = auth()->id();
            $now = now();

            // Prepare updates for order
            $updates = ['status' => $request->status];

            // Add timestamp based on the status
            switch ($request->status) {
                case 'approved':
                    $updates['approved_at'] = $now;
                    $updates['approved_by'] = $userId;
                    
                    // If transitioning from rejected to approved, clear rejection data
                    if ($order->status === 'rejected') {
                        $updates['rejected_by'] = null;
                        $updates['rejected_at'] = null;
                    }
                    break;
                case 'in_process':
                    $updates['in_process'] = true;
                    $updates['in_process_at'] = $now;
                    $updates['in_process_by'] = $userId;
                    break;
                case 'dispatched':
                    $updates['dispatched_by'] = $userId;
                    $updates['dispatched_at'] = $now;
                    break;
                case 'delivered':
                    $updates['delivered'] = true;
                    $updates['delivered_at'] = $now;
                    $updates['delivered_by'] = $userId;
                    break;
            }

            // Update the order
            $order->update($updates);

            // Trigger Kafka event for order status change
            Kafka::publishOrderPlaced("Refreshed");
            // Broadcast event
            event(new OrderEvent('Refreshed'));

            DB::commit();
            return response()->json('Order status updated successfully.', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function getOutstanding(Request $request, $id)
    {
        try {
            $outstanding = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('facilities', 'facilities.id', '=', 'orders.facility_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('order_items.id', $id)
                ->whereNotIn('order_items.status', ['pending', 'delivered'])
                ->select(
                    'products.name as product_name',
                    'facilities.name as facility_name',
                    'order_items.quantity',
                    'order_items.status'
                )
                ->get()
                ->map(function ($item) {
                    return [
                        'product' => $item->product_name,
                        'facility' => $item->facility_name,
                        'quantity' => $item->quantity,
                        'status' => $item->status
                    ];
                });

            return response()->json($outstanding, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function updateItem(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'id' => 'required|exists:order_items,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $orderItem = OrderItem::findOrFail($validated['id']);

            // Check if the new quantity will not exceed the current inventory quantity
            $currentInventoryQuantity = \App\Models\Inventory::where('product_id', $orderItem->product_id)->sum('quantity');
            if ($validated['quantity'] > $currentInventoryQuantity) {
                return response()->json('The new quantity exceeds the current inventory quantity.', 500);
            }

            $orderItem->update([
                'quantity' => $validated['quantity'],
            ]);
            Kafka::publishOrderPlaced("Refreshed");
            event(new OrderEvent('Refreshed'));

            DB::commit();
            return response()->json('Order item updated successfully.', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Bulk change status of orders
     */
    public function bulkChangeStatus(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|exists:orders,id',
            'status' => ['required', Rule::in(['approved', 'in process', 'dispatched', 'delivery_pending', 'delivered'])]
        ]);

        $allowedTransitions = [
            'pending' => ['approved'],
            'approved' => ['in process'],
            'in process' => ['dispatched'],
            'dispatched' => ['delivery_pending', 'delivered'],
            'delivery_pending' => ['delivered']
        ];

        DB::beginTransaction();
        try {
            $orders = Order::whereIn('id', $request->order_ids)->get();
            $updatedCount = 0;

            foreach ($orders as $order) {
                if (
                    isset($allowedTransitions[$order->status]) &&
                    in_array($request->status, $allowedTransitions[$order->status])
                ) {

                    $oldStatus = $order->status;
                    $order->status = $request->status;
                    $order->save();


                    $updatedCount++;
                }
            }

            DB::commit();

            if ($updatedCount === 0) {
                return response()->json("No orders were eligible for status change", 500);
            }

            Kafka::publishOrderPlaced('Refreshed');
            event(new OrderEvent('Order status updated'));
            return response()->json("Successfully updated {$updatedCount} orders to {$request->status}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Bulk change status of order items
     */
    public function bulkChangeItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'item_ids' => 'required|array',
                'item_ids.*' => 'required|exists:order_items,id',
                'status' => ['required', Rule::in(['approved', 'in process', 'dispatched', 'delivery_pending', 'delivered'])],
                'warehouse_id' => 'nullable|exists:warehouses,id'
            ]);

            $items = OrderItem::with('order')->whereIn('id', $request->item_ids)->get();
            $updatedCount = 0;
            $updatedOrders = [];

            $allowedTransitions = [
                'pending' => ['approved'],
                'approved' => ['in process'],
                'in process' => ['dispatched'],
                'dispatched' => ['delivery_pending', 'delivered'],
                'delivery_pending' => ['delivered']
            ];


            foreach ($items as $item) {
                // Check if transition is allowed
                if (
                    !isset($allowedTransitions[$item->status]) ||
                    !in_array($request->status, $allowedTransitions[$item->status])
                ) {
                    continue;
                }

                $oldStatus = $item->status;
                $item->status = $request->status;
                // Get all available inventory for this product from the warehouse, ordered by expiry date (FIFO)
                $warehouseInventories = Inventory::where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)
                    // ->where('warehouse_id', $item->warehouse_id)
                    ->orderBy('expiry_date', 'asc')  // Order by expiry date for FIFO (oldest first)
                    ->get();

                $remainingQuantity = (float) $item->quantity - (float) $item->quantity_on_order;

                if ($warehouseInventories->sum('quantity') < $remainingQuantity) {
                    return response()->json("Not enough items in the inventory", 500);
                }
                if ($request->status == 'approved') {
                    $item->approved_at = Carbon::now()->toDateString();
                    $item->approved_by = auth()->id();
                    $item->save();
                }

                if ($request->status == 'in process') {
                    $item->in_process = 1;
                    $item->save();
                }

                if ($request->status == 'dispatched') {
                    $item->dispatched_at = Carbon::now()->toDateString();
                    $item->dispatched_by = auth()->id();
                    $item->warehouse_id = $request->warehouse_id;
                    $item->save();
                }
                if ($request->status == 'delivery_pending') {
                    $item->delivery_pending_at = Carbon::now()->toDateString();
                    $item->save();
                }
                if ($request->status == 'delivered') {
                    $item->delivered = 1;

                    $usedInventories = [];

                    foreach ($warehouseInventories as $warehouseInventory) {
                        if ($remainingQuantity <= 0) break;

                        // Calculate how much we can take from this batch
                        $quantityToTake = min($remainingQuantity, $warehouseInventory->quantity);

                        // Update or create facility inventory for this batch
                        $facilityInventory = $item->order->facility->inventories()
                            ->where('product_id', $item->product_id)
                            ->where('batch_number', $warehouseInventory->batch_number)
                            ->first();

                        if ($facilityInventory) {
                            $facilityInventory->increment('quantity', $quantityToTake);
                            // Handle facility inventory with quantity 0
                            if ($facilityInventory->fresh()->quantity <= 0) {
                                // Get all zero quantity records for this product
                                $zeroQuantityInventories = $item->order->facility
                                    ->inventories()
                                    ->where('product_id', $item->product_id)
                                    ->where('quantity', '=', 0)
                                    ->orderBy('created_at', 'asc')  // Get oldest first
                                    ->get();

                                if ($zeroQuantityInventories->count() > 1) {
                                    // Keep the first (oldest) record and reset its metadata
                                    $firstRecord = $zeroQuantityInventories->first();
                                    $firstRecord->update([
                                        'batch_number' => null,
                                        'expiry_date' => null,
                                        'location' => null,
                                        'warehouse_id' => null
                                    ]);

                                    // Delete all other zero quantity records
                                    $item->order->facility->inventories()
                                        ->where('product_id', $item->product_id)
                                        ->where('quantity', '=', 0)
                                        ->where('id', '!=', $firstRecord->id)
                                        ->delete();
                                } else {
                                    // If this is the only zero quantity record, just reset its metadata
                                    $facilityInventory->update([
                                        'batch_number' => null,
                                        'expiry_date' => null,
                                        'location' => null,
                                        'warehouse_id' => null
                                    ]);
                                }
                            }
                        } else {
                            $item->order->facility->inventories()->create([
                                'product_id' => $item->product_id,
                                'batch_number' => $warehouseInventory->batch_number,
                                'expiry_date' => $warehouseInventory->expiry_date,
                                'quantity' => $quantityToTake,
                                'updated_at' => now()
                            ]);
                        }
                        // here we gonna update the inventories table
                        $warehouseInventory->decrement('quantity', $quantityToTake);

                        // Remove inventory record if quantity is 0
                        if ($warehouseInventory->fresh()->quantity <= 0) {
                            $warehouseInventory->delete();
                        }

                        // Track used inventory for logging
                        $usedInventories[] = [
                            'batch_number' => $warehouseInventory->batch_number,
                            'expiry_date' => $warehouseInventory->expiry_date->format('Y-m-d'),
                            'quantity' => $quantityToTake
                        ];

                        $remainingQuantity -= $quantityToTake;
                    }

                    // Check if all items in this order have the same status
                    $pendingItems = $item->order->items()
                        ->where('status', '!=', 'delivered')
                        ->count();

                    if ($pendingItems === 0) {
                        $item->order->status = 'completed';
                        $item->order->save();
                    }

                    $item->delivered = 1;
                    $item->status = 'delivered';
                    $item->save();
                }

                $zeroQuantityInventories = Inventory::where('product_id', $item->product_id)
                    ->where('warehouse_id', $item->warehouse_id)
                    ->where('quantity', '=', 0)
                    ->get();

                if ($zeroQuantityInventories->count() > 1) {
                    // Keep the oldest record and reset its metadata
                    $oldestRecord = $zeroQuantityInventories->sortBy('created_at')->first();
                    $oldestRecord->update([
                        'batch_number' => null,
                        'expiry_date' => null,
                        'location' => null,
                        'warehouse_id' => null
                    ]);

                    // Delete all other zero quantity records except the oldest
                    Inventory::where('product_id', $item->product_id)
                        ->where('warehouse_id', $item->warehouse_id)
                        ->where('quantity', '=', 0)
                        ->where('id', '!=', $oldestRecord->id)
                        ->delete();
                } elseif ($zeroQuantityInventories->count() == 1) {
                    // If only one record exists, just reset its metadata
                    $zeroQuantityInventories->first()->update([
                        'batch_number' => null,
                        'expiry_date' => null,
                        'location' => null,
                        'warehouse_id' => null
                    ]);
                }

                // Broadcast event
                Kafka::publishOrderPlaced('Refreshed');
                event(new OrderEvent('Refreshed'));

                // Track unique orders that were affected
                if (!in_array($item->order_id, $updatedOrders)) {
                    $updatedOrders[] = $item->order_id;

                    // Check if all items in this order have the same status
                    // $allItemsSameStatus = $item->order->items()
                    //     ->where('status', '!=', $request->status)
                    //     ->doesntExist();

                    // if ($allItemsSameStatus) {
                    //     $item->order->status = "completed";
                    //     $item->order->save();
                    // }

                }

                $updatedCount++;
            }

            DB::commit();

            if ($updatedCount === 0) {
                return response()->json("No items were eligible for status change. Please check if the status transitions are allowed.", 500);
            }

            Kafka::publishOrderPlaced('Refreshed');

            event(new OrderEvent('Order items status updated'));
            return response()->json("Successfully updated {$updatedCount} items to {$request->status}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function changeItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'status' => 'required|in:received'
            ]);

            $order = Order::with('items.inventory_allocations.backorders','items.inventory_allocations.warehouse','items.inventory_allocations.location','items.product', 'facility')
                ->where('status', 'delivered')
                ->findOrFail($request->order_id);
            if($order->status != 'delivered'){
                return response()->json('Order is not delivered', 500);
            }
            $debugInfo = []; // For debugging purposes
            
            foreach ($order->items as $item) {
                // Debug information for this item
                $itemDebug = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity_to_release' => $item->quantity_to_release,
                    'received_quantity' => $item->received_quantity,
                    'allocations' => []
                ];
                
                foreach ($item->inventory_allocations as $allocation) {
                    // Calculate total back order quantity for this allocation
                    $backOrderTotal = 0;
                    $backOrderDetails = [];
                    
                    if ($allocation->backorders && count($allocation->backorders) > 0) {
                        foreach ($allocation->backorders as $backorder) {
                            $backOrderTotal += $backorder->quantity;
                            $backOrderDetails[] = [
                                'id' => $backorder->id,
                                'type' => $backorder->type,
                                'quantity' => $backorder->quantity,
                                'notes' => $backorder->notes
                            ];
                        }
                    }
                    
                    // Calculate actual quantity to add to inventory (allocation quantity minus back orders)
                    $actualQuantity = $item->received_quantity;
                    
                    // Debug information for this allocation
                    $allocationDebug = [
                        'allocation_id' => $allocation->id,
                        'batch_number' => $allocation->batch_number,
                        'expiry_date' => $allocation->expiry_date,
                        'allocated_quantity' => $allocation->allocated_quantity,
                        'back_order_total' => $backOrderTotal,
                        'actual_quantity_to_add' => $actualQuantity,
                        'back_orders' => $backOrderDetails
                    ];
                    
                    // Update the allocation status
                    $allocation->update([
                        'status' => $request->status
                    ]);
                    
                    // Add to facility inventory if actual quantity is greater than 0
                    if ($actualQuantity > 0) {
                        // Check if facility inventory already exists for this product/batch
                        $facilityInventory = FacilityInventory::where('facility_id', $order->facility_id)
                            ->where('product_id', $item->product_id)
                            ->where('batch_number', $allocation->batch_number)
                            ->first();
                        
                        if ($facilityInventory) {
                            // Update existing inventory
                            $facilityInventory->increment('quantity', $actualQuantity);
                            $allocationDebug['inventory_action'] = 'updated_existing';
                            $allocationDebug['inventory_id'] = $facilityInventory->id;
                            $allocationDebug['new_total'] = $facilityInventory->fresh()->quantity;
                        } else {
                            // Create new inventory record
                            $facilityInventory = FacilityInventory::create([
                                'facility_id' => $order->facility_id,
                                'product_id' => $item->product_id,
                                'quantity' => $actualQuantity,
                                'batch_number' => $allocation->batch_number,
                                'expiry_date' => $allocation->expiry_date,
                                'is_active' => 1,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id()
                            ]);
                            $allocationDebug['inventory_action'] = 'created_new';
                            $allocationDebug['inventory_id'] = $facilityInventory->id;
                        }
                    } else {
                        $allocationDebug['inventory_action'] = 'skipped_zero_quantity';
                    }
                    
                    $itemDebug['allocations'][] = $allocationDebug;
                }
                
                $debugInfo[] = $itemDebug;
            }
            
            // Update order status to received
            $order->status = 'received';
            $order->save();

            // Broadcast event if needed
            // Kafka::publishOrderPlaced('Refreshed');
            // event(new OrderEvent('Refreshed'));

            DB::commit();
            
            // Return debug information along with success message
            return response()->json([
                'message' => 'Order received successfully and inventory updated.',
                'order_id' => $order->id,
                'new_status' => $order->status,
                'debug_info' => $debugInfo
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('orders.id', $id)
                ->with(['items.product', 'items.inventory_allocations.backorders', 'items.inventory_allocations.warehouse', 'items.inventory_allocations.location', 'facility', 'user'])
                ->first();

            // Get items with SOH using subquery
            $items = DB::table('order_items')
                ->select([
                    'order_items.*',
                    'products.name as product_name',
                    'inventory_sums.total_quantity as soh'
                ])
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin(DB::raw('(
                    SELECT product_id, SUM(quantity) as total_quantity
                    FROM inventories
                    GROUP BY product_id
                ) as inventory_sums'), 'products.id', '=', 'inventory_sums.product_id')
                ->where('order_items.order_id', $id)
                ->get();

            $order->items = $items;
            $products = Product::select('id','name')->get();
            
            DB::commit();
            return inertia("Order/Show", ['order' => $order, 'products' => $products]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return inertia("Order/Show", ['error' =>  $th->getMessage()]);
        }
    }

    public function pending(Request $request)
    {
        $query = Order::with(['facility', 'user'])
            ->where('status', 'pending');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('facility', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(10);

        return Inertia::render('Order/Pending', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function approved(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'approved')
            ->latest()
            ->get();

        return Inertia::render('Order/Approved', [
            'orders' => $orders,
        ]);
    }

    public function inProcess(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'in_process')
            ->latest()
            ->get();

        return Inertia::render('Order/InProcess', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function dispatched(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'dispatched')
            ->latest()
            ->get();

        return Inertia::render('Order/Dispatched', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function delivered(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'delivered')
            ->latest()
            ->get();

        return Inertia::render('Order/Delivered', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function received(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'received')
            ->latest()
            ->get();

        return Inertia::render('Order/Received', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function all(Request $request)
    {
        $query = Order::with(['facility', 'user']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('facility', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(10);

        return Inertia::render('Order/All', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function create(Request $request){
        $items = Product::get();
        return Inertia::render('Order/Create', [
            'items' => $items
        ]);
    }

    private const QUARTER_START_DATES = [
        1 => '01-01',
        2 => '01-04',
        3 => '01-07',
        4 => '01-10'
    ];

    private function generateOrderNumber() {
        $now = Carbon::now();
        $quarter = $now->quarter;
        $year = $now->year;

        // Get the last order number for this quarter
        $lastOrder = Order::where('order_number', 'like', "OR-{$quarter}-%")
            ->whereYear('created_at', $year)
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastOrder->order_number, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        // Format: OR-1-0001
        return sprintf("OR-%d-%04d", $quarter, $newSequence);
    }

    /**
     * Handle back orders for order items
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function backorder(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'order_item_id' => 'required|exists:order_items,id',
                'backorders' => 'required|array',
                'received_quantity' => 'required|numeric|min:0',
                'backorders.*.inventory_allocation_id' => 'required|exists:inventory_allocations,id',
                'backorders.*.quantity' => 'required|numeric|min:0',
                'backorders.*.type' => 'required|in:Missing,Damaged,Expired,Lost',
                'backorders.*.notes' => 'nullable|string',
                'backorders.*.id' => 'nullable|exists:facility_backorders,id',
                'deleted_backorders' => 'nullable|array',
                'deleted_backorders.*' => 'exists:facility_backorders,id'
            ]);

            return DB::transaction(function () use ($request) {
                // Get the order item and its associated order
                $orderItem = OrderItem::findOrFail($request->order_item_id);
                $order = $orderItem->order;
                
                // Update the order item's received quantity with the value from the frontend
                $orderItem->received_quantity = $request->received_quantity;
                $orderItem->save();
                
                // Calculate total back order quantity
                $totalBackOrderQty = collect($request->backorders)->sum('quantity');
                
                // Ensure the back order quantity doesn't exceed the difference between ordered and received
                $maxBackOrderQty = $orderItem->quantity - $orderItem->received_quantity;
                
                if ($totalBackOrderQty > $maxBackOrderQty) {
                    return response()->json('Total back order quantity exceeds the maximum allowed.', 500);
                }
                
                // Process deleted back orders first
                if ($request->has('deleted_backorders') && !empty($request->deleted_backorders)) {
                    FacilityBackorder::whereIn('id', $request->deleted_backorders)->delete();
                }
                
                // Process back orders (create new ones or update existing ones)
                foreach ($request->backorders as $backorderData) {
                    $inventoryAllocation = InventoryAllocation::where('id', $backorderData['inventory_allocation_id'])
                        ->where('order_item_id', $orderItem->id)
                        ->first();
                        
                    if (!$inventoryAllocation) {
                        return response()->json('Invalid inventory allocation specified.', 500);
                    }
                    
                    // Ensure the back order quantity doesn't exceed the allocation quantity
                    if ($backorderData['quantity'] > $inventoryAllocation->allocated_quantity) {
                        return response()->json('Back order quantity exceeds allocated quantity for batch ' . $inventoryAllocation->batch_number, 500);
                    }
                    
                    // Check if this is an existing back order that needs to be updated
                    if (isset($backorderData['id'])) {
                        $backorder = FacilityBackorder::find($backorderData['id']);
                        if ($backorder) {
                            $backorder->update([
                                'quantity' => $backorderData['quantity'],
                                'notes' => $backorderData['notes'],
                                'type' => $backorderData['type'],
                                'status' => 'pending',
                                'updated_by' => auth()->id()
                            ]);
                        }
                    } else {
                        // Create a new back order
                        FacilityBackorder::create([
                            'order_item_id' => $orderItem->id,
                            'product_id' => $orderItem->product_id,
                            'inventory_allocation_id' => $inventoryAllocation->id,
                            'quantity' => $backorderData['quantity'],
                            'notes' => $backorderData['notes'],
                            'type' => $backorderData['type'],
                            'status' => 'pending',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id()
                        ]);
                    }
                }
                
                // Check if the order item has any back orders after processing
                $hasBackOrders = FacilityBackorder::where('order_item_id', $orderItem->id)->exists();
                
                return response()->json('Back orders have been recorded successfully', 200);
            });
            
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function removeBackOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:facility_backorders,id'
            ]);
            
            $backorder = FacilityBackorder::findOrFail($request->id);
            $backorder->delete();
            
            return response()->json('Back order has been removed successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function checkInventory(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);
            
            $productId = $request->product_id;
            $facilityId = auth()->user()->facility_id;
            
            // Check if the item is in an active order pipeline
            $inThePipeline = OrderItem::where('product_id', $productId)
                ->whereHas('order', function ($query) use ($facilityId) {
                    $query->where('facility_id', $facilityId)
                        ->where('status', '!=', 'received');
                })
                ->exists();
                
            if ($inThePipeline) {
                return response()->json("This item is already in an active order pipeline.", 500);
            }
            
            // Get current Stock on Hand (SOH) for the facility - aggregate all quantities
            // Use groupBy to ensure proper aggregation if there are multiple entries for the same product
            $soh = DB::table('facility_inventories')
                ->where('product_id', $productId)
                ->where('facility_id', $facilityId)
                ->sum('quantity');
                
            // Calculate Average Monthly Consumption (AMC) from the last 4 months' AMC values
            $now = Carbon::now();
            $currentMonth = $now->format('Y-m');
            
            // Get the current month and previous 3 months
            $months = [];
            for ($i = 0; $i < 4; $i++) {
                $months[] = Carbon::now()->subMonths($i)->format('Y-m');
            }

            // Get AMC values for the last 4 months for this product and facility
            $amcValues = DB::table('monthly_consumptions')
                ->where('product_id', $productId)
                ->where('facility_id', $facilityId)
                ->whereIn('month_year', $months)
                ->select('month_year', 'amc')
                ->sum('amc');

            // logger()->info("amcValues:", $amcValues);
            
            // Calculate average of the AMC values
            $totalAmc = $amcValues;
            $countAmc = 4;
            
            // Average of the last 4 months' AMC values, or 0 if none found
            $amc = $totalAmc / $countAmc;
                        
            // Get number of days since the start of the quarter
            $quarter = $now->quarter;
            // Format is 'DD-MM' in the constant, so we need to parse it correctly
            $quarterStartDateParts = explode('-', self::QUARTER_START_DATES[$quarter]);
            $day = $quarterStartDateParts[0];
            $month = $quarterStartDateParts[1];
            
            // Create the quarter start date without time component
            $quarterStartDate = Carbon::createFromDate($now->year, $month, $day)->startOfDay();
            $nowDate = Carbon::now()->startOfDay();
            
            // Calculate days since quarter start (ensure positive value) - without time component
            $daysSinceQuarterStart = $quarterStartDate->diffInDays($nowDate);
            $debug['days_since_quarter_start'] = $daysSinceQuarterStart;
            
            // Calculate days remaining in the 120-day period
            $daysRemaining = 120 - $daysSinceQuarterStart;
            $debug['days_remaining'] = $daysRemaining;
            
            // Quantity on Order (QOO) - default to 0 as specified
            $qoo = 0;
            
            // Calculate required quantity using the formula:
            // AMC * (120 days - days since quarter start) - SOH - QOO
            $requiredQuantity = ceil($amc * $daysRemaining) - $soh - $qoo;
            $requiredQuantity = max(0, $requiredQuantity); // Ensure it's not negative
            
            // Get product name
            $product = DB::table('products')
                ->where('id', $productId)
                ->select('name')
                ->first();

            $totalInventory = DB::table('inventories')
                ->where('product_id', $request->product_id)
                ->where('expiry_date', '>=', Carbon::now()->addMonths(1)->toDateString())
                ->sum('quantity');

            logger()->info($totalInventory);
                
            if ((int) $requiredQuantity > (int) $totalInventory) {
                return response()->json('Insufficient inventory to fulfill the required quantity.', 500);
            }
            
            return response()->json([
                'name' => $product ? $product->name : null,
                'quantity' => $requiredQuantity,
                'soh' => $soh,
                'amc' => $amc,
                'days_since_quarter_start' => $daysSinceQuarterStart,
                'no_of_days' => $daysSinceQuarterStart,
                'insufficient_inventory' => false
            ], 200);
            
        } catch (\Throwable $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
