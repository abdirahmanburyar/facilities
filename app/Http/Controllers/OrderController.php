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

        $order = Order::where('facility_id', auth()->user()->facility_id)
            ->where('id', $request->id)
            ->with(['user', 'items.product'])
            ->when($tab === 'pending', function ($query) {
                return $query->where('status', 'pending');
            })
            ->when($tab === 'completed', function ($query) {
                return $query->where('status', 'completed');
            })
            ->latest()
            ->first();

        return inertia('Order/Index', [
            'orders' => Order::where('facility_id', auth()->user()->facility_id)->whereDate('order_date', Carbon::now()->toDateString())->get(),
            'currentOrder' => $order,
            'products' => Product::get(),
            'tab' => $tab
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
}
