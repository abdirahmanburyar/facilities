<?php

namespace App\Http\Controllers;

use App\Http\Resources\InventoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Category;
use App\Models\Dosage;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Inertia\Inertia;
use App\Events\InventoryEvent;
use Illuminate\Support\Facades\Event;
use App\Models\IssueQuantityReport;
use App\Models\IssueQuantityItem;
use App\Events\InventoryUpdated;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UploadInventory;
use App\Services\InventoryAnalyticsService;
use App\Models\InventoryItem;

class InventoryController extends Controller
{
	public function index(Request $request)
	{
		try {
			// Get the current user's facility
			$user = auth()->user();
			$facility = $user->facility;
			
			if (!$facility) {
				return back()->withErrors(['error' => 'User is not associated with any facility.']);
			}
			
		// Base query with relationships - filter products by eligible items for this facility type
		$productQuery = Product::query()
			->with([
				'category:id,name',
				'dosage:id,name',
				'inventories.items' => function($query) use ($facility) {
					$query->whereHas('inventory', function($subQuery) use ($facility) {
						$subQuery->where('facility_id', $facility->id);
					});
				}
			])
			->whereHas('eligible', function($query) use ($facility) {
				$query->where('facility_type', $facility->facility_type);
			})
			->where('is_active', true);
	
			// Apply filters
			if ($request->filled('search')) {
				$search = $request->search;
				$productQuery->where(function($q) use ($search) {
					$q->where('products.name', 'like', "%{$search}%")
					  ->orWhereHas('inventories.items', function($sub) use ($search) {
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
	
			// Status filter - will be applied after data is loaded but before pagination
			$statusFilter = $request->filled('status') ? $request->status : null;
	
			// Get all products first (without pagination) if status filter is applied
			if ($statusFilter) {
				$allProducts = $productQuery->get();
			} else {
				// Paginate normally if no status filter
				$products = $productQuery->paginate(
					$request->input('per_page', 25),
					['*'],
					'page',
					$request->input('page', 1)
				)->withQueryString();
				$products->setPath(url()->current());
			}
			
			// Add reorder_level and amc to each product using the Product model methods
			$currentProducts = $statusFilter ? $allProducts : $products->getCollection();
		$currentProducts->transform(function ($product) use ($facility) {
			try {
				// Set a timeout for each product calculation to prevent hanging
				set_time_limit(10); // 10 seconds per product
				
				$metrics = $product->calculateInventoryMetrics($facility->id);
				
				// Debug logging
				Log::info("Product {$product->id} ({$product->name}) metrics:", [
					'facility_id' => $facility->id,
					'metrics' => $metrics,
					'has_inventories' => $product->inventories ? $product->inventories->count() : 0,
					'total_items' => $product->inventories ? $product->inventories->flatMap->items->count() : 0
				]);
				
				$product->reorder_level = $metrics['reorder_level'];
				$product->amc = $metrics['amc'];
				
			} catch (\Exception $e) {
				Log::error("Error calculating metrics for product {$product->id}: " . $e->getMessage());
				// Use fallback values if calculation fails
				$product->reorder_level = $product->calculateFallbackReorderLevel($facility->id);
				$product->amc = 0;
			}
			
			return $product;
		});
			
			// Apply status filter after data is loaded and calculated
			if ($statusFilter) {
				try {
					$filteredCollection = $allProducts->filter(function ($product) use ($statusFilter) {
						try {
							$totalQuantity = $product->inventories->flatMap->items->sum('quantity');
							$reorderLevel = $product->reorder_level ?? 0;
							
							switch ($statusFilter) {
								case 'in_stock':
									if ($reorderLevel <= 0) {
										return $totalQuantity > 0;
									}
									$lowStockThreshold = $reorderLevel * 1.3;
									return $totalQuantity > $lowStockThreshold;
									
								case 'low_stock':
									if ($reorderLevel <= 0) {
										// No reorder level set, cannot be "low stock" - return false
										return false;
									}
									$lowStockThreshold = $reorderLevel * 1.3;
									return $totalQuantity > $reorderLevel && $totalQuantity <= $lowStockThreshold;
									
								case 'low_stock_reorder_level':
									if ($reorderLevel <= 0) return false;
									return $totalQuantity > 0 && $totalQuantity <= $reorderLevel;
									
								case 'out_of_stock':
									return $totalQuantity == 0;
									
								default:
									return true;
							}
						} catch (\Exception $e) {
							Log::warning('[INVENTORY-FILTER] Error filtering product ' . ($product->id ?? 'unknown') . ': ' . $e->getMessage());
							return false; // Exclude problematic products
						}
					});
					
					// Create pagination from filtered results
					$perPage = $request->input('per_page', 25);
					$currentPage = $request->input('page', 1);
					$offset = ($currentPage - 1) * $perPage;
					$paginatedItems = $filteredCollection->slice($offset, $perPage)->values();
					
					$products = new \Illuminate\Pagination\LengthAwarePaginator(
						$paginatedItems,
						$filteredCollection->count(),
						$perPage,
						$currentPage,
						[
							'path' => request()->url(),
							'pageName' => 'page',
						]
					);
					$products->withQueryString();
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
	
		// Calculate status counts using the same query structure as main inventory
		$allProducts = Product::with([
				'category:id,name',
				'dosage:id,name',
				'inventories.items' => function($query) use ($facility) {
					$query->whereHas('inventory', function($subQuery) use ($facility) {
						$subQuery->where('facility_id', $facility->id);
					});
				}
			])
			->whereHas('eligible', function($query) use ($facility) {
				$query->where('facility_type', $facility->facility_type);
			})
			->where('is_active', true)
			->get();
			
		// Calculate status counts using the Product model methods
		foreach ($allProducts as $product) {
			try {
				// Set a timeout for each product calculation
				set_time_limit(10); // 10 seconds per product
				
				// Only sum quantities from items that belong to this facility (already filtered by the with clause)
				$totalQuantity = $product->inventories->flatMap->items->sum('quantity');
					$metrics = $product->calculateInventoryMetrics($facility->id);
					$reorderLevel = $metrics['reorder_level'];
					
					if ($totalQuantity <= 0) {
						$statusCounts[3]['count']++; // out_of_stock
					} elseif ($reorderLevel > 0) {
						$lowStockThreshold = $reorderLevel * 1.3;
						
						if ($totalQuantity > $lowStockThreshold) {
							$statusCounts[0]['count']++; // in_stock
						} elseif ($totalQuantity > $reorderLevel && $totalQuantity <= $lowStockThreshold) {
							$statusCounts[1]['count']++; // low_stock
						} else {
							$statusCounts[2]['count']++; // low_stock_reorder_level
						}
					} else {
						// No reorder level set - cannot be "low stock", only in_stock or out_of_stock
						if ($totalQuantity > 0) {
							$statusCounts[0]['count']++; // in_stock
						} else {
							$statusCounts[3]['count']++; // out_of_stock
						}
					}
				} catch (\Exception $e) {
					Log::warning('[INVENTORY-COUNT] Error counting status for product ' . ($product->id ?? 'unknown') . ': ' . $e->getMessage());
					// Use fallback reorder level if calculation fails
					try {
						$reorderLevel = $product->calculateFallbackReorderLevel($facility->id);
					} catch (\Exception $fallbackError) {
						Log::error('[INVENTORY-COUNT] Fallback calculation also failed for product ' . ($product->id ?? 'unknown') . ': ' . $fallbackError->getMessage());
						$reorderLevel = 10; // Default minimum
					}
					// Continue with the product using fallback values
				}
			}
	
			return Inertia::render('Inventory/Index', [
				'inventories' => InventoryResource::collection($products),
				'inventoryStatusCounts' => $statusCounts,
				'filters' => $request->only(['search', 'per_page', 'page', 'category', 'dosage', 'status', 'location', 'warehouse']),
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
	 * Apply status filter to the product query
	 */
	protected function applyStatusFilter($query, $status)
	{
		return $query->whereHas('inventories.items', function($subQuery) use ($status) {
			$subQuery->where('inventory_items.quantity', '>', 0);
		});
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

		// event(new InventoryUpdated());
		
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
	
	public function getLocations(Request $request){
		try {
			$warehouse = $request->input('warehouse');
			
			if (!$warehouse) {
				return response()->json([], 200);
			}

			$locations = Location::where('warehouse', $warehouse)
				->select('id', 'location', 'warehouse')
				->get()
				->map(function($location) {
					return [
						'id' => $location->id,
						'location' => $location->location,
						'warehouse' => $location->warehouse
					];
				});

			return response()->json($locations, 200);
		} catch (\Throwable $th) {
			return response()->json($th->getMessage(), 500);
		}
	}

	public function updateLocation(Request $request)
	{
		$request->validate([
			'inventory_item_id' => 'required|exists:inventory_items,id',
			'location' => 'required|string|max:255'
		]);

		try {
			$inventoryItem = InventoryItem::findOrFail($request->inventory_item_id);
			$inventoryItem->update([
				'location' => $request->location
			]);

			// Trigger inventory update event
			// event(new InventoryUpdated($inventoryItem->inventory));

			return response()->json([
				'success' => true,
				'message' => 'Location updated successfully',
				'data' => $inventoryItem
			]);
		} catch (\Exception $e) {
			Log::error('Error updating inventory location: ' . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Failed to update location: ' . $e->getMessage()
			], 500);
		}
	}

	/**
	 * Import inventory items from Excel file
	 */
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
		
			// Queue the import job
			Excel::queueImport(new UploadInventory($importId), $file)->onQueue('imports');

			// broadcast(new UpdateProductUpload($importId, 0));

		
			return response()->json([
				'success' => true,
				'message' => 'Import has been queued successfully',
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
	 * Check if an item needs reorder action (out of stock)
	 *
	 * @param mixed $item
	 * @return bool
	 */
	private function needsReorderAction($item): bool
	{
		// If item doesn't exist or has zero quantity, it needs reorder
		if (!$item || !isset($item->quantity) || (float) $item->quantity <= 0) {
			return true;
		}
		return false;
	}
}
