<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Facades\Kafka;
use App\Models\Product;
use App\Models\Warehouse;
use App\Events\OrderEvent;
use App\Models\EligibleItem;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\OrderItem;
use App\Models\FacilityInventory;
use App\Models\Inventory;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $facility = auth()->user()->facility;
        $order = Order::where('facility_id', $facility->id)
            ->where('id', $request->id)
            ->with('user', 'items.product', 'items')
            ->first();

        $orders = Order::where('facility_id', $facility->id)
            // ->where('status', 'pending')
            ->get();

            $stats = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('orders.status as status', DB::raw('count(*) as count'))
            ->where('orders.facility_id', $facility->id)
            ->where('orders.id', $request->id)
            // ->groupBy('orders.status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            })
            ->toArray();

        // Ensure all statuses have a value
        $defaultStats = [
            'pending' => 0,
            'approved' => 0,
            'in_process' => 0,
            'dispatched' => 0,
            'delivery_pending' => 0,
            'delivered' => 0
        ];

        // $stats = array_merge($defaultStats, $stats);
            
        return inertia('Order/Index', [
            'stats' => $stats,
            'orders' => $orders,
            'currentOrder' => $order,
            'products' => Product::whereHas('eligibleItems' , function($q) use($facility) {
                $q->where('facility_type', $facility->facility_type);
            })->get(),
            'filters' => $request->only('id')
        ]);
    }

    public function createOrder(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $request->validate([
                    'order_type' => 'required',
                    'expected_date' => 'required'
                ]);
                $orderNumber = 'OR-' . Carbon::now()->format('Ymd') . '-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'user_id' => auth()->user()->id,
                    'facility_id' => auth()->user()->facility_id,
                    'order_type' => $request->order_type,
                    'expected_date' => $request->expected_date,
                    'status' => 'pending',
                    'order_number' => $orderNumber,
                    'order_date' => Carbon::now()->toDateString(),
                    'expected_date' => Carbon::now()->addDays(7), // Default 7 days expectation
                ]);

                // Dispatch order event if needed
                event(new OrderEvent("refreshed"));

                return response()->json(["message" => "Order created successfully", 'order' => $order], 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            DB::beginTransaction();
            $order = Order::findOrFail($request->id);
            $allowedStatuses = [
                'pending' => ['approved', 'rejected'],
                'approved' => ['in processing'],
                'in processing' => ['dispatched'],
                'dispatched' => ['delivered']
            ];

            // Check if the current status can transition to the requested status
            if (!isset($allowedStatuses[$order->status]) || !in_array($request->status, $allowedStatuses[$order->status])) {
                return response()->json("Order cannot be changed from {$order->status} to {$request->status}", 500);
            }

            $order->update([
                'status' => $request->status,
            ]);

            
            Kafka::publishOrderPlaced("Refreshed");
            event(new OrderEvent('Refreshed'));

            DB::commit();
            return response()->json('Order status updated successfully.', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $request->validate([
                    'id' => 'nullable|exists:order_items,id',
                    'product_id' => 'required|exists:products,id',
                    'quantity' => 'required',
                    'quantity_on_order' => 'required',
                    'order_id' => 'required'
                ]);

                // Check for duplicate product in same order, excluding current item if updating
                $query = OrderItem::where('product_id', $request->product_id)
                    ->where('order_id', $request->order_id);

                if ($request->id) {
                    $query->where('id', '!=', $request->id);
                }

                if ($query->exists()) {
                    return response()->json(Product::find($request->product_id)->name . ' already exists in this order.', 500);
                }

                OrderItem::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'product_id' => $request->product_id,
                        'quantity' => $request->quantity,
                        'quantity_on_order' => $request->quantity_on_order,
                        'order_id' => $request->order_id,
                        'user_id' => auth()->user()->id
                    ]
                );

                event(new OrderEvent("refreshed"));
                return response()->json("Order item " . ($request->id ? "updated" : "added") . " successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $request->validate([
                'barcode' => 'required',
            ]);

            $barcode = $request->barcode;
            $facility = auth()->user()->facility;

            // Find the product by barcode or name, but only if it's eligible for this facility
            $product = EligibleItem::where('facility_type', $facility->facility_type)
                ->whereHas('product', function ($query) use ($barcode) {
                    $query->where('barcode', 'like', '%' . $barcode . '%')
                        ->orWhere('name', 'like', '%' . $barcode . '%');
                })
                ->with('product:id,name,barcode')
                ->latest()
                ->first();
            logger()->info($product);
            if (!$product) {
                return response()->json(['message' => 'Product not found', 'product' => null], 500);
            }

            // Get Stock on Hand (SOH) from facility inventories
            $inventory = DB::table('facility_inventories')
                ->where('product_id', $product->product->id)
                ->where('facility_id', $facility->id)
                ->first();
            $stockOnHand = $inventory ? $inventory->quantity : 0;

            // Calculate days from the latest order and remaining days...
            $latestOrder = DB::table('orders')
                ->where('facility_id', $facility->id)
                ->where('order_date', '>=', Carbon::now()->startOfQuarter()->toDateString())
                ->where('order_date', '<=', Carbon::now()->endOfQuarter()->toDateString())
                ->orderBy('order_date', 'desc')
                ->first();

            $daysFromLatestOrder = 0;
            if ($latestOrder) {
                $orderDate = Carbon::parse($latestOrder->order_date)->toDateString();
                $currentDate = Carbon::now()->toDateString();
                $daysFromLatestOrder = Carbon::parse($currentDate)->diffInDays(Carbon::parse($orderDate));
            }

            $daysRemaining = 120 - $daysFromLatestOrder;
            $quantityOnOrder = 0;
            $amc = 200;
            $orderQuantity = max(0, ceil($amc * ($daysRemaining / 30) - $stockOnHand - $quantityOnOrder));

            return response()->json([
                "product" => [
                    "id" => $product->product->id,
                    "name" => $product->product->name,
                    "barcode" => $product->product->barcode,
                    "suggested_quantity" => $orderQuantity,
                    'stock_on_hand' => $stockOnHand
                ],
                "message" => "success"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function remove(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $orderItem = OrderItem::findOrFail($request->id);
                if ($orderItem->order->status !== 'pending') {
                    return response()->json('Order item cannot be removed', 409);
                }
                $orderItem->delete();
                Kafka::publishOrderPlaced("Refreshed");
                event(new OrderEvent("refreshed"));
                return response()->json("Order item removed successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function submitOrder(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                event(new OrderEvent("refreshed"));
                return response()->json("Order submitted successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function receivedItems(Request $request){
        try {
            $request->validate([
                'id' => 'required',
                'lost_quantity' => 'required',
                'damaged_quantity' => 'required',
                'status' => 'required'
            ]);
            
            $orderItem = OrderItem::where('id', $request->id)->first();

            if (!$orderItem) {
                return response()->json("Order item not found", 500);
            }

            $items = (int) $orderItem->quantity - (int) $request->lost_quantity - (int) $request->damaged_quantity;
            $remainingItems = $items;

            $status = ($request->lost_quantity == 0 && $request->damaged_quantity == 0) 
                ? 'delivered' 
                : 'delivery_pending';

            logger()->info($status);
            // return response()->json("don", 200);

            $orderItem->lost_quantity = $request->lost_quantity;
            $orderItem->damaged_quantity = $request->damaged_quantity;
            $orderItem->status = $status;
            $orderItem->save();

            // Get all inventory records for this product, ordered by expiry date
            $inventories = Inventory::where('product_id', $orderItem->product_id)
                ->where('warehouse_id', $orderItem->warehouse_id)
                ->orderBy('expiry_date', 'asc')
                ->get();

            if ($inventories->count() > 0) {
                foreach ($inventories as $inventory) {
                    if ($remainingItems <= 0) break;

                    // Get or create facility inventory for this batch
                    $facilityInventory = FacilityInventory::firstOrCreate(
                        [
                            'facility_id' => $orderItem->order->facility_id,
                            'product_id' => $orderItem->product_id,
                            'batch_number' => $inventory->batch_number
                        ],
                        [
                            'quantity' => 0,
                            'expiry_date' => $inventory->expiry_date
                        ]
                    );

                    // Calculate how much to add to this batch
                    $quantityToAdd = min($remainingItems, $inventory->quantity);
                    
                    if ($quantityToAdd > 0) {
                        // Add to facility inventory
                        $facilityInventory->increment('quantity', $quantityToAdd);
                        // Remove from main inventory
                        $inventory->decrement('quantity', $quantityToAdd);
                        $remainingItems -= $quantityToAdd;
                    }
                }

                // If there are still remaining items, add them to the last batch
                if ($remainingItems > 0 && $inventories->count() > 0) {
                    $lastInventory = $inventories->last();
                    $facilityInventory = FacilityInventory::where('facility_id', $orderItem->order->facility_id)
                        ->where('product_id', $orderItem->product_id)
                        ->where('batch_number', $lastInventory->batch_number)
                        ->first();

                    if ($facilityInventory) {
                        $facilityInventory->increment('quantity', $remainingItems);
                        $lastInventory->decrement('quantity', $remainingItems);
                    }
                }
            } else {
                throw new \Exception("No inventory records found for this product");
            }

            // Remove inventory record if quantity is 0
            $zeroQuantityInventories = Inventory::where('product_id', $orderItem->product_id)
            ->where('warehouse_id', $orderItem->warehouse_id)
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
                Inventory::where('product_id', $orderItem->product_id)
                    ->where('warehouse_id', $orderItem->warehouse_id)
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

            Kafka::publishOrderUpdated("Refreshed");
            event(new OrderEvent("refreshed"));
            return response()->json("done", 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function updateItem(Request $request){
        try {
            $request->validate([
                'id' => 'required',
                'quantity' => 'required'
            ]);
            OrderItem::where('id', $request->id)
                ->update([
                    'quantity' => $request->quantity,
                    'quantity_on_order' => $request->quantity_on_order,
                ]);
                
            event(new OrderEvent("refreshed"));

            return response()->json("Updated Successfully", 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
}
