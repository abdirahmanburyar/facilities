<?php

namespace App\Http\Controllers;

use App\Models\FacilityInventoryMovement;
use App\Models\Facility;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class FacilityInventoryMovementController extends Controller
{
    /**
     * Display facility inventory movements with filtering options
     */
    public function index(Request $request)
    {
        $query = FacilityInventoryMovement::with([
            'facility:id,name',
            'product:id,name',
            'createdBy:id,name'
        ])->where('facility_id', auth()->user()->facility_id);

        // Apply filters (removed facility_id filter since it's now single facility)
        if ($request->filled('product_id') && is_array($request->product_id)) {
            $query->whereIn('product_id', $request->product_id);
        }

        if ($request->filled('movement_type') && is_array($request->movement_type)) {
            $query->whereIn('movement_type', $request->movement_type);
        }

        if ($request->filled('source_type') && is_array($request->source_type)) {
            $query->whereIn('source_type', $request->source_type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('movement_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('movement_date', '<=', $request->end_date);
        }

        // Order by movement date descending
        $query->orderBy('movement_date', 'desc');

        // Get per page value, default to 25
        $perPage = $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;

        $movements = $query->paginate($perPage)->withQueryString();

        // Only get products since facility is now fixed to user's facility
        $products = Product::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('FacilityInventoryMovement/Index', [
            'movements' => $movements,
            'products' => $products,
        ]);
    }

    /**
     * Get facility inventory summary
     */
    public function summary(Request $request)
    {
        $query = FacilityInventoryMovement::where('facility_id', auth()->user()->facility_id);
        
        // Apply filters if provided (removed facility_id filter since it's now single facility)
        if ($request->filled('product_id') && is_array($request->product_id)) {
            $query->whereIn('product_id', $request->product_id);
        }

        if ($request->filled('movement_type') && is_array($request->movement_type)) {
            $query->whereIn('movement_type', $request->movement_type);
        }

        if ($request->filled('source_type') && is_array($request->source_type)) {
            $query->whereIn('source_type', $request->source_type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('movement_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('movement_date', '<=', $request->end_date);
        }

        // Get summary data
        $summary = [
            'total_facility_received' => $query->clone()->sum('facility_received_quantity') ?: 0,
            'total_facility_issued' => $query->clone()->sum('facility_issued_quantity') ?: 0,
            'facility_received_count' => $query->clone()->where('movement_type', 'facility_received')->count(),
            'facility_issued_count' => $query->clone()->where('movement_type', 'facility_issued')->count(),
        ];

        return response()->json($summary);
    }

    /**
     * Get facility inventory balance for a specific product
     */
    public function productBalance(Request $request)
    {
        $facilityId = $request->facility_id;
        $productId = $request->product_id;

        if (!$facilityId || !$productId) {
            return response()->json(['error' => 'Facility ID and Product ID are required'], 400);
        }

        $movements = FacilityInventoryMovement::where('facility_id', $facilityId)
            ->where('product_id', $productId)
            ->orderBy('movement_date', 'desc')
            ->with(['facility:id,name', 'product:id,name'])
            ->get();

        $totalReceived = $movements->sum('facility_received_quantity');
        $totalIssued = $movements->sum('facility_issued_quantity');
        $balance = $totalReceived - $totalIssued;

        return response()->json([
            'total_facility_received' => $totalReceived,
            'total_facility_issued' => $totalIssued,
            'current_balance' => $balance,
            'movements' => $movements
        ]);
    }

    /**
     * Record facility received movement (from transfer or order)
     */
    public static function recordFacilityReceived($data)
    {
        return FacilityInventoryMovement::recordFacilityReceived($data);
    }

    /**
     * Record facility issued movement (from dispense)
     */
    public static function recordFacilityIssued($data)
    {
        return FacilityInventoryMovement::recordFacilityIssued($data);
    }

    /**
     * Get movements for a specific facility
     */
    public function facilityMovements(Request $request, $facilityId)
    {
        $movements = FacilityInventoryMovement::byFacility($facilityId)
            ->with(['product:id,name', 'createdBy:id,name'])
            ->orderBy('movement_date', 'desc')
            ->paginate(20);

        $facility = Facility::findOrFail($facilityId);

        return Inertia::render('FacilityInventoryMovement/FacilityMovements', [
            'facility' => $facility,
            'movements' => $movements
        ]);
    }

    /**
     * Export movements to CSV
     */
    public function export(Request $request)
    {
        $query = FacilityInventoryMovement::with([
            'facility:id,name',
            'product:id,name',
            'createdBy:id,name'
        ])->where('facility_id', auth()->user()->facility_id);

        // Apply the same filters as index method (removed facility_id filter)
        if ($request->filled('product_id') && is_array($request->product_id)) {
            $query->whereIn('product_id', $request->product_id);
        }

        if ($request->filled('movement_type') && is_array($request->movement_type)) {
            $query->whereIn('movement_type', $request->movement_type);
        }

        if ($request->filled('source_type') && is_array($request->source_type)) {
            $query->whereIn('source_type', $request->source_type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('movement_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('movement_date', '<=', $request->end_date);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $filename = 'facility_inventory_movements_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($movements) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Date',
                'Item',
                'Movement Type',
                'Source Type',
                'Received Quantity',
                'Issued Quantity',
                'Batch Number',
                'Expiry Date',
                'Reference Number',
                'Created By',
                'Created At'
            ]);

            // Add data rows
            foreach ($movements as $movement) {
                fputcsv($file, [
                    $movement->movement_date,
                    $movement->product->name ?? '',
                    $movement->movement_type == 'facility_received' ? 'Received Quantity' : 'Issued Quantity',
                    $movement->source_type,
                    $movement->facility_received_quantity ?: '',
                    $movement->facility_issued_quantity ?: '',
                    $movement->batch_number ?: '',
                    $movement->expiry_date ?: '',
                    $movement->reference_number ?: '',
                    $movement->createdBy->name ?? '',
                    $movement->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
