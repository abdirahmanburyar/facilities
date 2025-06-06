<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryResource;
use App\Models\FacilityInventory;
use App\Models\Product;
use App\Models\Location;
use App\Models\Category;
use App\Models\Dosage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Events\InventoryEvent;
use Illuminate\Support\Facades\Event;
use App\Events\InventoryUpdated;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FacilityInventory::query();

        $user = auth()->user();
        
        $query = $query->where('facility_id', $user->facility_id)->with(['product.dosage']);

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('batch_number', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('category')) {
            $query->whereHas('product.category', function ($q) use ($request) {
                $q->where('name', $request->category);
              });
        }

        if ($request->filled('dosage')) {
            $query->whereHas('product.dosage', function ($q) use ($request) {
                $q->where('name', $request->dosage);
              });
        }


        if ($request->filled('warehouse')) {
            $query->whereHas('warehouse', function($query) use($request){
                $query->where('name','like', "%{$request->warehouse}%");
            });
        }

        if ($request->has('location') && $request->location) {
            $query->whereHas('location', function($query) use($request){
                $query->where('location','like', "%{$request->location}%");
            }); 
        }

        $inventories = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
            ->withQueryString();

        // Get products for dropdown
        $products = Product::select('id', 'name')->get();

        // Get inventory status counts
        $inStockCount = FacilityInventory::whereRaw('quantity > (products.id * 3)')
            ->join('products', 'facility_inventories.product_id', '=', 'products.id')
            ->count();

        $lowStockCount = FacilityInventory::whereRaw('quantity <= (products.id * 3)')
            ->join('products', 'facility_inventories.product_id', '=', 'products.id')
            ->count();

        $outOfStockCount = FacilityInventory::where('quantity', 0)
            ->count();

        $soonExpiringCount = FacilityInventory::where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->count();

        $expiredCount = FacilityInventory::where('expiry_date', '<', now())
            ->count();
        
        $inventoryStatusCounts = [
            ['status' => 'in_stock', 'count' => $inStockCount],
            ['status' => 'low_stock', 'count' => $lowStockCount],
            ['status' => 'out_of_stock', 'count' => $outOfStockCount],
            ['status' => 'soon_expiring', 'count' => $soonExpiringCount],
            ['status' => 'expired', 'count' => $expiredCount],
        ];

        $inventories->setPath(url()->current()); // Force Laravel to use full URLs


        return Inertia::render('Inventory/Index', [
            'inventories' => InventoryResource::collection($inventories),
            'products' => $products,
            'filters' => $request->only('search', 'product_id', 'dosage','category', 'location', 'batch_number', 'expiry_date_from', 'expiry_date_to', 'per_page', 'page'),
            'inventoryStatusCounts' => $inventoryStatusCounts,
            'category' => Category::pluck('name')->toArray(),
            'dosage' => Dosage::pluck('name')->toArray(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
           
        $validated = $request->validate([
            'id' => 'nullable|exists:inventories,id',
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0',
            'manufacturing_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacturing_date',
            'batch_number' => 'nullable|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $isNew = !$request->id;
        
        $inventory = Inventory::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        event(new InventoryUpdated());
        
        return response()->json( $request->id ? 'Inventory updated successfully' : 'Inventory created successfully', 200);
        } catch (\Throwable $th) {
            logger()->error('[PUSHER-DEBUG] Error in store method: ' . $th->getMessage());
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory)
    {
        $inventory->load(['product.category', 'product.dosage']);
        return response()->json([
            'success' => true,
            'data' => new InventoryResource($inventory),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {        
        try {
            $inventory->delete();
            event(new InventoryEvent());
            Log::info('Successfully dispatched InventoryEvent for deleted inventory ID: ' . $inventory->id);
            return response()->json([
                'success' => true,
                'message' => 'Inventory item deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }   
    }
    
}
