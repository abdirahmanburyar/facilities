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
use App\Imports\FacilityUploadInventory;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\EligibleItem;
use App\Models\Facility;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $facilityId = auth()->user()->facility_id;
            $facility = Facility::find($facilityId);
            $facilityType = $facility?->facility_type;

        // AMC based on last 3 months issuance (excluding current)
        $startDate = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $endDate   = Carbon::now()->subMonths(1)->endOfMonth()->format('Y-m-d');
        $amcSubquery = FacilityInventoryMovement::facilityIssued()
            ->where('facility_inventory_movements.facility_id', $facilityId)
            ->select('facility_inventory_movements.product_id', DB::raw('SUM(facility_issued_quantity) / 3 as amc'))
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->groupBy('facility_inventory_movements.product_id');

        // Product-first list to include products with no inventory
        $productQuery = Product::query()
            ->select('products.id', 'products.name', 'products.category_id', 'products.dosage_id')
            ->with(['category:id,name', 'dosage:id,name'])
            ->leftJoin('facility_reorder_levels as frl', function ($join) use ($facilityId) {
                $join->on('products.id', '=', 'frl.product_id')
                     ->where('frl.facility_id', '=', $facilityId);
            })
            ->addSelect(DB::raw('COALESCE(frl.amc, 0) as amc'))
            ->addSelect(DB::raw('COALESCE(frl.reorder_level, 0) as reorder_level'))
            // Restrict to eligible items for this facility type
            ->whereIn('products.id', EligibleItem::select('eligible_items.product_id')->where('eligible_items.facility_type', $facilityType));

        if ($request->filled('search')) {   
            $search = $request->search;
            $productQuery->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                                    ->orWhereExists(function($sub) use ($search) {
                      $sub->from('facility_inventories')
                          ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.facility_inventory_id')
                          ->whereColumn('facility_inventories.product_id', 'products.id')
                          ->where(function($w) use ($search){
                              $w->where('facility_inventory_items.barcode', 'like', "%{$search}%")
                                ->orWhere('facility_inventory_items.batch_number', 'like', "%{$search}%");
                          });
                  });
            });
        }

        if ($request->filled('product_id')) {
            $productQuery->where('products.id', $request->product_id);
        }

        if ($request->filled('category')) {
            $productQuery->whereHas('category', fn($q) => $q->where('name', $request->category));
        }

        if ($request->filled('dosage')) {
            $productQuery->whereHas('dosage', fn($q) => $q->where('name', $request->dosage));
        }

        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        $productsPaginator = $productQuery->paginate($perPage, ['products.*'], 'page', $page)->withQueryString();
        $productsPaginator->setPath(url()->current());

        $productIds = collect($productsPaginator->items())->pluck('id')->all();

        // Existing facility inventories for those products
        $existingInventories = FacilityInventory::query()
            ->with([
                'product:id,name,category_id,dosage_id',
                'product.category:id,name',
                'product.dosage:id,name',
                'items'
            ])
            ->whereIn('facility_inventories.product_id', $productIds)
            ->get()
            ->groupBy('facility_inventories.product_id');

        // Merge and ensure placeholder for products without inventory
        $merged = collect();
        foreach ($productsPaginator->items() as $product) {
            $amc = (float) ($product->amc ?? 0);
            $reorderLevel = (float) ($product->reorder_level ?? round($amc * 6));

            if (isset($existingInventories[$product->id]) && $existingInventories[$product->id]->isNotEmpty()) {
                $inventory = $existingInventories[$product->id]->first();
                $inventory->setAttribute('amc', $amc);
                $inventory->setAttribute('reorder_level', $reorderLevel);
                $inventory->setRelation('product', $inventory->product->loadMissing('category:id,name', 'dosage:id,name'));
                $merged->push($inventory);
            } else {
                $placeholder = new FacilityInventory();
                $placeholder->setAttribute('id', -$product->id);
                $placeholder->setAttribute('product_id', $product->id);
                $placeholder->setAttribute('amc', $amc);
                $placeholder->setAttribute('reorder_level', $reorderLevel);
                $placeholder->setRelation('product', $product);

                // Synthetic zero-quantity item
                $item = new \App\Models\FacilityInventoryItem();
                $item->setAttribute('id', -$product->id);
                $item->setAttribute('product_id', $product->id);
                $item->setAttribute('quantity', 0);
                $item->setAttribute('barcode', null);
                $item->setAttribute('batch_number', null);
                $item->setAttribute('expiry_date', null);
                $item->setAttribute('uom', null);
                $item->setAttribute('unit_cost', 0);
                $item->setAttribute('total_cost', 0);
                $placeholder->setRelation('items', collect([$item]));

                $merged->push($placeholder);
            }
        }

        // Apply status filters to the merged data
        if ($request->filled('status')) {
            try {
                logger()->info('Applying status filter: ' . $request->status . ' to ' . $merged->count() . ' items');
                
                $merged = $merged->filter(function ($inventory) use ($request) {
                    try {
                        $totalQuantity = $inventory->items->sum('quantity');
                        $reorderLevel = (float) ($inventory->reorder_level ?? 0);
                        
                        switch ($request->status) {
                            case 'reorder_level':
                                // Items that need reorder (total quantity <= 70% of reorder level)
                                $result = $reorderLevel > 0 && $totalQuantity <= ($reorderLevel * 0.7);
                                logger()->info("Product {$inventory->product->name}: Qty={$totalQuantity}, ReorderLevel={$reorderLevel}, NeedsReorder={$result}");
                                return $result;
                            
                            case 'low_stock':
                                // Items that are low stock (total quantity > 0 but <= reorder level)
                                $result = $totalQuantity > 0 && $totalQuantity <= $reorderLevel;
                                logger()->info("Product {$inventory->product->name}: Qty={$totalQuantity}, ReorderLevel={$reorderLevel}, LowStock={$result}");
                                return $result;
                            
                            case 'out_of_stock':
                                // Items that are out of stock (total quantity = 0)
                                $result = $totalQuantity === 0;
                                logger()->info("Product {$inventory->product->name}: Qty={$totalQuantity}, OutOfStock={$result}");
                                return $result;
                            
                            default:
                                return true;
                        }
                    } catch (\Exception $e) {
                        logger()->error('Error filtering inventory item: ' . $e->getMessage());
                        return false;
                    }
                });
                
                logger()->info('After status filtering: ' . $merged->count() . ' items remain');
            } catch (\Exception $e) {
                logger()->error('Error applying status filter: ' . $e->getMessage());
                // If filtering fails, return all items
                $merged = $merged;
            }
        }

        // Build paginator compatible with frontend
        $filteredCount = $merged->count();
        
        // Ensure we always have a valid response structure
        if ($filteredCount === 0) {
            // Create an empty paginator when no results
            $inventories = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                $page,
                ['path' => $productsPaginator->path(), 'pageName' => $productsPaginator->getPageName()]
            );
        } else {
            $inventories = new \Illuminate\Pagination\LengthAwarePaginator(
                $merged->values(),
                $filteredCount,
                $productsPaginator->perPage(),
                $productsPaginator->currentPage(),
                ['path' => $productsPaginator->path(), 'pageName' => $productsPaginator->getPageName()]
            );
        }

        // Calculate status counts independently of pagination
        $statusCounts = $this->calculateInventoryStatusCounts($request);

        logger()->info('Final response - inventories count: ' . $inventories->count() . ', total: ' . $inventories->total() . ', status filter: ' . ($request->status ?? 'none'));

        return Inertia::render('Inventory/Index', [
            'inventories' => InventoryResource::collection($inventories),
            'inventoryStatusCounts' => collect($statusCounts)->map(fn($count, $status) => ['status' => $status, 'count' => $count]),
            'products'   => Product::select('id', 'name')->get(),
            'warehouses' => Warehouse::pluck('name')->toArray(),
            'filters'    => $request->only(['search', 'product_id', 'category', 'dosage', 'per_page', 'page', 'status']),
            'category'   => Category::pluck('name')->toArray(),
            'dosage'     => Dosage::pluck('name')->toArray(),
        ]);
        } catch (\Throwable $th) {
            logger()->error('[INVENTORY-ERROR] Error in index method: ' . $th->getMessage());
            logger()->error('[INVENTORY-ERROR] Stack trace: ' . $th->getTraceAsString());
            
            // Return a safe fallback response
            return Inertia::render('Inventory/Index', [
                'inventories' => new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 25, 1),
                'inventoryStatusCounts' => collect([]),
                'products'   => collect([]),
                'warehouses' => [],
                'filters'    => $request->only(['search', 'product_id', 'category', 'dosage', 'per_page', 'page', 'status']),
                'category'   => [],
                'dosage'     => [],
                'errors'     => 'An error occurred while loading inventory data. Please try again.',
            ]);
        }
    }

    /**
     * Calculate inventory status counts independently of pagination
     */
    private function calculateInventoryStatusCounts(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $facility = Facility::find($facilityId);
        $facilityType = $facility?->facility_type;

        // Product-first query to include products with no inventory
        $query = Product::query()
            ->select('products.id', 'products.name', 'products.category_id', 'products.dosage_id')
            ->with(['category:id,name', 'dosage:id,name'])
            ->leftJoin('facility_reorder_levels as frl', function ($join) use ($facilityId) {
                $join->on('products.id', '=', 'frl.product_id')
                     ->where('frl.facility_id', '=', $facilityId);
            })
            ->addSelect(DB::raw('COALESCE(frl.amc, 0) as amc'))
            ->addSelect(DB::raw('COALESCE(frl.reorder_level, 0) as reorder_level'))
            // Restrict to eligible items for this facility type
            ->whereIn('products.id', EligibleItem::select('eligible_items.product_id')->where('eligible_items.facility_type', $facilityType));

        // Apply the same filters as the main query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhereExists(function($sub) use ($search) {
                      $sub->from('facility_inventories')
                          ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.facility_inventory_id')
                          ->whereColumn('facility_inventories.product_id', 'products.id')
                          ->where(function($w) use ($search){
                              $w->where('facility_inventory_items.barcode', 'like', "%{$search}%")
                                ->orWhere('facility_inventory_items.batch_number', 'like', "%{$search}%");
                          });
                  });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('products.id', $request->product_id);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('name', $request->category));
        }

        if ($request->filled('dosage')) {
            $query->whereHas('dosage', fn($q) => $q->where('name', $request->dosage));
        }

        // Get all results without pagination for counting
        $allProducts = $query->get();

        $statusCounts = [
            'in_stock' => 0,          // number of products with sufficient stock
            'low_stock' => 0,         // number of products at/below 70% of reorder level
            'out_of_stock' => 0,      // number of products with zero total quantity
            'soon_expiring' => 0,
            'expired' => 0,
        ];

        $now = now();
        foreach ($allProducts as $product) {
            $amc = (float) ($product->amc ?? 0);
            $reorderLevel = (float) ($product->reorder_level ?? round($amc * 6));

            // Get total quantity for this product from facility inventory
            $totalQuantity = FacilityInventory::where('facility_inventories.facility_id', $facilityId)
                ->where('facility_inventories.product_id', $product->id)
                ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.facility_inventory_id')
                ->sum('facility_inventory_items.quantity') ?? 0.0;

            // Product-level status for in-stock/low-stock
            if ($reorderLevel > 0 && $totalQuantity <= (0.7 * $reorderLevel)) {
                // Low stock when total_on_hand <= 70% of reorder level
                $statusCounts['low_stock']++;
            } else {
                $statusCounts['in_stock']++;
            }

            // Count expiry status (this would need to be implemented if you have expiry dates)
            // For now, we'll set these to 0 as they're not implemented in the current logic
        }

        // Robust Out-of-Stock (product-level): number of products whose total quantity <= 0
        $filteredProductIds = $query->pluck('products.id');
        if ($filteredProductIds->isNotEmpty()) {
            // Count products with positive total quantity
            $positiveTotals = FacilityInventory::query()
                ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.facility_inventory_id')
                ->whereIn('facility_inventories.product_id', $filteredProductIds)
                ->where('facility_inventories.facility_id', $facilityId)
                ->select('facility_inventories.product_id')
                ->groupBy('facility_inventories.product_id')
                ->havingRaw('COALESCE(SUM(COALESCE(facility_inventory_items.quantity,0)),0) > 0')
                ->get()
                ->count();

            $statusCounts['out_of_stock'] = $filteredProductIds->count() - $positiveTotals;
        }

        return $statusCounts;
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
            array_merge($validated, ['facility_id' => auth()->user()->facility_id])
        );

        // event(new InventoryUpdated(auth()->user()->facility_id));
        
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
            // event(new InventoryEvent());
            Log::info('Successfully dispatched InventoryEvent for deleted inventory ID: ' . $inventory->id);
            return response()->json([
                'success' => true,
                'message' => 'Inventory item deleted successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }   
    }
    

    public function import(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file was uploaded'
                ], 422);
            }
    
            $file = $request->file('file');
    
            // Validate file type
            $extension = $file->getClientOriginalExtension();
            if (!$file->isValid() || !in_array($extension, ['xlsx', 'xls', 'csv'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file'
                ], 422);
            }
    
            // Validate file size (max 50MB)
            if ($file->getSize() > 50 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'File size too large. Maximum allowed size is 50MB'
                ], 422);
            }
    
            $importId = (string) Str::uuid();
    
            Log::info('Queueing product import with Maatwebsite Excel', [
                'import_id' => $importId,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'extension' => $extension
            ]);
    
            // Initialize cache progress to 0
            Cache::put($importId, 0);
    
            // Import the file using Maatwebsite Excel with queue support
            Excel::import(new FacilityUploadInventory($importId, auth()->user()->facility_id), $file);

            return response()->json([
                'success' => true,
                'message' => 'Import queued successfully',
                'import_id' => $importId
            ]);
    
        } catch (\Exception $e) {
            Log::error('Product import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return eligible items for the current user's facility for template download
     */
    public function templateItems(Request $request)
    {
        $user = auth()->user();
        $facility = Facility::find($user->facility_id);
        if (!$facility) {
            return response()->json(['items' => []]);
        }

        // Eligible items by facility_type
        $eligible = EligibleItem::with(['product.category'])
            ->where('facility_type', $facility->facility_type)
            ->get();

        $items = $eligible->map(function ($ei) {
            return [
                'item' => $ei->product->name ?? '',
                'category' => $ei->product->category->name ?? '',
                'uom' => $ei->product->movement ?? '',
            ];
        })->values();

        return response()->json(['items' => $items]);
    }
}
