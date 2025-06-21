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
use App\Models\FacilityInventoryItem;
use App\Models\InventoryItem;
use App\Models\FacilityInventory;
// App Events and Resources
use App\Models\EligibleItem;
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
use App\Services\FacilityInventoryMovementService;

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
        $facility = $request->facility;
        $facilityLocation = $request->facilityLocation;
        $query = Order::query();

        $query->where('facility_id', auth()->user()->facility_id);

        if($request->filled('search')){
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        if($request->filled('currentStatus')){
            $query->where('status', $request->currentStatus);
        }

        if($request->filled('dateFrom') && !$request->filled('dateTo')){
            $query->whereDate('order_date', $request->dateFrom);
        }

        if($request->filled('dateFrom') && $request->filled('dateTo')){
            $query->whereBetween('order_date', [$request->dateFrom, $request->dateTo]);
        }

        if($request->filled('orderType')){
            $query->where('order_type', $request->orderType);
        }
        
        $query->with(['facility.handledby:id,name', 'user']);

        $orders = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
            ->withQueryString();
        $orders->setPath(url()->current()); // Force Laravel to use full URLs
        // Get order statistics from orders table
        $stats = DB::table('orders')
            ->where('facility_id', auth()->user()->facility_id)
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
            'reviewed' => 0,
            'approved' => 0,
            'rejected' => 0,
            'in_process' => 0,
            'dispatched' => 0,
            'received' => 0
        ];

        $stats = array_merge($defaultStats, $stats);
        
        return Inertia::render('Order/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page', 'orderType','currentStatus', 'dateFrom', 'dateTo','per_page'),
            'stats' => $stats,
            'totalOrders' => Order::count()
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
                'items.*.quantity' => 'required|numeric',
            ],[
                'items.*.product_id.required' => 'Item is required',
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
                        'days' => $item['no_of_days'],
                        'status' => 'pending',
                    ]);

                    // Calculate the quantity to deduct from inventory (quantity - quantity_on_order)
                    $quantityToDeduct = (float) $item['quantity'] - (float) ($item['quantity_on_order'] ?? 0);
                    
                    // Get available inventory for this product
                    $availableInventory = InventoryItem::where('product_id', $item['product_id'])
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
                            'uom' => $inventory->uom,
                            'unit_cost' => $inventory->unit_cost,
                            'total_cost' => $inventory->unit_cost * $quantityToAllocate,
                            'allocation_type' => 'Replenishment'
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

    public function receivedQuantity(Request $request){
        try {
            $request->validate([
                'order_item_id' => 'required',
                'received_quantity' => 'required|min:1',
            ]);
            $order = OrderItem::whereHas('order', function($query){
                $query->where('status', 'delivered');
            })->find($request->order_item_id);
            if(!$order) return response()->json("Order not exist, or not in delivered stage", 500);
            if((int) $order->received_quantity > (int) $order->quantity) return response()->json("Received quantity can be exceed the original quantity", 500);
            $order->received_quantity = $request->received_quantity;
            return response()->json('Done', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
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

    public function changeItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'status' => 'required'
            ]);

            $order = Order::with('items.inventory_allocations.backorders')
                ->where('id', $request->order_id)
                ->first();
            if($request->status == 'delivered' && $order->status == 'dispatched'){
                $order->status = 'delivered';
                $order->delivered_at = Carbon::now();
                $order->delivered_by = auth()->user()->id;
                $order->save();
                DB::commit();
                return response()->json("Marked as Delivered", 200);
            }
            
            $debugInfo = []; // For debugging purposes
            
            $facilityInventoryMovementService = new FacilityInventoryMovementService();
            $user = auth()->user();
            
            foreach ($order->items as $item) {
                // Debug information for this item
                
                foreach ($item->inventory_allocations as $allocation) {
                    // Calculate total back order quantity for this allocation
                    if((int) $allocation->allocated_quantity < (int) $allocation->backorders->sum('quantity')){
                        DB::rollback();
                        return response()->json('Backorder quantities exceeded the allocated quantity', 500);
                    }
                    $finalQuantity = $allocation->allocated_quantity - $allocation->backorders->sum('quantity');
                    $inventory = FacilityInventory::where('facility_id', $user->facility_id)
                        ->where('product_id', $allocation->product_id)
                        ->first();

                    if($inventory){
                        $inventoryItem = $inventory->item()->where('batch_number', $allocation['batch_number'])->first();
                        if($inventoryItem){
                            $inventoryItem->increment(['quantity' => $finalQuantity]);
                        }else{
                            $inventory->items()->create([
                                'product_id' => $allocation['product_id'],
                                'quantity' => $allocation['finalQuantity'],
                                'expiry_date' => $allocation['expiry_date'],
                                'batch_number' => $allocation['batch_number'],
                                'barcode' => $allocation['barcode'],
                                'uom' => $allocation['uom'],
                                'unit_cost' => $allocation['unit_cost'],
                                'total_cost' => $allocation['unit_cost'] *  $allocation['finalQuantity']
                            ]);
                        }
                        
                    }else{
                        $inventory = FacilityInventory::create([
                            'facility_id' => $order->facility_id,
                            'product_id' => $allocation->product_id
                        ]);
                        $inventory->items()->create([
                            'product_id' => $allocation->product_id,
                            'batch_number' => $allocation->batch_number,
                            'expiry_date' => $allocation->expiry_date,
                            'quantity' => $finalQuantity,
                            'barcode' => $allocation->barcode,
                            'uom' => $allocation->uom,
                            'unit_cost' => $allocation->unit_cost,
                            'total_cost' => $allocation->unit_cost * $finalQuantity
                        ]);
                    }
                    
                    // Record facility inventory movement for received quantity
                    if ($finalQuantity > 0) {
                        $facilityInventoryMovementService->recordOrderReceived(
                            $order,
                            $item,
                            $finalQuantity,
                            $allocation->batch_number,
                            $allocation->expiry_date,
                            $allocation->barcode
                        );
                    }
                }
            }
            
            // Update order status to received
            $order->status = 'received';
            $order->received_at = Carbon::now();
            $order->received_by = auth()->user()->id;
            $order->save();

            // Broadcast event if needed
            // Kafka::publishOrderPlaced('Refreshed');
            // event(new OrderEvent('Refreshed'));

            DB::commit();
            
            // Return debug information along with success message
            return response()->json('Order received successfully and inventory updated.', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('id', $id)
                ->with(['items.product.category','dispatch', 'items.inventory_allocations.warehouse', 'items.inventory_allocations.location','items.inventory_allocations.backorders', 'facility', 'user','reviewedBy', 'approvedBy', 'processedBy','dispatchedBy','deliveredBy','receivedBy'])
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
        $facility = Facility::find(auth()->user()->facility_id);
        $items = Product::whereHas('eligible', function($q) use ($facility) {
            $q->where('facility_type', $facility->facility_type);
        })->get();
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
                $orderItem = OrderItem::with('inventory_allocations')->find($request->order_item_id);
                $order = $orderItem->order;
                
                // Update the order item's received quantity with the value from the frontend
                $orderItem->received_quantity = $request->received_quantity;
                
                // Calculate total back order quantity
                $totalBackOrderQty = collect($request->backorders)->sum('quantity');
                
                // Ensure the back order quantity doesn't exceed the difference between ordered and received
                $maxBackOrderQty = collect($orderItem->inventory_allocations)->sum('allocated_quantity');
                
                if ($totalBackOrderQty > $maxBackOrderQty) {
                    return response()->json('Total back order quantity exceeds the maximum allowed.', 500);
                }
                $orderItem->save();
                
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
            $user = auth()->user();
            $facility = Facility::find($user->facility_id);
            if (!$facility) {
                return response()->json("Facility not found.", 500);
            }
    
            // Check eligibility without facility_id as per your logic
            $isEligible = EligibleItem::where('product_id', $productId)
                ->where('facility_type', $facility->facility_type)
                ->with('product');
    
            if (!$isEligible->exists()) {
                return response()->json("This item is not eligible for ordering.", 500);
            }
    
            // Check if product is in active order pipeline (status != received)
            $inThePipeline = OrderItem::where('product_id', $productId)
                ->whereHas('order', function ($query) use ($facility) {
                    $query->where('facility_id', $facility->id)
                        ->where('status', '!=', 'received');
                })
                ->first();
    
            if ($inThePipeline) {
                return response()->json("This item is already in an active order pipeline.", 500);
            }
    
            // Calculate Stock on Hand (SOH)
            $soh = FacilityInventoryItem::whereHas('inventory', function($query) use ($user){
                $query->where('facility_id', $user->facility_id);
            })
                ->where('product_id', $request->product_id)
                ->sum('quantity');

            // if($soh){
            //     $soh = 0;
            // }
    
            // Get last 4 months for AMC calculation (exclude current month)
            $months = [];
            for ($i = 1; $i <= 4; $i++) {
                $months[] = Carbon::now()->subMonths($i)->format('Y-m');
            }
    
            // // Get report IDs for those months and facility
            $reportIds = DB::table('monthly_consumption_reports')
                ->where('facility_id', $facility->id)
                ->whereIn('month_year', $months)
                ->pluck('id');
    
            // Total consumption over last 4 months for the product
            $totalConsumption = DB::table('monthly_consumption_items')
                ->whereIn('parent_id', $reportIds)
                ->where('product_id', $productId)
                ->sum('quantity');
    
            // Average Monthly Consumption (AMC) = total / 4 months
            $amc = $totalConsumption / 4;
    
            // Determine days since last received order update, fallback to quarter start if none
            $lastReceivedOrder = Order::where('facility_id', $facility->id)
                ->where('status', 'received')
                ->whereHas('items', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                })
                ->where('order_type', 'quarterly')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastReceivedOrder) {
                $lastReceivedDate = Carbon::parse($lastReceivedOrder->updated_at)->startOfDay();
                $daysSince = $lastReceivedDate->diffInDays(Carbon::now()->startOfDay());
            } else {
                // Fallback: use quarter start date
                $now = Carbon::now();
                $quarter = $now->quarter;
                $quarterStartDateParts = explode('-', self::QUARTER_START_DATES[$quarter]);
                $quarterStart = Carbon::createFromDate($now->year, $quarterStartDateParts[1], $quarterStartDateParts[0])->startOfDay();
                $daysSince = $quarterStart->diffInDays($now->startOfDay());
            }
    
            // Days remaining in 120-day cycle
            $daysRemaining = 120 - $daysSince;
    
            // // Quantity on Order (QOO) default to 0
            $qoo = 0;
    
            // Calculate projected consumption:
            // AMC is average monthly, so convert to daily by dividing by ~30 days
            $projectedConsumption = ($amc / 30) * $daysRemaining;
    
            // Calculate required quantity = projected consumption - SOH - QOO
            $requiredQuantity = ceil($projectedConsumption - $soh - $qoo);
            $requiredQuantity = max(0, $requiredQuantity);
    
            // If no AMC and no SOH and quantity zero, assign default order quantity (first time order)
            if ($amc == 0 && $soh == 0 && $requiredQuantity == 0) {
                $requiredQuantity = (int) $daysRemaining; // default value for first order, adjust as needed
            }
    
            $product = $isEligible->first()->product;
    
            // Check total available inventory (with expiry check)
            $totalInventory = Inventory::where('product_id', $product->id)
                ->whereHas('items', function($q){
                    $q->where('expiry_date', '>=', Carbon::now()->addMonths(1)->toDateString());
                })->withSum('items', 'quantity')
                ->first();
    
            // if ($requiredQuantity > $totalInventory && (int) $totalInventory->items_sum_quantity ?? 0) {
            //     return response()->json([
            //         'name' => $product->name,
            //         'quantity' => $requiredQuantity,
            //         'soh' => $soh,
            //         'amc' => $amc,
            //         'days_since_quarter_start' => $daysRemaining,
            //         'no_of_days' => $daysRemaining,
            //         'insufficient_inventory' => false
            //     ], 200);
            // }
    
    
            return response()->json([
                'name' => $product->name,
                'quantity' => $requiredQuantity ?? 0,
                'soh' => $soh,
                'amc' => $amc,
                'days_since_quarter_start' => $daysRemaining,
                'no_of_days' => $daysRemaining,
                'insufficient_inventory' => false
            ], 200);
    
        } catch (\Throwable $e) {
            logger()->error('Error in checkInventory', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json($e->getMessage(), 500);
        }
    }
    
}
