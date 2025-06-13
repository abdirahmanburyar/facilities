<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryResource;
use App\Models\FacilityInventory;
use App\Models\Product;
use App\Models\Location;
use App\Models\Category;
use App\Models\Dosage;
use App\Models\Dispence;
use App\Models\DispenceItem;
use App\Models\FacilityInventoryMovement;
use App\Models\Warehouse;
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
        // 1. Get the last 4 months for AMC calculation
        $fourMonthsAgo = now()->subMonths(4)->startOfMonth();
        
        // 2. Prepare AMC subquery combining dispense data and facility issued movements
        $amcSubqueryBase = DB::table(DB::raw('(
            SELECT 
                product_id,
                batch_number,
                SUM(quantity) as total_issued_quantity
            FROM (
                -- Dispense items (direct consumption)
                SELECT 
                    di.product_id,
                    di.batch_number,
                    di.quantity
                FROM dispence_items di
                INNER JOIN dispences d ON di.dispence_id = d.id
                WHERE d.dispence_date >= ?
                
                UNION ALL
                
                -- Facility issued movements (transfers, etc.)
                SELECT 
                    fim.product_id,
                    fim.batch_number,
                    fim.facility_issued_quantity as quantity
                FROM facility_inventory_movements fim
                WHERE fim.movement_type = \'facility_issued\'
                AND fim.movement_date >= ?
            ) combined_issued
            GROUP BY product_id, batch_number
        ) as amc_base'))
        ->addBinding([$fourMonthsAgo, $fourMonthsAgo])
        ->select(
            'product_id',
            'batch_number',
            DB::raw('COALESCE(total_issued_quantity / 4, 0) as amc')
        );

        // 3. Main inventory query
        $query = FacilityInventory::query()
            ->leftJoinSub($amcSubqueryBase, 'amc_data', function ($join) {
                $join->on('facility_inventories.product_id', '=', 'amc_data.product_id')
                     ->on('facility_inventories.batch_number', '=', 'amc_data.batch_number');
            })
            ->addSelect('facility_inventories.*')
            ->addSelect(DB::raw('COALESCE(amc_data.amc, 0) as amc'))
            ->addSelect(DB::raw('ROUND(COALESCE(amc_data.amc, 0) * 6) as reorder_level')); // AMC * 6

        $user = auth()->user();
        
        $query = $query->with(['product.dosage:id,name', 'product.category:id,name']);

        // Apply filters
        if ($request->has('search') && $request->search) { // Ensure search is not empty
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('facility_inventories.barcode', 'like', "%{$search}%")
                  ->orWhere('facility_inventories.batch_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($prodQ) use ($search) {
                      $prodQ->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('product_id') && $request->product_id) {
            $query->where('facility_inventories.product_id', $request->product_id);
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

        if ($request->has('facility_id') && $request->facility_id) {
            $query->where('facility_inventories.facility_id', $request->facility_id);
        } else {
            $query->where('facility_inventories.facility_id', $user->facility_id);
        }
        
        $perPage = $request->input('per_page', 25); // Default to 25
        $inventories = $query->paginate($perPage)
            ->withQueryString();

        // Debug log for AMC and Reorder Level
        if ($inventories->isNotEmpty()) {
            Log::debug('Inventory AMC and Reorder Level Calculation Debug:');
            foreach ($inventories->take(5) as $item) { // Log first 5 items
                Log::debug(sprintf(
                    'Product ID: %s, Batch: %s, AMC: %s, Reorder Level: %s, Current Qty: %s',
                    $item->product_id,
                    $item->batch_number,
                    $item->amc,         // This is the calculated AMC from the query
                    $item->reorder_level, // This is the calculated Reorder Level
                    $item->quantity
                ));
            }
        }

        $products = Product::select('id', 'name')->get();
        
        // Inventory Status Counts

        // in stock: quantity > reorder_level
        $inStockCount = DB::table('facility_inventories as inv')
            ->leftJoinSub($amcSubqueryBase, 'amc_data', function($join) {
                $join->on('inv.product_id', '=', 'amc_data.product_id')
                     ->on('inv.batch_number', '=', 'amc_data.batch_number');
            })
            ->where('inv.facility_id', $user->facility_id)
            ->whereRaw('inv.quantity > ROUND(COALESCE(amc_data.amc, 0) * 6)')
            ->count();

        // low stock: 0 < quantity <= reorder_level
        $lowStockCount = DB::table('facility_inventories as inv')
            ->leftJoinSub($amcSubqueryBase, 'amc_data', function($join) {
                $join->on('inv.product_id', '=', 'amc_data.product_id')
                     ->on('inv.batch_number', '=', 'amc_data.batch_number');
            })
            ->where('inv.facility_id', $user->facility_id)
            ->where('inv.quantity', '>', 0)
            ->whereRaw('inv.quantity <= ROUND(COALESCE(amc_data.amc, 0) * 6)')
            ->count();

        $outOfStockCount = DB::table('facility_inventories')
            ->where('facility_id', $user->facility_id)
            ->where('quantity', 0)
            ->count();
            
        $soonExpiringCount = DB::table('facility_inventories')
            ->where('facility_id', $user->facility_id)
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(160))
            ->count();
            
        $expiredCount = DB::table('facility_inventories')
            ->where('facility_id', $user->facility_id)
            ->where('expiry_date', '<', now())
            ->count();
        
        $inventoryStatusCounts = [
            ['status' => 'in_stock', 'count' => $inStockCount],
            ['status' => 'low_stock', 'count' => $lowStockCount],
            ['status' => 'out_of_stock', 'count' => $outOfStockCount],
            ['status' => 'soon_expiring', 'count' => $soonExpiringCount],
            ['status' => 'expired', 'count' => $expiredCount],
        ];

        return Inertia::render('Inventory/Index', [
            'inventories' => InventoryResource::collection($inventories),
            'inventoryStatusCounts' => $inventoryStatusCounts,
            'products' => $products,
            'filters' => $request->only('search', 'product_id', 'dosage','category', 'batch_number', 'expiry_date_from', 'expiry_date_to', 'per_page', 'page'),
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
            'id' => 'nullable|exists:facility_inventories,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'manufacturing_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacturing_date',
            'batch_number' => 'nullable|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $isNew = !$request->id;
        
        $inventory = FacilityInventory::updateOrCreate(
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
    public function show(FacilityInventory $inventory)
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
    public function destroy(FacilityInventory $inventory)
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
