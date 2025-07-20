<?php

namespace App\Http\Controllers;

use App\Models\BackOrder;
use App\Models\PackingListDifference;
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
use App\Http\Resources\BackOrderHistoryResource;

class BackOrderController extends Controller
{
    public function index(Request $request){
        try {
            $backorders = BackOrder::whereHas('differences.inventoryAllocation', function($query) {
                $query->whereHas('order_item.order', function($orderQuery) {
                    $orderQuery->where('facility_id', auth()->user()->facility_id);
                })->orWhereHas('transfer_item.transfer', function($transferQuery) {
                    $transferQuery->where('to_facility_id', auth()->user()->facility_id);
                });
            });

            if($request->filled('search')){
                $backorders->where(function($query) use ($request) {
                    $query->where('back_order_number', 'like', '%'.$request->search.'%')
                        ->orWhereHas('differences.product', function($productQuery) use ($request) {
                            $productQuery->where('name', 'like', '%'.$request->search.'%')
                                ->orWhere('batch_number', 'like', '%'.$request->search.'%')
                                ->orWhere('barcode', 'like', '%'.$request->search.'%');
                        });
                });
            }

            if($request->filled('status')){
                $backorders->where('status', $request->status);
            }

            $backorders->with([
                'order',
                'transfer',
                'differences.product:id,name,productID',
                'differences.inventoryAllocation.order_item.order:id,order_number,order_type',
                'differences.inventoryAllocation.transfer_item.transfer:id,transferID,transfer_type',
                'creator:id,name',
                'updater:id,name'
            ]);

            $backorders = $backorders->paginate($request->filled('per_page', 25), ['*'], 'page', $request->filled('page', 1))
                ->withQueryString();
            $backorders->setPath(url()->current());

            return inertia('BackOrder/Index', [
                'history' => BackOrderResource::collection($backorders),
                'filters' => $request->only('search', 'per_page', 'page', 'status')
            ]);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            return redirect()->back()->with(['error' => $th->getMessage()]);
        }
    }

