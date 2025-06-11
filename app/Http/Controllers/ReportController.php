<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Facility;
use App\Models\FacilityMonthlyReport;
use App\Models\FacilityMonthlyReportItem;
use App\Jobs\GenerateMonthlyInventoryReportJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display the reports dashboard
     */
    public function index()
    {
        return Inertia::render('Reports/Index');
    }

    /**
     * Display the monthly inventory report interface
     */
    public function monthlyInventory()
    {
        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return redirect()->back()->with('error', 'No facility assigned to your account.');
        }

        return Inertia::render('Reports/MonthlyInventory', [
            'facility' => [
                'id' => $currentFacility->id,
                'name' => $currentFacility->name,
                'facility_type' => $currentFacility->facility_type,
            ],
            'currentYear' => now()->year,
            'currentMonth' => now()->month,
        ]);
    }

    /**
     * Generate monthly inventory report
     */
    public function generateMonthlyReport(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (now()->year + 1),
            'month' => 'required|integer|min:1|max:12',
            'force' => 'boolean',
            'use_selected_unit_only' => 'boolean'
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 400);
        }

        $facilityId = $currentFacility->id;
        $year = $request->year;
        $month = $request->month;
        $force = $request->boolean('force', false);

        try {
            // Dispatch the job to generate the report
            GenerateMonthlyInventoryReportJob::dispatch($facilityId, $year, $month, $force);

            return response()->json([
                'success' => true,
                'message' => 'Monthly inventory report generation started. You will be notified when it\'s complete.',
                'report_period' => sprintf('%04d-%02d', $year, $month)
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating monthly report: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start report generation. Please try again.'
            ], 500);
        }
    }

    /**
     * View generated monthly inventory report
     */
    public function viewMonthlyReport(Request $request)
    {
        $request->validate([
            'report_period' => 'required|string',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 400);
        }

        $facilityId = $currentFacility->id;
        $reportPeriod = $request->report_period;

        // Get the report data using report_period field
        $query = FacilityMonthlyReport::with(['facility', 'items.product'])
            ->where('report_period', $reportPeriod)
            ->where('facility_id', $facilityId);

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No reports found for the specified criteria. Please generate the report first.'
            ], 404);
        }

        // Format the data for display
        $reportData = $reports->map(function ($report) {
            return [
                'id' => $report->id,
                'facility' => [
                    'id' => $report->facility->id,
                    'name' => $report->facility->name,
                    'facility_type' => $report->facility->facility_type,
                ],
                'report_period' => $report->report_period,
                'year' => $report->year,
                'month' => $report->month,
                'status' => $report->status,
                'items' => $report->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'dosage_form' => $item->product->dosage_form ?? '',
                            'strength' => $item->product->strength ?? '',
                            'category' => $item->product->category ?? 'ESSENTIAL MEDICINES'
                        ],
                        'opening_balance' => (float) $item->opening_balance,
                        'stock_received' => (float) $item->stock_received,
                        'stock_issued' => (float) $item->stock_issued,
                        'positive_adjustments' => (float) $item->positive_adjustments,
                        'negative_adjustments' => (float) $item->negative_adjustments,
                        'closing_balance' => (float) $item->closing_balance,
                        'stockout_days' => (int) $item->stockout_days,
                    ];
                })->groupBy('product.category')
            ];
        });

        return Inertia::render('Reports/ViewMonthlyInventory', [
            'reports' => $reportData,
            'facility' => $currentFacility,
            'report_period' => $reportPeriod,
            'month_name' => Carbon::createFromFormat('Y-m', $reportPeriod)->format('F Y')
        ]);
    }

    /**
     * Export monthly inventory report as Excel
     */
    public function exportMonthlyReportExcel(Request $request)
    {
        // Implementation for Excel export
        // This would use Laravel Excel package
        return response()->json(['message' => 'Excel export feature coming soon']);
    }

    /**
     * Export monthly inventory report as PDF
     */
    public function exportMonthlyReportPdf(Request $request)
    {
        // Implementation for PDF export
        // This would use Laravel PDF package
        return response()->json(['message' => 'PDF export feature coming soon']);
    }

    /**
     * Get report generation status
     */
    public function getReportStatus(Request $request)
    {
        $request->validate([
            'report_period' => 'required|string',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 400);
        }

        $facilityId = $currentFacility->id;
        $reportPeriod = $request->report_period;

        $query = FacilityMonthlyReport::where('report_period', $reportPeriod)
            ->where('facility_id', $facilityId);

        $reports = $query->get();

        return response()->json([
            'reports_found' => $reports->count(),
            'reports' => $reports->map(function ($report) {
                return [
                    'facility_id' => $report->facility_id,
                    'facility_name' => $report->facility->name,
                    'status' => $report->status,
                    'items_count' => $report->items()->count(),
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ];
            })
        ]);
    }
}
