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
                $productQuery->where(function($q) use ($search, $facilityId) {
                    $q->where('products.name', 'like', "%{$search}%")
                      ->orWhereExists(function($sub) use ($search, $facilityId) {
                          $sub->from('facility_inventories')
                              ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.facility_inventory_id')
                              ->whereColumn('facility_inventories.product_id', 'products.id')
                              ->where('facility_inventories.facility_id', $facilityId)
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

            // Get facility inventories with items for those products
            $facilityInventories = FacilityInventory::query()
                ->with([
                    'product:id,name,category_id,dosage_id',
                    'product.category:id,name',
                    'product.dosage:id,name',
                    'items'
                ])
                ->where('facility_id', $facilityId)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');

            // Build the final inventory list
            $inventories = collect();
            foreach ($productsPaginator->items() as $product) {
                $amc = (float) ($product->amc ?? 0);
                $reorderLevel = (float) ($product->reorder_level ?? round($amc * 6));

                if (isset($facilityInventories[$product->id])) {
                    $inventory = $facilityInventories[$product->id];
                    $inventory->setAttribute('amc', $amc);
                    $inventory->setAttribute('reorder_level', $reorderLevel);
                    $inventories->push($inventory);
                } else {
                    // Create placeholder for products without inventory
                    $placeholder = new FacilityInventory();
                    $placeholder->setAttribute('id', -$product->id);
                    $placeholder->setAttribute('product_id', $product->id);
                    $placeholder->setAttribute('facility_id', $facilityId);
                    $placeholder->setAttribute('quantity', 0);
                    $placeholder->setAttribute('amc', $amc);
                    $placeholder->setAttribute('reorder_level', $reorderLevel);
                    $placeholder->setRelation('product', $product);
                    
                    // Create a synthetic item to ensure the product shows up in the table
                    $syntheticItem = new \App\Models\FacilityInventoryItem();
                    $syntheticItem->setAttribute('id', -$product->id);
                    $syntheticItem->setAttribute('product_id', $product->id);
                    $syntheticItem->setAttribute('quantity', 0);
                    $syntheticItem->setAttribute('barcode', null);
                    $syntheticItem->setAttribute('batch_number', null);
                    $syntheticItem->setAttribute('expiry_date', null);
                    $syntheticItem->setAttribute('uom', null);
                    $syntheticItem->setAttribute('unit_cost', 0);
                    $syntheticItem->setAttribute('total_cost', 0);
                    $placeholder->setRelation('items', collect([$syntheticItem]));
                    
                    $inventories->push($placeholder);
                }
            }

            // Apply status filters
            if ($request->filled('status')) {
                $inventories = $inventories->filter(function ($inventory) use ($request) {
                    $totalQuantity = $inventory->quantity ?? 0;
                    $reorderLevel = (float) ($inventory->reorder_level ?? 0);
                    
                    switch ($request->status) {
                        case 'reorder_level':
                            // Items that need reorder (total quantity <= 70% of reorder level)
                            return $reorderLevel > 0 && $totalQuantity <= ($reorderLevel * 0.7);
                        
                        case 'low_stock':
                            // Items that are low stock (total quantity > 0 but <= reorder level)
                            return $totalQuantity > 0 && $totalQuantity <= $reorderLevel;
                        
                        case 'out_of_stock':
                            // Items that are out of stock (total quantity = 0)
                            return $totalQuantity === 0;
                        
                        default:
                            return true;
                    }
                });
            }

            // Build paginator with proper structure
            $filteredCount = $inventories->count();
            $inventoriesPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $inventories->values(),
                $filteredCount,
                $perPage,
                $page,
                ['path' => $productsPaginator->path(), 'pageName' => $productsPaginator->getPageName()]
            );

            // Calculate status counts
            $statusCounts = $this->calculateInventoryStatusCounts($request);

            return Inertia::render('Inventory/Index', [
                'inventories' => InventoryResource::collection($inventoriesPaginator),
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
            $query->where(function($q) use ($search, $facilityId) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhereExists(function($sub) use ($search, $facilityId) {
                      $sub->from('facility_inventories')
                          ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.facility_inventory_id')
                          ->whereColumn('facility_inventories.product_id', 'products.id')
                          ->where('facility_inventories.facility_id', $facilityId)
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
            $totalQuantity = FacilityInventory::where('facility_id', $facilityId)
                ->where('product_id', $product->id)
                ->value('quantity') ?? 0.0;

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
                ->whereIn('product_id', $filteredProductIds)
                ->where('facility_id', $facilityId)
                ->where('quantity', '>', 0)
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
