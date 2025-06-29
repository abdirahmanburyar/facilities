<?php

namespace App\Http\Controllers;

use App\Models\FacilityBackorder;
use App\Http\Resources\BackOrderResource;
use Carbon\Carbon;
use App\Models\BackOrderHistory;
use App\Models\FacilityInventory;
use App\Models\Liquidate;
use App\Models\FacilityInventoryItem;
use Illuminate\Http\Request;

use App\Models\Disposal;
use Illuminate\Support\Facades\DB;
use App\Services\FacilityInventoryMovementService;

class BackOrderController extends Controller
{
    public function index(Request $request){
        try {
            $backorders = FacilityBackorder::whereHas('transferItem.transfer', function($query) {
                $query->where('to_facility_id', auth()->user()->facility_id);
            })
            ->orWhereHas('orderItem.order', function($query) {
                $query->where('facility_id', auth()->user()->facility_id);
            });
            if($request->filled('search')){
                $backorders->whereHas('product', function($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('batch_number', 'like', '%'.$request->search.'%')
                        ->orWhere('barcode', 'like', '%'.$request->search.'%');
                });
            }

            if($request->filled('status')){
                $backorders->where('status', $request->status);
            }

            $backorders->with('product:id,name,productID','inventoryAllocation','orderItem.order:id,order_number,order_type','transferItem.transfer');

            $backorders = $backorders->paginate($request->filled('per_page', 2), ['*'], 'page', $request->filled('page', 1))
                ->withQueryString();
            $backorders->setPath(url()->current());

            return inertia('BackOrder/Index', [
                'backorders' => BackOrderResource::collection($backorders),
                'filters' => $request->only('search', 'per_page', 'page', 'status')
            ]);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            return redirect()->back()->with(['error' => $th->getMessage()]);
        }
    }

    // liquidate
    public function liquidate(Request $request){
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:facility_backorders,id',
                'quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf', // Max 10MB per file
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list to include its number in the note
            $item = FacilityBackorder::with('inventoryAllocation','orderItem.order:id,order_number,order_type','transferItem.transfer:id,transferID,transfer_type')
                ->find($request->id);
            
            // Generate note based on condition and source
            // $note = $item ? $item->orderItem->order->order_number .' - '. $item->orderItem->order->order_type .' - '. $item->transferItem->transfer->transferID .' - '. $request->note : 'Unknown';
            $note = $request->note;
            
            // Handle file attachments if any
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $fileName = 'liquidate_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('attachments/liquidations'), $fileName);
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => '/attachments/liquidations/' . $fileName,
                        'type' => $file->getClientMimeType(),
                        'size' => filesize(public_path('attachments/liquidations/' . $fileName)),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
            }

            $liquidate = Liquidate::create([
                'product_id' => $item->inventoryAllocation->product_id,
                'liquidated_by' => auth()->id(),
                'order_item_id' => $item->orderItem->id ?? null,
                'transfer_item_id' => $item->transferItem->id ?? null,
                'liquidated_at' => Carbon::now(),
                'quantity' => $request->quantity,
                'status' => 'pending', // Default status is pending
                'note' => $note,
                'barcode' => $item->inventoryAllocation->barcode,
                'expire_date' => $item->inventoryAllocation->expiry_date,
                'batch_number' => $item->inventoryAllocation->batch_number,
                'uom' => $item->inventoryAllocation->uom,
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
            ]);

            if ($item) {
                // Create a record in BackOrderHistory before deleting
                BackOrderHistory::create([
                    'order_id' => $item->order_id ?? null,
                    'transfer_id' => $item->transfer_id ?? null,
                    'product_id' => $item->inventoryAllocation->product_id,
                    'quantity' => $request->quantity,
                    'status' => 'Liquidated',
                    'note' => $note,
                    'performed_by' => auth()->id()
                ]);
                
                // Delete the record
                $item->inventoryAllocation->decrement('allocated_quantity', $request->quantity);
                $item->decrement('quantity', $request->quantity);
                if ($item->quantity == 0) {
                    $item->update([
                        'finalized' => "Liquidated"
                    ]);
                }
            }
            // Commit the transaction
            DB::commit();
            
