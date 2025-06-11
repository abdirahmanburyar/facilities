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
            'product:id,name,generic_name',
            'createdBy:id,name'
        ]);

        // Apply filters
        if ($request->facility_id) {
            $query->byFacility($request->facility_id);
        }

        if ($request->product_id) {
            $query->byProduct($request->product_id);
        }

        if ($request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->source_type) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->start_date && $request->end_date) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Default to last 30 days if no date filter
        if (!$request->start_date && !$request->end_date) {
            $query->byDateRange(
                Carbon::now()->subDays(30)->startOfDay(),
                Carbon::now()->endOfDay()
            );
        }

        $movements = $query->orderBy('movement_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get facilities and products for filters
        $facilities = Facility::select('id', 'name')->orderBy('name')->get();
        $products = Product::select('id', 'name', 'generic_name')->orderBy('name')->get();

        return Inertia::render('FacilityInventoryMovement/Index', [
            'movements' => $movements,
            'facilities' => $facilities,
            'products' => $products,
            'filters' => $request->only([
                'facility_id', 'product_id', 'movement_type', 
                'source_type', 'start_date', 'end_date'
            ])
        ]);
    }

    /**
     * Get facility inventory summary
     */
    public function summary(Request $request)
    {
        $facilityId = $request->facility_id;
        $startDate = $request->start_date ?? Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->end_date ?? Carbon::now()->endOfDay();

        $query = FacilityInventoryMovement::query();
        
        if ($facilityId) {
            $query->byFacility($facilityId);
        }

        $query->byDateRange($startDate, $endDate);

        // Get summary data
        $summary = [
            'total_facility_received' => $query->clone()->sum('facility_received_quantity'),
            'total_facility_issued' => $query->clone()->sum('facility_issued_quantity'),
            'facility_received_count' => $query->clone()->facilityReceived()->count(),
            'facility_issued_count' => $query->clone()->facilityIssued()->count(),
        ];

        // Get movements by source type
        $bySourceType = $query->clone()
            ->selectRaw('source_type, 
                SUM(facility_received_quantity) as total_facility_received,
                SUM(facility_issued_quantity) as total_facility_issued,
                COUNT(*) as movement_count')
            ->groupBy('source_type')
            ->get();

        // Get recent movements
        $recentMovements = $query->clone()
            ->with(['facility:id,name', 'product:id,name'])
            ->orderBy('movement_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => $summary,
            'by_source_type' => $bySourceType,
            'recent_movements' => $recentMovements
        ]);
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
            'product:id,name,generic_name',
            'createdBy:id,name'
        ]);

        // Apply same filters as index
        if ($request->facility_id) {
            $query->byFacility($request->facility_id);
        }

        if ($request->product_id) {
            $query->byProduct($request->product_id);
        }

        if ($request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->source_type) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->start_date && $request->end_date) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        $movements = $query->orderBy('movement_date', 'desc')->get();

        $filename = 'facility_inventory_movements_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($movements) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date', 'Facility', 'Product', 'Movement Type', 'Source Type',
                'Facility Received Qty', 'Facility Issued Qty', 'Batch Number',
                'Expiry Date', 'Reference Number', 'Created By'
            ]);

            // CSV data
            foreach ($movements as $movement) {
                fputcsv($file, [
                    $movement->movement_date->format('Y-m-d H:i:s'),
                    $movement->facility->name ?? '',
                    $movement->product->name ?? '',
                    ucfirst(str_replace('_', ' ', $movement->movement_type)),
                    ucfirst($movement->source_type),
                    $movement->facility_received_quantity,
                    $movement->facility_issued_quantity,
                    $movement->batch_number ?? '',
                    $movement->expiry_date ? $movement->expiry_date->format('Y-m-d') : '',
                    $movement->reference_number ?? '',
                    $movement->createdBy->name ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
