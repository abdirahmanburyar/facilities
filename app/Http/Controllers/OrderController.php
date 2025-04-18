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

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recent');
        
        $order = Order::where('id', $request->id)->with(['user', 'items.product'])
            ->when($tab === 'pending', function($query) {
                return $query->where('status', 'pending');
            })
            ->when($tab === 'completed', function($query) {
                return $query->where('status', 'completed');
            })
            ->latest()
            ->first();
        return inertia('Order/Index', [
            'orders' => Order::whereDate('order_date', Carbon::now()->toDateString())->get(),
            'currentOrder' => $order,
            'products' => Product::get(),
            'tab' => $tab
        ]);
    }

    public function createOrder(Request $request){
        try {
            return DB::transaction(function () use ($request) {
                $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT);
                
                $order = Order::create([
                    'user_id' => auth()->user()->id,
                    'facility_id' => auth()->user()->facility_id,
                    'order_type' => $request->order_type,
                    'status' => 'pending',
                    'order_number' => $orderNumber,
                    'order_date' => Carbon::now()->toDateString(),
                    'expected_date' => now()->addDays(7), // Default 7 days expectation
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

            // Trigger Kafka event for order status change
            try {
                Kafka::publishOrderPlaced("Refreshed");
            } catch (\Throwable $e) {
                return response()->json($e->getMessage(), 500);
            }

            event(new OrderEvent('Refreshed'));

            DB::commit();
            return response()->json('Order status updated successfully.', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function store(Request $request){
        try {
            return DB::transaction(function () use ($request) {
                $request->validate([
                    'id' => 'nullable|exists:order_items,id',
                    'product_id' => 'required|exists:products,id',
                    'quantity' => 'required',
                    'order_id' => 'required'
                ]);

                // Check for duplicate product in same order, excluding current item if updating
                $query = OrderItem::where('product_id', $request->product_id)
                    ->where('order_id', $request->order_id);
                
                if ($request->id) {
                    $query->where('id', '!=', $request->id);
                }

                if ($query->exists()) {
                    return response()->json(Product::find($request->product_id)->name . ' already exists in this order.', 409);
                }

                OrderItem::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'product_id' => $request->product_id,
                        'quantity' => $request->quantity,
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

    public function search(Request $request){
        try {
            $request->validate([
                'barcode' => 'required',
                'tag' => 'required'
            ]);
            $barcode = $request->barcode;
            $facilityId = auth()->user()->facility_id;
            $tag = $request->tag;
            $exist = EligibleItem::where('facility_id', $facilityId)->first();
            if(!$exist && $tag == 'allow') {
                return response()->json(["message" => "Facility does not have an eligible item", "product" => null], 200);
            }
            
            // Find the product by barcode or name
            $product = Product::where('barcode', $barcode)->orWhere('name', 'like', '%' . $barcode . '%')->first();
            logger()->info($product);
            
            // Check if product exists in inventory with quantity > 0
            if ($product) {
                $inventory = DB::table('inventories')
                    ->where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->first();
                
                if (!$inventory) {
                    return response()->json([
                        "message" => "Product is out of stock", 
                        "product" => null
                    ], 200);
                }
            }
            
            return response()->json(["product" => $product, "message" => "success"], 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function remove(Request $request){
        try {
            return DB::transaction(function () use ($request) {
                $orderItem = OrderItem::findOrFail($request->id);
                if($orderItem->order->status !== 'pending') {
                    return response()->json('Order item cannot be removed', 409);
                }
                $orderItem->delete();
                return response()->json("Order item removed successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function submitOrder(Request $request){
        try {
            return DB::transaction(function () use ($request) {
                event(new OrderEvent("refreshed"));
                return response()->json("Order submitted successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

}
