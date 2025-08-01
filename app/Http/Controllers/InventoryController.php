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

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Step 1: Get last 3 months (excluding current)
        $startDate = Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $endDate   = Carbon::now()->subMonths(1)->endOfMonth()->format('Y-m-d');
        // Step 2: Convert string date and filter by last 3 months
        $amcSubquery = FacilityInventoryMovement::facilityIssued()
            ->where('facility_id', auth()->user()->facility_id)
            ->select('product_id', DB::raw('SUM(facility_issued_quantity) / 3 as amc'))
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->groupBy('product_id');

        $query = FacilityInventory::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->with([
                'product:id,name,category_id,dosage_id',
                'product.category:id,name',
                'product.dosage:id,name'
            ])
            ->leftJoinSub($amcSubquery, 'amc_data', function ($join) {
                $join->on('facility_inventories.product_id', '=', 'amc_data.product_id');
            })
            ->addSelect('facility_inventories.*')
            ->addSelect(DB::raw('COALESCE(amc_data.amc, 0) as amc'))
            ->addSelect(DB::raw('ROUND(COALESCE(amc_data.amc, 0) * 6) as reorder_level'));

        if ($request->filled('search')) {   
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('items', function($itemQuery) use ($search) {
                    $itemQuery->where('barcode', 'like', "%{$search}%")
                        ->orWhere('batch_number', 'like', "%{$search}%");
                })
                ->orWhereHas('product', function($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('category')) {
            $query->whereHas('product.category', fn($q) => $q->where('name', $request->category));
        }

        if ($request->filled('dosage')) {
            $query->whereHas('product.dosage', fn($q) => $q->where('name', $request->dosage));
        }

        $inventories = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
        ->withQueryString();
        $inventories->setPath(url()->current()); // Force Laravel to use full URLs

        $statusCounts = [
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'soon_expiring' => 0,
            'expired' => 0,
        ];

        $now = now();
        foreach ($inventories as $inventory) {
            $amc = $inventory->amc ?: 0;
            $reorderLevel = $inventory->reorder_level ?: ($amc * 6);

            foreach ($inventory->items ?? [] as $item) {
                $qty = $item->quantity;

                if ($qty == 0) {
                    $statusCounts['out_of_stock']++;
                } elseif ($qty <= $reorderLevel) {
                    $statusCounts['low_stock']++;
                } else {
                    $statusCounts['in_stock']++;
                }

                if ($item->expiry_date) {
                    if ($item->expiry_date < $now) {
                        $statusCounts['expired']++;
                    } elseif ($item->expiry_date <= $now->copy()->addDays(160)) {
                        $statusCounts['soon_expiring']++;
                    }
                }
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
}