    public function manageBackOrder()
    {
        // Get orders with active back orders (those that have non-finalized differences)
        $orders = \App\Models\Order::where('facility_id', auth()->user()->facility_id)
            ->whereHas('backOrders', function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            })
            ->with(['backOrders' => function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            }])
            ->get()
            ->filter(function($order) {
                return $order->backOrders->count() > 0;
            });

        // Get transfers with active back orders (those that have non-finalized differences)
        $transfers = \App\Models\Transfer::where('to_facility_id', auth()->user()->facility_id)
            ->whereHas('backOrders', function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            })
            ->with(['backOrders' => function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            }])
            ->get(['id', 'transferID', 'transfer_type'])
            ->filter(function($transfer) {
                return $transfer->backOrders->count() > 0;
            });

        return inertia('BackOrder/BackOrder', [
            'orders' => $orders,
            'transfers' => $transfers
        ]);
    }

    public function getBackOrder(Request $request, $id)
    {
        try {
            // Extract type from the URL path
            $path = $request->path();
            $type = str_contains($path, '/order/') ? 'order' : 'transfer';
            
            $query = PackingListDifference::whereNull('finalized');
            
            if ($type === 'order') {
                $query->whereHas('inventoryAllocation', function($q) use ($id) {
                    $q->where('order_item_id', '!=', null)
                      ->whereHas('order_item', function($orderQ) use ($id) {
                          $orderQ->where('order_id', $id);
                      });
                });
            } elseif ($type === 'transfer') {
                $query->whereHas('inventoryAllocation', function($q) use ($id) {
                    $q->where('transfer_item_id', '!=', null)
                      ->whereHas('transfer_item', function($transferQ) use ($id) {
                          $transferQ->where('transfer_id', $id);
                      });
                });
            } else {
                return response()->json('Invalid type for BackOrderController@getBackOrder', 400);
            }
            
            $results = $query->with([
                'product:id,name,productID',
                'inventoryAllocation.order_item.order:id,order_number',
                'inventoryAllocation.transfer_item.transfer:id,transferID',
                'backOrder:id,back_order_number,back_order_date,status'
            ])->get();
            // Transform the data to include source information
            $results = $results->map(function($item) use ($type, $id) {
                $item->source_id = $id;
                $item->source_type = $type;
                $item->source = $type === 'order'
                    ? ($item->inventoryAllocation->order_item->order ?? null)
                    : ($item->inventoryAllocation->transfer_item->transfer ?? null);
                return $item;
            });
            return response()->json($results, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    // liquidate
    public function liquidate(Request $request){
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf', // Max 10MB per file
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list difference item
            $item = PackingListDifference::with([
                'inventoryAllocation',
                'product',
                'backOrder'
            ])->find($request->id);
            
            if (!$item) {
                throw new \Exception('Item not found');
            }
            
            // Generate note based on condition and source
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
                'product_id' => $item->product_id,
                'liquidated_by' => auth()->id(),
                'order_item_id' => $item->inventoryAllocation->order_item_id ?? null,
                'transfer_item_id' => $item->inventoryAllocation->transfer_item_id ?? null,
                'liquidated_at' => Carbon::now(),
                'quantity' => $request->quantity,
                'status' => 'pending', // Default status is pending
                'type' => $request->status, // Set type to the status from request
                'note' => $note,
                'barcode' => $item->inventoryAllocation->barcode ?? 'N/A',
                'expire_date' => $item->inventoryAllocation->expiry_date ?? 'N/A',
                'batch_number' => $item->inventoryAllocation->batch_number ?? 'N/A',
                'uom' => $item->inventoryAllocation->uom ?? 'N/A',
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
            ]);

            // Create a record in BackOrderHistory
            BackOrderHistory::create([
                'back_order_id' => $item->back_order_id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'status' => 'Liquidated',
                'note' => $note,
                'performed_by' => auth()->id(),
                'barcode' => $item->inventoryAllocation->barcode ?? 'N/A',
                'batch_number' => $item->inventoryAllocation->batch_number ?? 'N/A',
                'expiry_date' => $item->inventoryAllocation->expiry_date ?? now()->addYears(1)->toDateString(),
                'uom' => $item->inventoryAllocation->uom ?? 'N/A',
                'unit_cost' => $item->inventoryAllocation->unit_cost ?? 0,
                'total_cost' => ($item->inventoryAllocation->unit_cost ?? 0) * $request->quantity,
            ]);
            
            // Update the packing list difference
            // $item->decrement('quantity', $request->quantity);
            // if ($item->quantity <= 0) {
                $item->update([
                    'finalized' => "Liquidated"
                ]);
            // }

            // Update inventory allocation if exists
            // if ($item->inventoryAllocation) {
            //     $item->inventoryAllocation->decrement('allocated_quantity', $request->quantity);
            // }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json("Liquidated Successfully.", 200);
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
                'id' => 'required|exists:packing_list_differences,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB per file
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list difference item
            $item = PackingListDifference::with([
                'inventoryAllocation',
                'product',
                'backOrder'
            ])->find($request->id);
            
            if (!$item) {
                throw new \Exception('Item not found');
            }
            
            // Generate note based on condition and source
            $note = $request->note;
            
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
                'product_id' => $item->product_id,
                'back_order_id' => $item->back_order_id,
                'disposal_by' => auth()->id(),
                'disposed_at' => Carbon::now(),
                'order_item_id' => $item->inventoryAllocation->order_item_id ?? null,
                'transfer_item_id' => $item->inventoryAllocation->transfer_item_id ?? null,
                'quantity' => $request->quantity,
                'status' => 'pending', // Default status is pending
                'type' => $request->status, // Set type to the status from request
                'note' => $note,
                'barcode' => $item->inventoryAllocation->barcode ?? 'N/A',
                'expire_date' => $item->inventoryAllocation->expiry_date ?? 'N/A',
                'batch_number' => $item->inventoryAllocation->batch_number ?? 'N/A',
                'uom' => $item->inventoryAllocation->uom ?? 'N/A',
                'attachments' => !empty($attachments) ? json_encode($attachments) : null,
            ]);

            // Create a record in BackOrderHistory
            BackOrderHistory::create([
                'back_order_id' => $item->back_order_id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'status' => 'Disposed',
                'note' => $note,
                'performed_by' => auth()->id(),
                'barcode' => $item->inventoryAllocation->barcode ?? 'N/A',
                'batch_number' => $item->inventoryAllocation->batch_number ?? 'N/A',
                'expiry_date' => $item->inventoryAllocation->expiry_date ?? now()->addYears(1)->toDateString(),
                'uom' => $item->inventoryAllocation->uom ?? 'N/A',
                'unit_cost' => $item->inventoryAllocation->unit_cost ?? 0,
                'total_cost' => ($item->inventoryAllocation->unit_cost ?? 0) * $request->quantity,
            ]);
            
            // Update the packing list difference
            $item->decrement('quantity', $request->quantity);
            if ($item->quantity <= 0) {
                $item->update([
                    'finalized' => "Disposed"
                ]);
            }

            // Update inventory allocation if exists
            if ($item->inventoryAllocation) {
                $item->inventoryAllocation->decrement('allocated_quantity', $request->quantity);
            }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json("Disposed Successfully.", 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    // received
    public function received(Request $request) {
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'back_order_id' => 'required|exists:back_orders,id',
                'product_id' => 'required|exists:products,id',
                'source_id' => 'required',
                'source_type' => 'required|in:order,transfer',
                'quantity' => 'required|integer|min:1',
                'original_quantity' => 'required|integer|min:1',
                'status' => 'required|string',
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list difference item
            $item = PackingListDifference::with([
                'inventoryAllocation',
                'product',
                'backOrder'
            ])->find($request->id);
            
            if (!$item) {
                throw new \Exception('Item not found');
            }
            
            // Get inventory allocation details
            $inventoryAllocation = $item->inventoryAllocation;
            $unitCost = $inventoryAllocation && $inventoryAllocation->unit_cost ? (float) $inventoryAllocation->unit_cost : 0.0;
            $totalCost = (float) ($unitCost * $request->quantity);
            
            // Ensure total_cost is never null
            if ($totalCost === null || is_nan($totalCost)) {
                $totalCost = 0.0;
            }
            
            // Determine type based on inventory allocation
            $type = null;
            if ($inventoryAllocation) {
                if ($inventoryAllocation->transfer_item_id !== null) {
                    $type = "Transfer";
                } elseif ($inventoryAllocation->order_item_id !== null) {
                    $type = "Order";
                }
            }
            
            // Create a record in BackOrderHistory with inventory details
            $backOrderHistoryData = [
                'back_order_id' => $item->back_order_id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'status' => 'Received',
                'note' => "Received {$request->quantity} items by " . auth()->user()->name,
                'performed_by' => auth()->id(),
                'unit_cost' => $unitCost,
            ];
            
            // Explicitly set total_cost after array creation
            $backOrderHistoryData['total_cost'] = $totalCost;
            
            // Set order_item_id or transfer_item_id based on type
            if ($type === "Order" && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                $backOrderHistoryData['order_item_id'] = $inventoryAllocation->order_item_id;
            } elseif ($type === "Transfer" && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                $backOrderHistoryData['transfer_item_id'] = $inventoryAllocation->transfer_item_id;
            }            
            
            // Add inventory allocation details if available
            if ($inventoryAllocation) {
                $backOrderHistoryData['batch_number'] = $inventoryAllocation->batch_number ?? 'N/A';
                $backOrderHistoryData['barcode'] = $inventoryAllocation->barcode ?? 'N/A';
                $backOrderHistoryData['expiry_date'] = $inventoryAllocation->expiry_date ?? now()->addYears(1)->toDateString();
                $backOrderHistoryData['uom'] = $inventoryAllocation->uom ?? 'N/A';
            } else {
                // Set default values if no inventory allocation
                $backOrderHistoryData['batch_number'] = 'N/A';
                $backOrderHistoryData['barcode'] = 'N/A';
                $backOrderHistoryData['expiry_date'] = now()->addYears(1)->toDateString();
                $backOrderHistoryData['uom'] = 'N/A';
            }
            
            BackOrderHistory::create($backOrderHistoryData);
            
            // Update the packing list difference
            $item->decrement('quantity', $request->quantity);
            // Update inventory allocation if exists
            if ($item->inventoryAllocation) {
                $item->inventoryAllocation->increment('received_quantity', $request->quantity);
            }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json("Received Successfully.", 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function testBackOrderRoute()
    {
        return response()->json(['message' => 'BackOrder route test successful']);
    }

    public function receiveBackOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'back_order_id' => 'required|exists:back_orders,id',
                'product_id' => 'required|exists:products,id',
                'source_id' => 'required',
                'source_type' => 'required|in:order,transfer',
                'quantity' => 'required|integer|min:1',
                'original_quantity' => 'required|integer|min:1',
                'status' => 'required|string',
            ]);

            return DB::transaction(function () use ($request) {
                $packingListDiff = PackingListDifference::with('inventoryAllocation')->find($request->id);
                
                // Calculate remaining quantity
                $receivedQuantity = $request->quantity;
                $originalQuantity = $request->original_quantity;
                $remainingQuantity = $originalQuantity - $receivedQuantity;

                // Get inventory allocation details
                $inventoryAllocation = $packingListDiff ? $packingListDiff->inventoryAllocation : null;
                $unitCost = $inventoryAllocation && $inventoryAllocation->unit_cost ? (float) $inventoryAllocation->unit_cost : 0.0;
                $totalCost = (float) ($unitCost * $receivedQuantity);
                
                // Ensure total_cost is never null
                if ($totalCost === null || is_nan($totalCost)) {
                    $totalCost = 0.0;
                }
                
                // Determine type based on inventory allocation
                $type = null;
                if ($inventoryAllocation) {
                    if ($inventoryAllocation->transfer_item_id !== null) {
                        $type = "Transfer";
                    } elseif ($inventoryAllocation->order_item_id !== null) {
                        $type = "Order";
                    }
                }
                
                // Create BackOrderHistory record with all inventory details
                $backOrderHistoryData = [
                    'packing_list_id' => null,
                    'product_id' => $request->product_id,
                    'quantity' => $receivedQuantity,
                    'status' => 'Received',
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                    'performed_by' => auth()->user()->id,
                    'back_order_id' => $request->back_order_id,
                    'unit_cost' => $unitCost,
                ];
                
                // Explicitly set total_cost after array creation
                $backOrderHistoryData['total_cost'] = $totalCost;
                
                // Set order_item_id or transfer_item_id based on type from inventory allocation
                if ($type === "Order" && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                    $backOrderHistoryData['order_item_id'] = $inventoryAllocation->order_item_id;
                } elseif ($type === "Transfer" && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                    $backOrderHistoryData['transfer_item_id'] = $inventoryAllocation->transfer_item_id;
                }
                
                // Add inventory allocation details if available
                if ($inventoryAllocation) {
                    $backOrderHistoryData['batch_number'] = $inventoryAllocation->batch_number ?? 'N/A';
                    $backOrderHistoryData['barcode'] = $inventoryAllocation->barcode ?? 'N/A';
                    $backOrderHistoryData['expiry_date'] = $inventoryAllocation->expiry_date ?? now()->addYears(1)->toDateString();
                    $backOrderHistoryData['uom'] = $inventoryAllocation->uom ?? 'N/A';
                } else {
                    // Set default values if no inventory allocation
                    $backOrderHistoryData['batch_number'] = 'N/A';
                    $backOrderHistoryData['barcode'] = 'N/A';
                    $backOrderHistoryData['expiry_date'] = now()->addYears(1)->toDateString();
                    $backOrderHistoryData['uom'] = 'N/A';
                }
                
                
                $backOrderHistory = BackOrderHistory::create($backOrderHistoryData);

                // Create ReceivedBackOrder record with inventory allocation details
                $receivedBackOrderData = [
                    'product_id' => $request->product_id,
                    'received_by' => auth()->user()->id,
                    'quantity' => $receivedQuantity,
                    'status' => 'pending',
                    'type' => $type ?? $request->status, // Use type from inventory allocation, fallback to request status
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                    'received_at' => now()->toDateString(),
                    'back_order_id' => $request->back_order_id,
                ];
                
                // Set order_id or transfer_id based on type from inventory allocation
                if ($type === "Order" && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                    // Get the order_id from the order_item
                    $orderItem = \App\Models\OrderItem::find($inventoryAllocation->order_item_id);
                    if ($orderItem) {
                        $receivedBackOrderData['order_id'] = $orderItem->order_id;
                    }
                } elseif ($type === "Transfer" && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                    // Get the transfer_id from the transfer_item
                    $transferItem = \App\Models\TransferItem::find($inventoryAllocation->transfer_item_id);
                    if ($transferItem) {
                        $receivedBackOrderData['transfer_id'] = $transferItem->transfer_id;
                    }
                }
                
                // Set facility information based on type from inventory allocation
                if ($type === 'Order' && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                    // Get order facility details
                    $orderItem = \App\Models\OrderItem::find($inventoryAllocation->order_item_id);
                    if ($orderItem && $orderItem->order && $orderItem->order->facility) {
                        $receivedBackOrderData['facility_id'] = $orderItem->order->facility->id;
                        $receivedBackOrderData['facility'] = $orderItem->order->facility->name;
                    }
                } elseif ($type === 'Transfer' && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                    // Get transfer facility details
                    $transferItem = \App\Models\TransferItem::find($inventoryAllocation->transfer_item_id);
                    if ($transferItem && $transferItem->transfer) {
                        $receivedBackOrderData['facility_id'] = $transferItem->transfer->to_facility_id;
                        // Get facility name
                        $toFacility = \App\Models\Facility::find($transferItem->transfer->to_facility_id);
                        if ($toFacility) {
                            $receivedBackOrderData['facility'] = $toFacility->name;
                        }
                    }
                }
                
                // Add inventory allocation details if available
                if ($packingListDiff && $packingListDiff->inventoryAllocation) {
                    $receivedBackOrderData['barcode'] = $packingListDiff->inventoryAllocation->barcode ?? 'N/A';
                    $receivedBackOrderData['batch_number'] = $packingListDiff->inventoryAllocation->batch_number ?? 'N/A';
                    $receivedBackOrderData['expire_date'] = $packingListDiff->inventoryAllocation->expiry_date ?? null;
                    $receivedBackOrderData['uom'] = $packingListDiff->inventoryAllocation->uom ?? 'N/A';
                    $receivedBackOrderData['unit_cost'] = $packingListDiff->inventoryAllocation->unit_cost ?? 0;
                    $receivedBackOrderData['total_cost'] = ($packingListDiff->inventoryAllocation->unit_cost ?? 0) * $receivedQuantity;
                } else {
                    // Set default values if no inventory allocation
                    $receivedBackOrderData['barcode'] = 'N/A';
                    $receivedBackOrderData['batch_number'] = 'N/A';
                    $receivedBackOrderData['expire_date'] = null;
                    $receivedBackOrderData['uom'] = 'N/A';
                    $receivedBackOrderData['unit_cost'] = 0;
                    $receivedBackOrderData['total_cost'] = 0;
                }
                
                // Generate the received_backorder_number manually to ensure it's set
                $receivedBackOrderData['received_backorder_number'] = \App\Models\ReceivedBackOrder::generateReceivedBackorderNumber();
                
                $receivedBackOrder = \App\Models\ReceivedBackOrder::create($receivedBackOrderData);

                // Create ReceivedBackorderItem record
                $receivedBackorderItemData = [
                    'received_backorder_id' => $receivedBackOrder->id,
                    'product_id' => $request->product_id,
                    'quantity' => $receivedQuantity,
                    'unit_cost' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->unit_cost ?? 0 : 0,
                    'total_cost' => ($packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->unit_cost ?? 0 : 0) * $receivedQuantity,
                    'barcode' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->barcode ?? 'N/A' : 'N/A',
                    'expire_date' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->expiry_date ?? null : null,
                    'batch_number' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->batch_number ?? 'N/A' : 'N/A',
                    'warehouse_id' => null, // Set to null for facilities
                    'uom' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->uom ?? 'N/A' : 'N/A',
                    'location' => null, // Set to null for facilities
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                ];

                \App\Models\ReceivedBackorderItem::create($receivedBackorderItemData);

                // Handle the packing list difference record
                // if ($remainingQuantity <= 0) {
                //     $packingListDiff->delete();
                // } else {
                //     $packingListDiff->quantity = $remainingQuantity;
                //     $packingListDiff->save();
                // }

                return response()->json([
                    'message' => "Successfully received {$receivedQuantity} items" . ($remainingQuantity > 0 ? ", {$remainingQuantity} items remaining" : ""),
                    'received_quantity' => $receivedQuantity,
                    'remaining_quantity' => $remainingQuantity,
                ], 200);

            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function showBackOrder(Request $request)
    {
        $query = BackOrder::query();

        // Only show back orders for the current user's facility
        $query->whereHas('order', function($q) {
            $q->where('facility_id', auth()->user()->facility_id);
        });

        if($request->filled('search')){
            $query->whereHas('order', function($q) use ($request){
                $q->where('order_number', 'like', '%' . $request->search . '%');
            })
            ->orWhere('back_order_number', 'like', '%' . $request->search . '%');
        }
        if($request->filled('status')){
            $query->where('status', $request->status);
        }
        
        // with
        $query = $query->with('order.facility')->latest();
        $history = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
            ->withQueryString();
        $history->setPath(url()->current());

        return inertia('BackOrder/Index', [
            'history' => BackOrderHistoryResource::collection($history),
            'filters' => $request->only('search', 'per_page', 'status')
        ]);
    }

    public function getBackOrderHistories($backOrderId)
    {
        try {
            $histories = BackOrderHistory::with(['product.dosage','product.category', 'performer'])
            ->where('back_order_id', $backOrderId)
            ->get();
        return response()->json($histories, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function uploadBackOrderAttachment(Request $request, $backOrderId)
    {
        try {
            \Log::info('Upload attachment called', [
                'backOrderId' => $backOrderId,
                'files_count' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
                'request_data' => $request->all()
            ]);
            
            $request->validate([
                'attachments' => 'required|array',
                'attachments.*' => 'file|mimes:pdf|max:10240', // 10MB max per file
            ]);

            $backOrder = BackOrder::findOrFail($backOrderId);
            \Log::info('BackOrder found', ['backOrder' => $backOrder->toArray()]);
            
            $attachments = [];

            foreach ($request->file('attachments') as $file) {
                $fileName = 'backorder_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Get file info BEFORE moving
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                $filePath = public_path('attachments/backorders/' . $fileName);
                $file->move(public_path('attachments/backorders'), $fileName);
                
                \Log::info('File uploaded', [
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $fileName,
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath)
                ]);
                
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => '/attachments/backorders/' . $fileName,
                    'type' => $mimeType,
                    'size' => $size,
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }

            // Merge with existing attachments
            $existingAttachments = $backOrder->attach_documents ?? [];
            $allAttachments = array_merge($existingAttachments, $attachments);
            
            \Log::info('Updating back order attachments', [
                'existing_count' => count($existingAttachments),
                'new_count' => count($attachments),
                'total_count' => count($allAttachments)
            ]);
            
            $backOrder->update(['attach_documents' => $allAttachments]);

            return response()->json(['message' => 'Attachments uploaded successfully', 'files' => $allAttachments], 200);
        } catch (\Throwable $th) {
            \Log::error('Upload attachment error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return response()->json($th->getMessage(), 500);
        }
    }

    public function deleteBackOrderAttachment(Request $request, $backOrderId)
    {
        try {
            \Log::info('Delete attachment called', [
                'backOrderId' => $backOrderId,
                'file_path' => $request->file_path,
                'request_data' => $request->all()
            ]);
            
            $request->validate([
                'file_path' => 'required|string'
            ]);

            $backOrder = BackOrder::findOrFail($backOrderId);
            $attachments = $backOrder->attach_documents ?? [];
            
            \Log::info('Current attachments', ['attachments' => $attachments]);

            // Remove the specified attachment
            $attachments = array_filter($attachments, function($attachment) use ($request) {
                return $attachment['path'] !== $request->file_path;
            });

            $backOrder->update(['attach_documents' => array_values($attachments)]);

            // Delete the physical file
            $filePath = public_path($request->file_path);
            \Log::info('Deleting physical file', [
                'file_path' => $filePath,
                'file_exists' => file_exists($filePath)
            ]);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return response()->json(['message' => 'Attachment deleted successfully', 'files' => array_values($attachments)], 200);
        } catch (\Throwable $th) {
            \Log::error('Delete attachment error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return response()->json($th->getMessage(), 500);
        }
    }
}
