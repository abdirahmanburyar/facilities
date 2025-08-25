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

            // Base query with relationships - adapted from warehouse but using facilities data structure
            $productQuery = Product::query()
                ->with([
                    'category:id,name',
                    'dosage:id,name',
                    'facilityInventoryItems.inventory'
                ])
                // Restrict to eligible items for this facility type
                ->whereIn('products.id', EligibleItem::select('eligible_items.product_id')->where('eligible_items.facility_type', $facilityType));

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $productQuery->where(function($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                      ->orWhereHas('facilityInventoryItems', function($sub) use ($search) {
                          $sub->where(function($w) use ($search) {
                              $w->where('barcode', 'like', "%{$search}%")
                                ->orWhere('batch_number', 'like', "%{$search}%");
                          });
                      });
                });
            }

            if ($request->filled('category')) {
                $productQuery->whereHas('category', fn($q) => $q->where('name', $request->category));
            }
            if ($request->filled('dosage')) {
                $productQuery->whereHas('dosage', fn($q) => $q->where('name', $request->dosage));
            }

            // Status filter - will be applied after data is loaded
            $statusFilter = $request->filled('status') ? $request->status : null;

            // Paginate
            $products = $productQuery->paginate(
                $request->input('per_page', 25),
                ['*'],
                'page',
                $request->input('page', 1)
            )->withQueryString();

            $products->setPath(url()->current());
            
            // Add reorder_level and amc to each product using the Product model methods
            $products->getCollection()->transform(function ($product) use ($facilityId) {
                $metrics = $product->getFacilityInventoryStructureForFacility($facilityId);
                $product->reorder_level = $metrics['reorder_level'];
                $product->amc = $metrics['amc'];
                $product->status = $metrics['status'];
                return $product;
            });
            
            // Apply status filter after data is loaded and calculated
            if ($statusFilter) {
                try {
                    $filteredCollection = $products->getCollection()->filter(function ($product) use ($statusFilter, $facilityId) {
                        try {
                            // Use the already calculated metrics from the transform
                            $totalQuantity = $product->facilityInventoryItemsForFacility($facilityId)->sum('quantity');
                            $reorderLevel = $product->reorder_level ?? 0;
                            
                            switch ($statusFilter) {
                                case 'in_stock':
                                    if ($reorderLevel <= 0) {
                                        return $totalQuantity > 0;
                                    }
                                    $lowStockThreshold = $reorderLevel * 1.3;
                                    return $totalQuantity > $lowStockThreshold;
                                    
                                case 'low_stock':
                                    if ($reorderLevel <= 0) return false;
                                    $lowStockThreshold = $reorderLevel * 1.3;
                                    return $totalQuantity > $reorderLevel && $totalQuantity <= $lowStockThreshold;
                                    
                                case 'low_stock_reorder_level':
                                    if ($reorderLevel <= 0) return false;
                                    return $totalQuantity > 1 && $totalQuantity <= $reorderLevel;
                                    
                                case 'out_of_stock':
                                    return $totalQuantity <= 0;
                                    
                                default:
                                    return true;
                            }
                        } catch (\Exception $e) {
                            Log::warning('[INVENTORY-FILTER] Error filtering product ' . ($product->id ?? 'unknown') . ': ' . $e->getMessage());
                            return false; // Exclude problematic products
                        }
                    });
                    
                    // Update the collection and pagination
                    $products->setCollection($filteredCollection);
                    $products->setTotal($filteredCollection->count());
                } catch (\Exception $e) {
                    Log::error('[INVENTORY-FILTER] Error applying status filter: ' . $e->getMessage());
                    // Continue without filtering if there's an error
                }
            }

            // Filters data
            $categories = Category::orderBy('name')->pluck('name')->toArray();
            $dosages = Dosage::orderBy('name')->pluck('name')->toArray();

            // Status counts
            $statusCounts = [
                [ 'status' => 'in_stock', 'count' => 0 ],
                [ 'status' => 'low_stock', 'count' => 0 ],
                [ 'status' => 'low_stock_reorder_level', 'count' => 0 ],
                [ 'status' => 'out_of_stock', 'count' => 0 ],
            ];

            // Calculate status counts using the Product model methods for reliability
            $allProducts = Product::with(['facilityInventoryItems'])
                ->whereIn('products.id', EligibleItem::select('eligible_items.product_id')->where('eligible_items.facility_type', $facilityType))
                ->get();

            foreach ($allProducts as $product) {
                try {
                    // Use the facilities Product model methods for consistent calculation
                    $metrics = $product->getFacilityInventoryStructureForFacility($facilityId);
                    // Ensure items is always a collection for sum() method
                    $items = collect($metrics['items']);
                    $totalQuantity = $items->sum('quantity');
                    $reorderLevel = $metrics['reorder_level'];
                    
                    if ($totalQuantity <= 0) {
                        $statusCounts[3]['count']++; // out_of_stock
                    } elseif ($reorderLevel <= 0) {
                        // No reorder level set, default to in stock
                        $statusCounts[0]['count']++; // in_stock
                    } else {
                        // Calculate the low stock threshold (reorder level + 30%)
                        $lowStockThreshold = $reorderLevel * 1.3;
                        
                        if ($totalQuantity > 1 && $totalQuantity <= $reorderLevel) {
                            // Items at or below reorder level but more than 1 (2 to 9,000 in your example)
                            $statusCounts[2]['count']++; // low_stock_reorder_level
                        } elseif ($totalQuantity <= $lowStockThreshold) {
                            // Items between reorder level and reorder level + 30% (9,001 to 11,700 in your example)
                            $statusCounts[1]['count']++; // low_stock
                        } else {
                            // Items above reorder level + 30% (above 11,700 in your example)
                            $statusCounts[0]['count']++; // in_stock
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('[INVENTORY-COUNT] Error counting status for product ' . ($product->id ?? 'unknown') . ': ' . $e->getMessage());
                    // Skip problematic products in counting
                }
            }

            return Inertia::render('Inventory/Index', [
                'inventories' => InventoryResource::collection($products),
                'inventoryStatusCounts' => $statusCounts,
                'filters' => $request->only(['search', 'per_page', 'page', 'category', 'dosage', 'status']),
                'category' => $categories,
                'dosage' => $dosages,
            ]);
        } catch (\Exception $e) {
            Log::error('[INVENTORY-ERROR] Error in index method: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return back()->withErrors(['error' => 'An error occurred while loading inventory data.']);
        }
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
    
            $file = $request->request->file('file');
    
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
    
            // Store the file
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('inventory_uploads', $fileName, 'public');
    
            // Import the data
            $import = new FacilityUploadInventory();
            Excel::import($import, $filePath, 'public');
    
            return response()->json([
                'success' => true,
                'message' => 'Inventory data imported successfully',
                'data' => $import->getResults()
            ], 200);
    
        } catch (\Throwable $th) {
            logger()->error('[INVENTORY-IMPORT-ERROR] Error in import method: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to import inventory data: ' . $th->getMessage()
            ], 500);
        }
    }
}
