<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Facility;
use App\Models\FacilityMonthlyReport;
use App\Models\FacilityMonthlyReportItem;
use App\Models\FacilityInventoryMovement;
use App\Models\Product;
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
            $reportPeriod = sprintf('%04d-%02d', $year, $month);
            GenerateMonthlyInventoryReportJob::dispatch($facilityId, $reportPeriod, $force);

            return response()->json([
                'success' => true,
                'message' => 'Monthly inventory report generation started. You will be notified when it\'s complete.',
                'report_period' => $reportPeriod
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
        $query = FacilityMonthlyReport::with(['facility', 'items.product','approvedBy','reviewedBy','submittedBy','rejectedBy'])
            ->where('report_period', $reportPeriod)
            ->where('facility_id', $facilityId)
            ->first();

        if(!$query){
            return redirect()->route('reports.monthly-inventory')
                ->with('error', 'No report found for the specified period.');
        }

        // Get user permissions
        $user = auth()->user();
        $canApprove = $user->can('lmis.approve');

        return Inertia::render('Reports/ViewMonthlyInventory', [
            'reports' => $query,
            'facility' => $currentFacility,
            'reportPeriod' => $reportPeriod,
            'monthName' => Carbon::createFromFormat('Y-m', $reportPeriod)->format('F Y'),
            'isApproved' => $query->status === 'approved',
            'noReportsFound' => false,
            'message' => null,
            'canApprove' => $canApprove
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
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 403);
        }

        $facilityId = $currentFacility->id;
        $reportPeriod = $request->report_period;

        try {
            // Get the report with all relationships
            $report = FacilityMonthlyReport::where('report_period', $reportPeriod)
                ->where('facility_id', $facilityId)
                ->with([
                    'facility', 
                    'submittedBy', 
                    'reviewedBy',
                    'approvedBy',
                    'rejectedBy'
                ])
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'exists' => false,
                    'message' => 'Report not found for this period.'
                ], 404);
            }

            // Calculate summary statistics using database aggregations
            $summaryStats = FacilityMonthlyReportItem::where('parent_id', $report->id)
                ->selectRaw('
                    COUNT(*) as total_items,
                    COUNT(DISTINCT product_id) as total_products,
                    COALESCE(SUM(opening_balance), 0) as total_opening_balance,
                    COALESCE(SUM(closing_balance), 0) as total_closing_balance,
                    COALESCE(SUM(stock_received), 0) as total_received,
                    COALESCE(SUM(stock_issued), 0) as total_issued
                ')
                ->first();

            // Get user permissions
            $user = auth()->user();
            $permissions = [
                'can_submit' => $user->can('lmis.submit'),
                'can_review' => $user->can('lmis.review'),
                'can_approve' => $user->can('lmis.approve'),
                'can_edit' => $user->can('lmis.edit'),
            ];

            // Build audit trail using relationships
            $auditTrail = collect();
            
            if ($report->submitted_at && $report->submittedBy) {
                $auditTrail->push([
                    'action' => 'submitted',
                    'timestamp' => $report->submitted_at->format('Y-m-d H:i:s'),
                    'user' => $report->submittedBy->name,
                    'status' => 'Submitted for Review'
                ]);
            }

            if ($report->reviewed_at && $report->reviewedBy) {
                $auditTrail->push([
                    'action' => 'reviewed',
                    'timestamp' => $report->reviewed_at->format('Y-m-d H:i:s'),
                    'user' => $report->reviewedBy->name,
                    'status' => 'Reviewed'
                ]);
            }

            if ($report->approved_at && $report->approvedBy) {
                $auditTrail->push([
                    'action' => 'approved',
                    'timestamp' => $report->approved_at->format('Y-m-d H:i:s'),
                    'user' => $report->approvedBy->name,
                    'status' => 'Approved'
                ]);
            }

            if ($report->rejected_at && $report->rejectedBy) {
                $auditTrail->push([
                    'action' => 'rejected',
                    'timestamp' => $report->rejected_at->format('Y-m-d H:i:s'),
                    'user' => $report->rejectedBy->name,
                    'status' => 'Rejected',
                    'comments' => $report->comments
                ]);
            }

            return response()->json([
                'success' => true,
                'exists' => true,
                'report' => [
                    'id' => $report->id,
                    'facility_id' => $report->facility_id,
                    'report_period' => $report->report_period,
                    'status' => $report->status,
                    'comments' => $report->comments,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ],
                'summary' => [
                    'total_items' => (int) $summaryStats->total_items,
                    'total_products' => (int) $summaryStats->total_products,
                    'total_opening_balance' => (float) $summaryStats->total_opening_balance,
                    'total_closing_balance' => (float) $summaryStats->total_closing_balance,
                    'total_received' => (float) $summaryStats->total_received,
                    'total_issued' => (float) $summaryStats->total_issued,
                ],
                'audit_trail' => $auditTrail->sortBy('timestamp')->values(),
                'facility' => [
                    'id' => $report->facility->id,
                    'name' => $report->facility->name,
                    'facility_type' => $report->facility->facility_type,
                ],
                'permissions' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting report status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving report status.'
            ], 500);
        }
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
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 403);
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

    /**
     * Submit monthly report for review
     */
    public function submitMonthlyReport(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:facility_monthly_reports,id',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 403);
        }

        // Find the report and verify ownership
        $report = FacilityMonthlyReport::where('id', $request->report_id)
            ->where('facility_id', $currentFacility->id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or access denied.'
            ], 404);
        }

        // Check if report can be submitted
        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft reports can be submitted for review.'
            ], 400);
        }

        // Update report status
        $report->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted for review successfully.',
            'report' => $report
        ]);
    }

    /**
     * Approve monthly report
     */
    public function approveMonthlyReport(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:facility_monthly_reports,id'
        ]);

        try {
            $report = FacilityMonthlyReport::where('id', $request->report_id)
                ->where('facility_id', auth()->user()->facility_id)
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found or you do not have permission to access it.'
                ], 404);
            }

            // Only allow approval from reviewed status
            if ($report->status !== 'reviewed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Report must be reviewed before it can be approved.'
                ], 400);
            }

            // Check permission
            if (!auth()->user()->can('lmis.approve')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to approve reports.'
                ], 403);
            }

            $report->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report approved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error approving report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the report.'
            ], 500);
        }
    }

    /**
     * Reject monthly report
     */
    public function rejectMonthlyReport(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:facility_monthly_reports,id',
            'comments' => 'nullable|string|max:1000'
        ]);

        try {
            $report = FacilityMonthlyReport::where('id', $request->report_id)
                ->where('facility_id', auth()->user()->facility_id)
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found or you do not have permission to access it.'
                ], 404);
            }

            // Allow rejection from submitted or reviewed status
            if (!in_array($report->status, ['submitted', 'reviewed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only submitted or reviewed reports can be rejected.'
                ], 400);
            }

            // Check permission
            if (!auth()->user()->can('lmis.approve')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to reject reports.'
                ], 403);
            }

            $report->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
                'comments' => $request->comments,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report rejected successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error rejecting report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the report.'
            ], 500);
        }
    }

    /**
     * Return a monthly report to draft status
     */
    public function returnMonthlyReportToDraft(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:facility_monthly_reports,id'
        ]);

        try {
            $report = FacilityMonthlyReport::where('id', $request->report_id)
                ->where('facility_id', auth()->user()->facility_id)
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found or you do not have permission to access it.'
                ], 404);
            }

            if ($report->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only submitted reports can be returned to draft.'
                ], 400);
            }

            $report->update([
                'status' => 'draft',
                'submitted_at' => null,
                'submitted_by' => null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report has been returned to draft successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error returning report to draft: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while returning the report to draft.'
            ], 500);
        }
    }

    /**
     * Reopen an approved monthly report for editing
     */
    public function reopenMonthlyReport(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:facility_monthly_reports,id'
        ]);

        try {
            $report = FacilityMonthlyReport::where('id', $request->report_id)
                ->where('facility_id', auth()->user()->facility_id)
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found or you do not have permission to access it.'
                ], 404);
            }

            if ($report->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved reports can be reopened.'
                ], 400);
            }

            $report->update([
                'status' => 'draft',
                'approved_at' => null,
                'approved_by' => null,
                'reopened_at' => now(),
                'reopened_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report has been reopened for editing successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error reopening report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reopening the report.'
            ], 500);
        }
    }

    /**
     * Start review of a monthly inventory report
     */
    public function startMonthlyReportReview(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:facility_monthly_reports,id',
        ]);

        // Get current user's facility
        $currentFacility = auth()->user()->facility;
        
        if (!$currentFacility) {
            return response()->json([
                'success' => false,
                'message' => 'No facility assigned to your account.'
            ], 403);
        }

        // Find the report and verify it belongs to user's facility
        $report = FacilityMonthlyReport::where('id', $request->report_id)
            ->where('facility_id', $currentFacility->id)
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found or access denied.'
            ], 404);
        }

        // Check if report is in submitted status
        if ($report->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Report must be in submitted status to start review.'
            ], 400);
        }

        // // Check if user has review permission
        // if (!auth()->user()->can('lmis.review')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You do not have permission to review reports.'
        //     ], 403);
        // }

        try {
            // Update report status to reviewed
            $report->update([
                'status' => 'reviewed',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report has been reviewed successfully.',
                'report' => [
                    'id' => $report->id,
                    'status' => $report->status,
                    'reviewed_at' => $report->reviewed_at,
                    'reviewed_by' => $report->reviewed_by,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display facility inventory movements with filtering options
     */
    public function inventoryMovements(Request $request)
    {
        // Check if any filters are applied
        $hasFilters = $request->filled('product_id') || 
                     $request->filled('movement_type') || 
                     $request->filled('source_type') || 
                     $request->filled('start_date') || 
                     $request->filled('end_date');

        // Initialize empty paginated result
        $movements = new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            $request->get('per_page', 25),
            1,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        // Only fetch data if filters are applied
        if ($hasFilters) {
            $query = FacilityInventoryMovement::with([
                'facility:id,name',
                'product:id,name',
                'createdBy:id,name'
            ])->where('facility_id', auth()->user()->facility_id);

            // Apply filters
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
        }

        // Get products for filter options
        $products = Product::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Reports/InventoryMovements', [
            'movements' => $movements,
            'products' => $products,
        ]);
    }

    /**
     * Get facility inventory summary for movements
     */
    public function inventoryMovementsSummary(Request $request)
    {
        $query = FacilityInventoryMovement::where('facility_id', auth()->user()->facility_id);
        
        // Apply filters if provided
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
            'total_received' => $query->clone()->where('movement_type', 'facility_received')->sum('quantity') ?: 0,
            'total_issued' => $query->clone()->where('movement_type', 'facility_issued')->sum('quantity') ?: 0,
            'received_count' => $query->clone()->where('movement_type', 'facility_received')->count(),
            'issued_count' => $query->clone()->where('movement_type', 'facility_issued')->count(),
        ];

        return response()->json($summary);
    }

    /**
     * Export inventory movements to CSV
     */
    public function exportInventoryMovements(Request $request)
    {
        $query = FacilityInventoryMovement::with([
            'facility:id,name',
            'product:id,name',
            'createdBy:id,name'
        ])->where('facility_id', auth()->user()->facility_id);

        // Apply the same filters as index method
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

        $filename = 'inventory_movements_' . now()->format('Y-m-d_H-i-s') . '.csv';

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
                'Quantity',
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
                    $movement->movement_type == 'facility_received' ? 'Received' : 'Issued',
                    $movement->source_type,
                    $movement->quantity ?: '',
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
