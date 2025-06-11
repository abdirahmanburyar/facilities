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
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
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
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
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
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
        }

        $facilityId = $currentFacility->id;
        $reportPeriod = $request->report_period;

        // Get the report data using report_period field
        $query = FacilityMonthlyReport::with(['facility', 'items.product'])
            ->where('report_period', $reportPeriod)
            ->where('facility_id', $facilityId);

        $reports = $query->get();

        $reportData = [];
        $noReportsFound = $reports->isEmpty();

        if (!$noReportsFound) {
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
                    'status' => $report->status,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                    'items' => $report->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->name,
                            'unit' => $item->product->unit ?? 'Units',
                            'opening_balance' => (float) $item->opening_balance,
                            'stock_received' => (float) $item->stock_received,
                            'stock_issued' => (float) $item->stock_issued,
                            'positive_adjustments' => (float) $item->positive_adjustments,
                            'negative_adjustments' => (float) $item->negative_adjustments,
                            'closing_balance' => (float) $item->closing_balance,
                            'stockout_days' => (int) $item->stockout_days,
                        ];
                    })->groupBy(function($item) {
                        return 'ESSENTIAL MEDICINES'; // Simple grouping for now
                    })
                ];
            });
        }

        return Inertia::render('Reports/ViewMonthlyInventory', [
            'reports' => $reportData,
            'facility' => $currentFacility,
            'reportPeriod' => $reportPeriod,
            'monthName' => Carbon::createFromFormat('Y-m', $reportPeriod)->format('F Y'),
            'isApproved' => !$noReportsFound && $reports->first()->status === 'approved',
            'noReportsFound' => $noReportsFound,
            'message' => $noReportsFound ? 'No reports found for the specified criteria. Please generate the report first.' : null
        ]);
    }

    /**
     * Export monthly inventory report as Excel
     */
    public function exportMonthlyReportExcel(Request $request)
    {
        $request->validate([
            'report_period' => 'required|string',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
        }

        $facilityId = $currentFacility->id;
        $reportPeriod = $request->report_period;

        // Get the report data
        $query = FacilityMonthlyReport::with(['facility', 'items.product'])
            ->where('report_period', $reportPeriod)
            ->where('facility_id', $facilityId);

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No reports found for the specified period.');
        }

        // Prepare Excel data
        $excelData = [];
        
        // Add headers
        $excelData[] = [
            'Item / Strength/Dose /Dosage Form',
            'Unit of Measurement',
            'Beginning Balance',
            'Qty Received',
            'Qty Consumed',
            'Positive Adjustment',
            'Negative Adjustment',
            'Closing Balance',
            'Stockout Days'
        ];

        // Add data rows
        foreach ($reports as $report) {
            foreach ($report->items as $item) {
                $excelData[] = [
                    $item->product->name,
                    $item->product->unit ?? 'Units',
                    $item->opening_balance ?? 0,
                    $item->stock_received ?? 0,
                    $item->stock_issued ?? 0,
                    $item->positive_adjustments ?? 0,
                    $item->negative_adjustments ?? 0,
                    $item->closing_balance ?? 0,
                    $item->stockout_days ?? 0
                ];
            }
        }

        // Create CSV content (simple Excel format)
        $filename = 'LMIS_Monthly_Report_' . $currentFacility->name . '_' . $reportPeriod . '.csv';
        
        $callback = function() use ($excelData) {
            $file = fopen('php://output', 'w');
            
            foreach ($excelData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
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

    /**
     * Update individual report item (adjustments and stockout days)
     */
    public function updateReportItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer|exists:facility_monthly_report_items,id',
            'positive_adjustments' => 'nullable|numeric|min:0',
            'negative_adjustments' => 'nullable|numeric|min:0',
            'stockout_days' => 'nullable|integer|min:0|max:31',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
        }

        // Find the report item and verify it belongs to user's facility
        $reportItem = FacilityMonthlyReportItem::with('report')
            ->where('id', $request->item_id)
            ->first();

        if (!$reportItem || $reportItem->report->facility_id !== $currentFacility->id) {
            return response()->json([
                'success' => false,
                'message' => 'Report item not found or access denied.'
            ], 404);
        }

        // Check if report is still editable (not approved)
        if ($reportItem->report->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit approved reports.'
            ], 403);
        }

        // Update the item
        $reportItem->update([
            'positive_adjustments' => $request->positive_adjustments ?? 0,
            'negative_adjustments' => $request->negative_adjustments ?? 0,
            'stockout_days' => $request->stockout_days ?? 0,
        ]);

        // Recalculate closing balance
        $reportItem->closing_balance = $reportItem->opening_balance 
            + $reportItem->stock_received 
            - $reportItem->stock_issued 
            + $reportItem->positive_adjustments 
            - $reportItem->negative_adjustments;
        
        $reportItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Report item updated successfully.',
            'item' => $reportItem
        ]);
    }

    /**
     * Save report (update status or other report-level changes)
     */
    public function saveReport(Request $request)
    {
        $request->validate([
            'report_period' => 'required|string',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No facility assigned to your account.');
        }

        $facilityId = $currentFacility->id;
        $reportPeriod = $request->report_period;

        // Find the report
        $report = FacilityMonthlyReport::where('report_period', $reportPeriod)
            ->where('facility_id', $facilityId)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found.'
            ], 404);
        }

        // Update report timestamp
        $report->touch();

        return response()->json([
            'success' => true,
            'message' => 'Report saved successfully.',
            'report' => $report
        ]);
    }
}
