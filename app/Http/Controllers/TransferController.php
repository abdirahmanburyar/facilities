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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
    
    public function receiveTransfer($id)
    {
        $transfer = Transfer::findOrFail($id);
        if (!in_array($transfer->status, ['transferred', 'delivered'])) {
            return redirect()->back()->with('error', 'Transfer must be transferred to be received');
        }
        $transfer->update(['status' => 'received']);
        return redirect()->back()->with('success', 'Transfer received successfully');
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
            'toWarehouse:id,name', 
            'fromFacility:id,name', 
            'toFacility:id,name'
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
        $facilities = Facility::where('id', auth()->user()->facility_id)->select('id','name')->get();
        
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
    public function destroyItem($id)
    {
        try {
            $transferItem = TransferItem::findOrFail($id);
            
            // Check if the transfer is in a state where items can be deleted
            $transfer = Transfer::findOrFail($transferItem->transfer_id);
            
            if (!in_array($transfer->status, ['pending', 'draft'])) {
                return response()->json([
                    'message' => 'Cannot delete items from a transfer that is not in pending or draft status'
                ], 403);
            }
            
            // Delete the transfer item
            $transferItem->delete();
            
            return response()->json([
                'message' => 'Transfer item deleted successfully'
            ]);
        } catch (\Exception $e) {
            logger()->error('Error deleting transfer item: ' . $e->getMessage(), [
                'item_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to delete transfer item: ' . $e->getMessage()
            ], 500);
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
}
