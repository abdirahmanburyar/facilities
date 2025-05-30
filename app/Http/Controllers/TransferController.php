<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\Facility;
use App\Models\FacilityInventory;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Product;
use App\Models\InventoryAllocation;
use App\Models\User;
use App\Models\Supplier;
use App\Models\FacilityBackorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TransferController extends Controller
{
    public function approve($id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            
            if ($transfer->status !== 'pending') {
                return redirect()->back()->with('error', 'Transfer must be pending to be approved');
            }

            $transfer->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);

            return redirect()->route('transfers.show', $id)->with('success', 'Transfer approved successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve transfer: ' . $e->getMessage());
        }
    }

    public function reject($id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            
            if ($transfer->status !== 'pending') {
                return redirect()->back()->with('error', 'Transfer must be pending to be rejected');
            }

            $transfer->update([
                'status' => 'rejected',
                'rejected_by' => Auth::id(),
                'rejected_at' => now()
            ]);

            // Return inventory quantity if applicable
            if (!empty($transfer->inventory_id)) {
                $inventory = Inventory::findOrFail($transfer->inventory_id);
                $inventory->increment('quantity', $transfer->quantity);
            }

            return redirect()->route('transfers.show', $id)->with('success', 'Transfer rejected successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to reject transfer: ' . $e->getMessage());
        }
    }

    public function inProcess($id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            
            if ($transfer->status !== 'approved') {
                return redirect()->back()->with('error', 'Transfer must be approved to be set in process');
            }

            $transfer->update([
                'status' => 'in_process',
                'process_started_at' => now()
            ]);

            return redirect()->route('transfers.show', $id)->with('success', 'Transfer marked as in process');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update transfer status: ' . $e->getMessage());
        }
    }

    public function dispatch($id)
    {
        try {
            $transfer = Transfer::findOrFail($id);
            
            if ($transfer->status !== 'in_process') {
                return redirect()->back()->with('error', 'Transfer must be in process to be dispatched');
            }

            $transfer->update([
                'status' => 'dispatched',
                'dispatched_by' => Auth::id(),
                'dispatched_at' => now()
            ]);

            return redirect()->route('transfers.show', $id)->with('success', 'Transfer has been dispatched');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to dispatch transfer: ' . $e->getMessage());
        }
    }

    public function deliver($id)
    {
        $transfer = Transfer::findOrFail($id);
        if ($transfer->status !== 'dispatched') {
            return redirect()->back()->with('error', 'Transfer must be dispatched to be delivered');
        }
        $transfer->update(['status' => 'transferred']);
        return redirect()->back()->with('success', 'Transfer delivered successfully');
    }

    public function completeTransfer($id)
    {
        DB::beginTransaction();
        try {
            $transfer = Transfer::with(['toWarehouse', 'toFacility', 'items.product'])->findOrFail($id);
            
            if ($transfer->status !== 'dispatched') {
                return redirect()->back()->with('error', 'Transfer must be dispatched to be completed');
            }

            // Check if the transfer is to a warehouse or facility
            if (!$transfer->toWarehouse && !$transfer->toFacility) {
                throw new \Exception('Invalid transfer destination');
            }

            // Process each transfer item
            foreach ($transfer->items as $item) {
                $inventoryData = [
                    'product_id' => $item->product_id,
                    'batch_number' => $item->batch_number,
                    'quantity' => $transfer->quantity,
                    'expiry_date' => $item->expire_date,
                    'uom' => $item->uom ?? 'pcs'
                ];

                if ($transfer->toWarehouse) {
                    // Add to warehouse inventory
                    $inventoryData['warehouse_id'] = $transfer->to_warehouse_id;
                    Inventory::create($inventoryData);
                } else {
                    // Add to facility inventory
                    $inventoryData['facility_id'] = $transfer->to_facility_id;
                    FacilityInventory::create($inventoryData);
                }
            }

            $transfer->update([
                'status' => 'received'
            ]);

            DB::commit();
            return redirect()->route('transfers.show', $id)->with('success', 'Transfer completed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error completing transfer: ' . $e->getMessage());
        }
    }

    public function bulkApprove(Request $request)
    {
        try {
            $transfers = Transfer::whereIn('id', $request->transferIds)
                                ->where('status', 'pending')
                                ->get();

            foreach ($transfers as $transfer) {
                $transfer->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now()
                ]);
            }

            return response()->json(['message' => count($transfers) . ' transfers approved successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to approve transfers: ' . $e->getMessage()], 500);
        }
    }

    public function bulkReject(Request $request)
    {
        DB::beginTransaction();
        try {
            $transfers = Transfer::whereIn('id', $request->transferIds)
                                ->where('status', 'pending')
                                ->get();

            foreach ($transfers as $transfer) {
                $transfer->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now()
                ]);

                // Return inventory quantity
                $inventory = Inventory::findOrFail($transfer->inventory_id);
                $inventory->increment('quantity', $transfer->quantity);
            }

            DB::commit();
            return response()->json(['message' => count($transfers) . ' transfers rejected successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Failed to reject transfers: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        // Start building the query
        $query = Transfer::with('toWarehouse', 'toWarehouse', 'toFacility', 'fromFacility', 'items')->withCount('items');
        
        // Apply filters
        // Filter by tab/status
        if ($request->has('tab') && $request->tab !== 'all') {
            $query->where('status', $request->tab);
        }
        
        // Filter by search term
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('transferID', 'like', $searchTerm)
                  ->orWhereHas('toFacility', function($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('toWarehouse', function($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  });
            });
        }
        
        // Filter by facility (supports multiple selections)
        if ($request->has('facility_id') && !empty($request->facility_id)) {
            $facilityIds = explode(',', $request->facility_id);
            $query->where(function($q) use ($facilityIds) {
                $q->whereIn('to_facility_id', $facilityIds);
            });
        }
                
        // Filter by date range
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }
        
        // Execute the query
        $transfers = $query->get();
        
        // Get all transfers for statistics (unfiltered)
        $allTransfers = Transfer::all();
        $total = $allTransfers->count();
        $approvedCount = $allTransfers->whereIn('status', ['approved', 'in_process', 'dispatched', 'transferred'])->count();
        $inProcessCount = $allTransfers->whereIn('status', ['in_process', 'dispatched'])->count();
        $transferredCount = $allTransfers->where('status', 'transferred')->count();
        $rejectedCount = $allTransfers->where('status', 'rejected')->count();
        $pendingCount = $allTransfers->where('status', 'pending')->count();
        
        $statistics = [
            'approved' => [
                'count' => $approvedCount,
                'percentage' => $total > 0 ? round(($approvedCount / $total) * 100) : 0,
                'stages' => ['approved', 'in_process', 'dispatched', 'transferred']
            ],
            'pending' => [
                'count' => $pendingCount,
                'percentage' => $total > 0 ? round(($pendingCount / $total) * 100) : 0,
                'stages' => ['pending']
            ],
            'in_process' => [
                'count' => $inProcessCount,
                'percentage' => $total > 0 ? round(($inProcessCount / $total) * 100) : 0,
                'stages' => ['in_process', 'dispatched']
            ],
            'transferred' => [
                'count' => $transferredCount,
                'percentage' => $total > 0 ? round(($transferredCount / $total) * 100) : 0,
                'stages' => ['transferred']
            ],
            'rejected' => [
                'count' => $rejectedCount,
                'percentage' => $total > 0 ? round(($rejectedCount / $total) * 100) : 0,
                'stages' => ['rejected']
            ]
        ];
        
        // Get data for filter dropdowns
        $facilities = Facility::select('id', 'name')->orderBy('name')->get();
        $warehouses = Warehouse::select('id', 'name')->orderBy('name')->get();

        return inertia('Transfer/Index', [
            'transfers' => $transfers,
            'statistics' => $statistics,
            'facilities' => $facilities,
            'warehouses' => $warehouses,
            'filters' => $request->only(['search', 'facility_id', 'warehouse_id', 'date_from', 'date_to', 'tab'])
        ]);
    }

    public function store(Request $request)
    {
        logger()->info($request->all());
        DB::beginTransaction();
        try {
            $request->validate([
                'destination_type' => 'required|in:warehouse,facility',
                'destination_id' => 'required|integer',
                'items' => 'required|array',
                'items.*.product_id' => 'required|integer',
                'items.*.id' => 'required|exists:facility_inventories,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.batch_number' => 'required|string',
                'items.*.barcode' => 'nullable|string',
                'items.*.expiry_date' => 'nullable|date',
                'items.*.uom' => 'nullable|string',
                'notes' => 'nullable|string|max:500'
            ]);
            
            // Prepare transfer data
            $transferData = [
                'transferID' => str_pad(Transfer::max('id') + 1, 4, '0', STR_PAD_LEFT),
                'from_facility_id' => auth()->user()->facility_id,
                'to_warehouse_id' => $request->destination_type === 'warehouse' ? $request->destination_id : null,
                'to_facility_id' => $request->destination_type === 'facility' ? $request->destination_id : null,
                'created_by' => auth()->id(),
                'quantity' => collect($request->items)->sum('quantity'),
                'transfer_date' => now(),
                'status' => 'pending',
                'note' => $request->notes
            ];
            
            // Create the transfer record
            $transfer = Transfer::create($transferData);
            
            // Process each inventory item
            foreach ($request->items as $item) {
                $inventory = FacilityInventory::where('facility_id', auth()->user()->facility_id)   
                        ->where('product_id', $item['product_id'])
                        ->where('id', $item['id'])
                        ->first();
                        
                if (!$inventory) {
                        DB::rollBack();
                        return response()->json('Insufficient stock hand in the inventory', 500);
                    }
                
                if ((int) $item['quantity'] > (int) $inventory->quantity) {
                    DB::rollBack();
                    return response()->json('Transfer quantity cannot exceed available quantity for item: ' . 
                            ($inventory->product->name ?? 'Unknown'), 500);
                }
                
                // Create transfer item record
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'barcode' => $item['barcode'] ?? '',
                    'uom' => $item['uom'] ?? '',
                    'quantity' => $item['quantity'],
                    'batch_number' => $item['batch_number'],
                    'expire_date' => $item['expiry_date'],
                ]);
                
                // Update inventory quantity
                $inventory->decrement('quantity', $item['quantity']);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Transfer created successfully',
                'transfer_id' => $transfer->transferID,
                'transfer' => $transfer->load('toWarehouse', 'fromFacility', 'toFacility')
            ]);
        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json('Failed to create transfer: ' . $e->getMessage(), 500);
        }
    }

    public function show($id){
        $transfer = Transfer::where('id', $id)->with([
            'items.product', 
            'items.backorders', 
            'toWarehouse', 
            'fromFacility', 
            'toFacility'
        ])->first();
        return inertia('Transfer/Show', [
            'transfer' => $transfer
        ]);
    }
    
    public function disposal(Request $request){
        return inertia('Transfer/Disposal');
    }

    public function create(Request $request){
        $warehouses = Warehouse::select('id','name')->get();
        $facilities = Facility::whereNot('id', auth()->user()->facility_id)->select('id','name')->get();
        
        // Get inventory from facility_inventories table for the current user's facility
        $facilityId = auth()->user()->facility_id;
        $inventory = FacilityInventory::where('facility_id', $facilityId)
            ->where('quantity', '>', 0)
            ->with('product')
            ->get()
            ->map(function ($inventory) {
                return [
                    'id' => $inventory->id,
                    'product_id' => $inventory->product_id,
                    'product_name' => $inventory->product->name,
                    'quantity' => $inventory->quantity,
                    'barcode' => $inventory->barcode,
                    'expiry_date' => $inventory->expiry_date,
                    'batch_number' => $inventory->batch_number
                ];
            });
        
        return inertia('Transfer/Create', [
            'warehouses' => $warehouses,
            'facilities' => $facilities,
            'inventory' => $inventory,
        ]);
    }
    
    /**
     * Delete a transfer item
     */
    

    /**
     * Process inventory changes when a transfer is received
     */
    private function processInventoryChanges(Transfer $transfer, $items)
    {
        foreach ($items as $item) {
            $inventory = FacilityInventory::where([
                'facility_id' => $transfer['to_facility_id'],
                'product_id' => $item['product_id'],
                'batch_number' => $item['batch_number'],
            ])->first();

            if ($inventory) {
                $inventory->increment('quantity', $item['received_quantity']);
            } else {
                FacilityInventory::create([
                    'facility_id' => $transfer['to_facility_id'],
                    'product_id' => $item['product_id'],
                    'batch_number' => $item['batch_number'],
                    'quantity' => $item['received_quantity'],
                    'expiry_date' => $item['expire_date'],
                    'barcode' => $item['barcode'],
                ]);
            }
        }
    }
    
    /**
     * Handle back orders for transfer items
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function backorder(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'transfer_item_id' => 'required|exists:transfer_items,id',
                'backorders' => 'required|array',
                'received_quantity' => 'required|numeric|min:0',
                'backorders.*.quantity' => 'required|numeric|min:0',
                'backorders.*.type' => 'required|string',
                'backorders.*.notes' => 'nullable|string',
            ]);
            
            DB::beginTransaction();
            
            // Get the transfer item
            $transferItem = TransferItem::findOrFail($request->transfer_item_id);
            
            // Update the received quantity for the transfer item
            $transferItem->received_quantity = $request->received_quantity;
            $transferItem->save();
            
            // Process each backorder
            foreach ($request->backorders as $backorderData) {
                // Check if this is an existing backorder (has an ID)
                if (!empty($backorderData['id'])) {
                    // Update existing backorder
                    $backorder = FacilityBackorder::findOrFail($backorderData['id']);
                    $backorder->update([
                        'quantity' => $backorderData['quantity'],
                        'type' => $backorderData['type'],
                        'notes' => $backorderData['notes'] ?? null,
                        'updated_by' => auth()->id()
                    ]);
                } else {
                    // Create new backorder
                    FacilityBackorder::create([
                        'transfer_item_id' => $transferItem->id,
                        'product_id' => $transferItem->product_id,
                        'inventory_allocation_id' => $backorderData['inventory_allocation_id'],
                        'quantity' => $backorderData['quantity'],
                        'type' => $backorderData['type'],
                        'notes' => $backorderData['notes'] ?? null,
                        'status' => 'pending',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]);
                }
            }
            
            DB::commit();
            return response()->json('Back orders have been recorded successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function changeItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'status' => 'required|in:received'
            ]);

            $transfer = Transfer::with('items.backorders', 'items.product', 'fromFacility', 'toFacility')
                ->where('status', 'dispatched')
                ->findOrFail($request->transfer_id);
                
            if($transfer->status != 'dispatched'){
                return response()->json('Transfer is not dispatched', 500);
            }
            
            $debugInfo = []; // For debugging purposes
            
            foreach ($transfer->items as $item) {
                // Debug information for this item
                $itemDebug = [
                    'item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'received_quantity' => $item->received_quantity,
                    'backorders' => []
                ];
                
                // Calculate total back order quantity for this item
                $backOrderTotal = 0;
                $backOrderDetails = [];
                
                if ($item->backorders && count($item->backorders) > 0) {
                    foreach ($item->backorders as $backorder) {
                        $backOrderTotal += $backorder->quantity;
                        $backOrderDetails[] = [
                            'id' => $backorder->id,
                            'type' => $backorder->type,
                            'quantity' => $backorder->quantity,
                            'notes' => $backorder->notes
                        ];
                    }
                }
                
                // Calculate actual quantity to add to inventory (item quantity minus back orders)
                $actualQuantity = $item->received_quantity > 0 ? $item->received_quantity : $item->quantity - $backOrderTotal;
                
                $itemDebug['back_order_total'] = $backOrderTotal;
                $itemDebug['actual_quantity_to_add'] = $actualQuantity;
                $itemDebug['back_orders'] = $backOrderDetails;
                
                // Add to destination inventory if actual quantity is greater than 0
                if ($actualQuantity > 0) {
                    // Determine if destination is a facility or warehouse
                    if ($transfer->to_facility_id) {
                        // FACILITY DESTINATION: Use facility_inventories table
                        $destinationFacilityId = $transfer->to_facility_id;
                        
                        // Check if facility inventory already exists for this product with matching batch number and expiry date
                        $facilityInventory = FacilityInventory::where('facility_id', $destinationFacilityId)
                            ->where('product_id', $item->product_id)
                            ->where('batch_number', $item->batch_number)
                            ->where('expiry_date', $item->expire_date)
                            ->first();
                        
                        if ($facilityInventory) {
                            // Update existing inventory
                            $facilityInventory->increment('quantity', $actualQuantity);
                            $itemDebug['inventory_action'] = 'updated_existing_facility_inventory';
                            $itemDebug['inventory_id'] = $facilityInventory->id;
                            $itemDebug['new_total'] = $facilityInventory->fresh()->quantity;
                        } else {
                            // Create new inventory record
                            $facilityInventory = FacilityInventory::create([
                                'facility_id' => $destinationFacilityId,
                                'product_id' => $item->product_id,
                                'quantity' => $actualQuantity,
                                'batch_number' => $item->batch_number,
                                'expiry_date' => $item->expire_date,
                                'is_active' => 1,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id()
                            ]);
                            $itemDebug['inventory_action'] = 'created_new_facility_inventory';
                            $itemDebug['inventory_id'] = $facilityInventory->id;
                        }
                    } elseif ($transfer->to_warehouse_id) {
                        // WAREHOUSE DESTINATION: Use inventories table
                        $destinationWarehouseId = $transfer->to_warehouse_id;
                        
                        // Check if warehouse inventory already exists for this product with matching batch number and expiry date
                        $warehouseInventory = Inventory::where('warehouse_id', $destinationWarehouseId)
                            ->where('product_id', $item->product_id)
                            ->where('batch_number', $item->batch_number)
                            ->where('expire_date', $item->expire_date)
                            ->first();
                        
                        if ($warehouseInventory) {
                            // Update existing inventory
                            $warehouseInventory->increment('quantity', $actualQuantity);
                            $itemDebug['inventory_action'] = 'updated_existing_warehouse_inventory';
                            $itemDebug['inventory_id'] = $warehouseInventory->id;
                            $itemDebug['new_total'] = $warehouseInventory->fresh()->quantity;
                        } else {
                            // Create new inventory record
                            $warehouseInventory = Inventory::create([
                                'warehouse_id' => $destinationWarehouseId,
                                'product_id' => $item->product_id,
                                'quantity' => $actualQuantity,
                                'batch_number' => $item->batch_number,
                                'expire_date' => $item->expire_date,
                                'is_active' => 1,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id()
                            ]);
                            $itemDebug['inventory_action'] = 'created_new_warehouse_inventory';
                            $itemDebug['inventory_id'] = $warehouseInventory->id;
                        }
                    } else {
                        $itemDebug['inventory_action'] = 'skipped_no_destination';
                    }
                } else {
                    $itemDebug['inventory_action'] = 'skipped_zero_quantity';
                }
                
                // Deduct from source inventory (facility or warehouse)
                if ($transfer->from_facility_id) {
                    // FACILITY SOURCE: Use facility_inventories table
                    $sourceFacilityInventory = FacilityInventory::where('facility_id', $transfer->from_facility_id)
                        ->where('product_id', $item->product_id)
                        ->where('batch_number', $item->batch_number)
                        ->where('expiry_date', $item->expire_date)
                        ->first();
                    
                    if ($sourceFacilityInventory) {
                        // Deduct the transferred quantity from source
                        $sourceFacilityInventory->decrement('quantity', $item->quantity);
                        $itemDebug['source_inventory_action'] = 'deducted_from_facility';
                        $itemDebug['source_inventory_id'] = $sourceFacilityInventory->id;
                        $itemDebug['source_new_total'] = $sourceFacilityInventory->fresh()->quantity;
                        
                        // If inventory becomes zero or negative, mark as inactive
                        if ($sourceFacilityInventory->fresh()->quantity <= 0) {
                            $sourceFacilityInventory->update(['is_active' => 0]);
                            $itemDebug['source_inventory_deactivated'] = true;
                        }
                    } else {
                        $itemDebug['source_inventory_action'] = 'facility_inventory_not_found';
                    }
                } elseif ($transfer->from_warehouse_id) {
                    // WAREHOUSE SOURCE: Use inventories table
                    $sourceWarehouseInventory = Inventory::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->where('batch_number', $item->batch_number)
                        ->where('expire_date', $item->expire_date)
                        ->first();
                    
                    if ($sourceWarehouseInventory) {
                        // Deduct the transferred quantity from source
                        $sourceWarehouseInventory->decrement('quantity', $item->quantity);
                        $itemDebug['source_inventory_action'] = 'deducted_from_warehouse';
                        $itemDebug['source_inventory_id'] = $sourceWarehouseInventory->id;
                        $itemDebug['source_new_total'] = $sourceWarehouseInventory->fresh()->quantity;
                        
                        // If inventory becomes zero or negative, mark as inactive
                        if ($sourceWarehouseInventory->fresh()->quantity <= 0) {
                            $sourceWarehouseInventory->update(['is_active' => 0]);
                            $itemDebug['source_inventory_deactivated'] = true;
                        }
                    } else {
                        $itemDebug['source_inventory_action'] = 'warehouse_inventory_not_found';
                    }
                } else {
                    $itemDebug['source_inventory_action'] = 'skipped_no_source';
                }
                
                $debugInfo[] = $itemDebug;
            }
            
            // Update transfer status to received
            $transfer->status = 'received';
            $transfer->save();

            DB::commit();
            
            // Return debug information along with success message
            return response()->json([
                'message' => 'Transfer received successfully and inventory updated.',
                'transfer_id' => $transfer->id,
                'new_status' => $transfer->status,
                'debug_info' => $debugInfo
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }
    
    /**
     * Remove a back order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                       
    /**
     * Get inventories based on source type and ID
     */
    public function getInventories(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:warehouse,facility',
            'source_id' => 'required|integer',
        ]);
        
        try {
            if ($request->source_type === 'warehouse') {
                // Get warehouse inventories directly with DB query
                $result = DB::table('inventories as i')
                    ->join('products as p', 'i.product_id', '=', 'p.id')
                    ->leftJoin('warehouses as w', 'i.warehouse_id', '=', 'w.id')
                    ->where('i.warehouse_id', $request->source_id)
                    ->where('i.quantity', '>', 0)
                    ->whereNotNull('p.id') // Ensure product exists
                    ->select([
                        'i.id',
                        'i.product_id',
                        'p.name',
                        'i.batch_number',
                        'i.quantity',
                        'i.expiry_date',
                        'i.warehouse_id',
                        'w.name as warehouse_name',
                        // Add JSON for product object
                        DB::raw("JSON_OBJECT('id', p.id, 'name', p.name) as product_json")
                    ])
                    ->get()
                    ->map(function($item) {
                        // Convert product_json to actual product array
                        $item->product = json_decode($item->product_json);
                        unset($item->product_json);
                        
                        // Add missing fields with default values
                        $item->uom = 'N/A'; // Default UoM since column doesn't exist
                        $item->barcode = ''; // Default barcode since column doesn't exist
                        $item->batch_number = $item->batch_number ?? '';
                        $item->warehouse_name = $item->warehouse_name ?? 'Unknown';
                        
                        return $item;
                    });
            } else {
                // Get facility inventories directly with DB query
                $result = DB::table('facility_inventories as fi')
                    ->join('products as p', 'fi.product_id', '=', 'p.id')
                    ->leftJoin('facilities as f', 'fi.facility_id', '=', 'f.id')
                    ->where('fi.facility_id', $request->source_id)
                    ->where('fi.quantity', '>', 0)
                    ->whereNotNull('p.id') // Ensure product exists
                    ->select([
                        'fi.id',
                        'fi.product_id',
                        'p.name',
                        'fi.batch_number',
                        'fi.quantity',
                        'fi.expiry_date',
                        'fi.facility_id',
                        'f.name as facility_name',
                        // Add JSON for product object
                        DB::raw("JSON_OBJECT('id', p.id, 'name', p.name) as product_json")
                    ])
                    ->get()
                    ->map(function($item) {
                        // Convert product_json to actual product array
                        $item->product = json_decode($item->product_json);
                        unset($item->product_json);
                        
                        // Add missing fields with default values
                        $item->uom = 'N/A'; // Default UoM since column doesn't exist
                        $item->barcode = ''; // Default barcode since column doesn't exist
                        $item->batch_number = $item->batch_number ?? '';
                        $item->facility_name = $item->facility_name ?? 'Unknown';
                        
                        return $item;
                    });
            }
            
            // Log the count of items found
            $count = $result->count();
            logger()->info("Found {$count} inventory items for {$request->source_type} ID: {$request->source_id}");
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            logger()->error('Error in getInventories: ' . $e->getMessage(), [
                'source_type' => $request->source_type,
                'source_id' => $request->source_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function receiveTransfer(Request $request)
    {
        try {
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'status' => 'required|in:received',
                'items' => 'required|array',
            ]);
            $transfer = Transfer::findOrFail($request->transfer_id);
            if(!$transfer || $transfer->status !== 'dispatched') {
                return response()->json('Transfer must be dispatched to be received', 500);
            }

            if($transfer->from_facility_id != auth()->user()->facility_id) {
                return response()->json('You are not authorized to receive this transfer', 500);
            }

            // Check if all items.quantity and items.received_quantity are equal
            foreach ($request->items as $item) {
                $areEqual = (int) $item['quantity'] == array_sum(array_column($item['backorders'], 'quantity')) + (int) $item['received_quantity'];
                logger()->info('Item ID: ' . $item['id'] . ', Equal check: ' . ($areEqual ? 'true' : 'false'));
                if(!$areEqual) {
                    return response()->json([
                        'id' => $item['id'],
                        'message' => 'There is mismatch in quantity'
                    ], 500);
                }
            }     
            
            logger()->info('Items: ' . json_encode($request->items));
            
            // Process inventory changes
            $this->processInventoryChanges($transfer, $request->items);
            
            // Update transfer status
            $transfer->status = 'received';
            $transfer->save();

            return response()->json(['message' => 'Transfer received successfully'], 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
    
    public function destroyItem($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $transferItem = TransferItem::with('transfer')->find($id);
                
                
                if (!in_array($transferItem->transfer->status, ['pending', 'draft']) || $transferItem->transfer->from_facility_id != auth()->user()->facility_id) {
                    return response()->json('You are not authorized to delete this transfer item', 500);
                }

                $inventory = FacilityInventory::where('product_id', $transferItem->product_id)->where('facility_id', $transferItem->transfer->from_facility_id)->first();
                if($inventory) {
                    $inventory->quantity += $transferItem->quantity;
                    $inventory->save();
                }else{
                    FacilityInventory::create([
                        'product_id' => $transferItem->product_id,
                        'facility_id' => $transferItem->transfer->from_facility_id,
                        'quantity' => $transferItem->quantity,
                        'batch_number' => $transferItem->batch_number,
                        'expiry_date' => $transferItem->expiry_date,
                        'barcode' => $transferItem->barcode,
                        'uom' => $transferItem->uom,
                    ]);
                }
            
                // Delete the transfer item
                $transferItem->transfer()->delete();
                $transferItem->backorders()->delete();
                $transferItem->delete();
                
                return response()->json('Transfer item deleted successfully', 200);
            });
        } catch (\Throwable $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