            return response()->json("Liquidated Succesfully.", 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    // dispose
    public function dispose(Request $request){
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:facility_backorders,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB per file
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list to include its number in the note
            $item = FacilityBackorder::with('inventoryAllocation','transferItem.transfer','orderItem.order:id,order_number,order_type')->find($request->id);
            
            // Generate note based on condition and source
            if($item->transferItem){
                $note = $item->transferItem->transfer->transferID .' - '. $item->type .' - '. $request->note;
            }else{
                $note = $item->orderItem->order->order_number .' - '. $item->orderItem->order->order_type .' - '. $item->type .' - '. $request->note;
            }
            
            // Handle file attachments if any
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $fileName = 'dispose_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('attachments/disposals'), $fileName);
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => '/attachments/disposals/' . $fileName,
                        'type' => $file->getClientMimeType(),
                        'size' => filesize(public_path('attachments/disposals/' . $fileName)),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
            }

            $dispose = Disposal::create([
                'product_id' => $item->inventoryAllocation->product_id ?? $item->transferItem->product_id,
                'disposal_by' => auth()->id(),
                'disposed_at' => Carbon::now(),
                'order_item_id' => $item->orderItem->id ?? null,
                'transfer_item_id' => $item->transferItem->id ?? null,
                'quantity' => $request->quantity,
                'status' => 'pending', // Default status is pending
                'note' => $note,
                'barcode' => $item->inventoryAllocation->barcode ?? $item->transferItem->barcode ?? 'N/A',
                'expire_date' => $item->inventoryAllocation->expiry_date ?? $item->transferItem->expiry_date ?? 'N/A',
                'batch_number' => $item->inventoryAllocation->batch_number ?? $item->transferItem->batch_number ?? 'N/A',
                'uom' => $item->inventoryAllocation->uom ?? $item->transferItem->uom ?? 'N/A',
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
            ]);

            if ($item) {
                // Create a record in BackOrderHistory before deleting
                BackOrderHistory::create([
                    'order_id' => $item->orderItem->order_id ?? null,
                    'transfer_id' => $item->transferItem->id ?? null,
                    'product_id' => $item->inventoryAllocation->product_id ?? $item->transferItem->product_id,
                    'quantity' => $request->quantity,
                    'status' => 'Disposal',
                    'note' => $note,
                    'performed_by' => auth()->id()
                ]);
                
                // Delete the record
                // $item->inventoryAllocation->decrement('allocated_quantity', $request->quantity);
                // $item->decrement('quantity', $request->quantity);
                // if ($item->quantity == 0) {
                // }
                $item->update([
                    'finalized' => "Disposed"
                ]);
            }

            // Commit the transaction
            DB::commit();
            
            return response()->json("Disposed Succesfully.", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    // received
    public function received(Request $request) {
        try {
            return DB::transaction(function () use ($request) {
                $request->validate([
                    'id' => 'required|exists:facility_backorders,id',
                    'quantity' => 'required|integer|min:1',
                ]);
    
                $facilityInventoryMovementService = new FacilityInventoryMovementService();
    
                $item = FacilityBackorder::with([
                    'inventoryAllocation',
                    'orderItem.order:id,order_number,order_type,facility_id',
                    'transferItem.transfer:id,from_facility_id,to_facility_id'
                ])->find($request->id);
    
                if (!$item) {
                    return response()->json('Backorder item not found.', 404);
                }
    
                // Determine source of data
                $facilityId   = $item->orderItem->order->facility_id ?? $item->transferItem->transfer->to_facility_id;
                $productId    = $item->inventoryAllocation->product_id ?? $item->transferItem->product_id;
                $batchNumber  = $item->inventoryAllocation->batch_number ?? $item->transferItem->batch_number;
                $expiryDate   = $item->inventoryAllocation->expiry_date ?? $item->transferItem->expiry_date;
                $barcode      = $item->inventoryAllocation->barcode ?? $item->transferItem->barcode;
                $uom          = $item->inventoryAllocation->uom ?? $item->transferItem->uom;
    
                // Ensure main inventory record exists
                $inventory = FacilityInventory::firstOrCreate([
                    'product_id' => $productId,
                    'facility_id' => $facilityId,
                ]);
    
                // Check if inventory item exists with the same batch/barcode/expiry
                $inventoryItem = FacilityInventoryItem::where('facility_inventory_id', $inventory->id)
                    ->where('batch_number', $batchNumber)
                    ->where('expiry_date', $expiryDate)
                    ->where('barcode', $barcode)
                    ->first();
    
                if ($inventoryItem) {
                    $inventoryItem->increment('quantity', $request->quantity);
                } else {
                    $inventoryItem = FacilityInventoryItem::create([
                        'facility_inventory_id' => $inventory->id,
                        'product_id'            => $productId,
                        'quantity'              => $request->quantity,
                        'batch_number'          => $batchNumber,
                        'expiry_date'           => $expiryDate,
                        'barcode'               => $barcode,
                        'uom'                   => $uom,
                        'unit_cost'             => null, // Optional, fill if available
                        'total_cost'            => null,
                    ]);
                }
    
                // Sync the main inventory's quantity from its items
                $inventory->quantity = $inventory->items()->sum('quantity');
                $inventory->save();
    
                // Record movement
                if ($item->transferItem) {
                    $facilityInventoryMovementService->recordTransferReceived(
                        $item->transferItem->transfer,
                        $item->transferItem,
                        $facilityId,
                        $request->quantity
                    );
                } elseif ($item->orderItem) {
                    $facilityInventoryMovementService->recordOrderReceived(
                        $item->orderItem->order,
                        $item->orderItem,
                        $facilityId,
                        $request->quantity,
                        $batchNumber,
                        $expiryDate,
                        $barcode,
                        $uom
                    );
                }
    
                // Record in backorder history
                BackOrderHistory::create([
                    'order_id'     => $item->orderItem->order_id ?? null,
                    'transfer_id'  => $item->transferItem->id ?? null,
                    'product_id'   => $productId,
                    'quantity'     => $request->quantity,
                    'status'       => 'Received',
                    'note'         => "From the backorder",
                    'performed_by' => auth()->id()
                ]);
    
                // Update received quantity
                if ($item->transferItem) {
                    $item->transferItem->increment('received_quantity', $request->quantity);
                } else {
                    $item->orderItem->increment('received_quantity', $request->quantity);
                }
    
                $item->decrement('quantity', $request->quantity);
                $item->refresh();
    
                if ($item->quantity == 0) {
                    $item->delete();
                }
    
                return response()->json("Received successfully.", 200);
            });
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }
    
}
