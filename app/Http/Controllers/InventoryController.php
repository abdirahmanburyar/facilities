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
        $facilityId = auth()->user()->facility_id;

        // AMC based on last 3 months issuance (excluding current)
        $startDate = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $endDate   = Carbon::now()->subMonths(1)->endOfMonth()->format('Y-m-d');
        $amcSubquery = FacilityInventoryMovement::facilityIssued()
            ->where('facility_id', $facilityId)
            ->select('product_id', DB::raw('SUM(facility_issued_quantity) / 3 as amc'))
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->groupBy('product_id');

        // Product-first list to include products with no inventory
        $productQuery = Product::query()
            ->select('products.id', 'products.name', 'products.category_id', 'products.dosage_id')
            ->with(['category:id,name', 'dosage:id,name'])
            ->leftJoinSub($amcSubquery, 'amc_data', function ($join) {
                $join->on('products.id', '=', 'amc_data.product_id');
            })
            ->leftJoin('facility_reorder_levels as frl', function ($join) use ($facilityId) {
                $join->on('products.id', '=', 'frl.product_id')
                     ->where('frl.facility_id', '=', $facilityId);
            })
            ->addSelect(DB::raw('COALESCE(frl.amc, COALESCE(amc_data.amc, 0)) as amc'))
            ->addSelect(DB::raw('COALESCE(frl.reorder_level, ROUND(COALESCE(amc_data.amc, 0) * 6)) as reorder_level'));

        if ($request->filled('search')) {   
            $search = $request->search;
            $productQuery->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhereExists(function($sub) use ($search) {
                      $sub->from('facility_inventories')
                          ->join('facility_inventory_items', 'facility_inventories.id', '=', 'facility_inventory_items.inventory_id')
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
            ->where('facility_inventories.facility_id', $facilityId)
            ->with([
                'product:id,name,category_id,dosage_id',
                'product.category:id,name',
                'product.dosage:id,name',
                'items'
            ])
            ->whereIn('product_id', $productIds)
            ->get()
            ->groupBy('product_id');

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
                $placeholder->setAttribute('facility_id', $facilityId);
                $placeholder->setAttribute('product_id', $product->id);
                $placeholder->setAttribute('amc', $amc);
                $placeholder->setAttribute('reorder_level', $reorderLevel);
                $placeholder->setRelation('product', $product);

                // Synthetic zero-quantity item
                $item = new \App\Models\FacilityInventoryItem();
                $item->setAttribute('id', -$product->id);
                $item->setAttribute('product_id', $product->id);
                $item->setAttribute('quantity', 0);
                $item->setAttribute('batch_number', null);
                $item->setAttribute('barcode', null);
                $item->setAttribute('location', null);
                $item->setAttribute('expiry_date', null);
                $placeholder->setRelation('items', collect([$item]));

                $merged->push($placeholder);
            }
        }

        // Build paginator compatible with frontend
        $inventories = new \Illuminate\Pagination\LengthAwarePaginator(
            $merged->values(),
            $productsPaginator->total(),
            $productsPaginator->perPage(),
            $productsPaginator->currentPage(),
            ['path' => $productsPaginator->path(), 'pageName' => $productsPaginator->getPageName()]
        );

        // Status counts (product-level in/out/low; out_of_stock as zero-total products)
        $statusCounts = [
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'soon_expiring' => 0,
            'expired' => 0,
        ];

        $now = now();
        foreach ($inventories as $inventory) {
            $amc = (float) ($inventory->amc ?? 0);
            $reorderLevel = (float) ($inventory->reorder_level ?? ($amc * 6));

            $totalQty = 0.0;
            $hasExpired = false;
            $hasSoonExpiring = false;
            foreach ($inventory->items ?? [] as $item) {
                $qty = (float) ($item->quantity ?? 0);
                $totalQty += $qty;
                if ($item->expiry_date) {
                    if ($item->expiry_date < $now) {
                        $hasExpired = true;
                    } elseif ($item->expiry_date <= $now->copy()->addDays(160)) {
                        $hasSoonExpiring = true;
                    }
                }
            }

            if ($totalQty <= 0.0) {
                $statusCounts['out_of_stock']++;
            } elseif ($reorderLevel > 0 && $totalQty <= (0.7 * $reorderLevel)) {
                $statusCounts['low_stock']++;
            } else {
                $statusCounts['in_stock']++;
            }

            if ($hasExpired) {
                $statusCounts['expired']++;
            } elseif ($hasSoonExpiring) {
                $statusCounts['soon_expiring']++;
            }
        }

        return Inertia::render('Inventory/Index', [
            'inventories' => InventoryResource::collection($inventories),
            'inventoryStatusCounts' => collect($statusCounts)->map(fn($count, $status) => ['status' => $status, 'count' => $count]),
            'products'   => Product::select('id', 'name')->get(),
            'warehouses' => Warehouse::pluck('name')->toArray(),
            'filters'    => $request->only(['search', 'product_id', 'category', 'dosage', 'per_page', 'page']),
            'category'   => Category::pluck('name')->toArray(),
            'dosage'     => Dosage::pluck('name')->toArray(),
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
