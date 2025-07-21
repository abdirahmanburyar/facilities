<?php

namespace App\Http\Controllers;

use App\Models\FacilityMonthlyReport;
use App\Models\FacilityMonthlyReportItem;
use App\Models\Product;
use App\Models\Facility;
use App\Jobs\GenerateMonthlyInventoryReportJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response as ResponseFacade;

class MonthlyInventoryReportController extends Controller
{
    /**
     * Display a listing of monthly reports
     */
    public function index(Request $request): Response
    {
        $facilityId = auth()->user()->facility_id;
        
        $query = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->with(['items.product.category:id,name','items.product.dosage:id,name','facility.handledBy','approvedBy:id,name','submittedBy:id,name','reviewedBy:id,name','rejectedBy:id,name']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('product_id')) {
            if (is_array($request->product_id)) {
                $query->whereIn('items.product_id', $request->product_id);
            } else {
                $query->where('items.product_id', $request->product_id);
            }
        }

        // Only fetch reports if both year and month are provided
        if ($request->filled('month_year')) {
            $reports = $query->orderBy('created_at', 'desc')
                            ->first();
        } else {
            // Return empty collection if year or month is not provided
            $reports = collect();
        }

        // Get the current facility to determine its type
        $facility = auth()->user()->facility;
        
        // Get eligible products for this facility type for the filter dropdown
        $products = collect();
        if ($facility) {
            $products = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
        }
        
        $years = range(date('Y'), date('Y') - 5);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('MonthlyInventoryReport/Index', [
            'reports' => $reports,
            'filters' => $request->only(['month_year', 'status', 'product_id']),
            'products' => $products,
            'years' => $years,
            'months' => $months,
            'facilityType' => $facility ? $facility->facility_type : null,
        ]);
    }

    /**
     * Show the form for creating a new report or editing existing one
     */
    public function create(Request $request): Response
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        
        // Get the current facility to determine its type
        $facility = auth()->user()->facility;
        if (!$facility) {
            return redirect()->back()->with('error', 'Facility not found for current user.');
        }
        
        // Format the report period
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        // Get or create the monthly report for this period
        $monthlyReport = FacilityMonthlyReport::firstOrCreate([
            'facility_id' => $facilityId,
            'report_period' => $reportPeriod,
        ], [
            'status' => 'draft',
        ]);
        
        // Get eligible products for this facility type
        $eligibleProducts = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
        
        // Get existing report items for this period
        $existingItems = FacilityMonthlyReportItem::where('parent_id', $monthlyReport->id)
            ->with('product:id,name','product.dosage')
            ->get()
            ->keyBy('product_id');

        $reportData = [];
        foreach ($eligibleProducts as $product) {
            $existing = $existingItems->get($product->id);
            $reportData[] = [
                'product_id' => $product->id,
                'product' => $product,
                'opening_balance' => $existing ? $existing->opening_balance : 0,
                'stock_received' => $existing ? $existing->stock_received : 0,
                'stock_issued' => $existing ? $existing->stock_issued : 0,
                'positive_adjustments' => $existing ? $existing->positive_adjustments : 0,
                'negative_adjustments' => $existing ? $existing->negative_adjustments : 0,
                'closing_balance' => $existing ? $existing->closing_balance : 0,
                'stockout_days' => $existing ? $existing->stockout_days : 0,
                'id' => $existing ? $existing->id : null,
            ];
        }

        return Inertia::render('MonthlyInventoryReport/Create', [
            'reportData' => $reportData,
            'year' => $year,
            'month' => $month,
            'monthName' => $this->getMonthName($month),
            'facility' => $facility,
            'reportId' => $monthlyReport->id,
            'eligibleProductsCount' => $eligibleProducts->count(),
            'facilityType' => $facility->facility_type,
        ]);
    }

    /**
     * Store or update monthly reports
     */
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'reports' => 'required|array',
            'reports.*.product_id' => 'required|exists:products,id',
            'reports.*.opening_balance' => 'required|numeric|min:0',
            'reports.*.stock_received' => 'required|numeric|min:0',
            'reports.*.stock_issued' => 'required|numeric|min:0',
            'reports.*.positive_adjustments' => 'nullable|numeric|min:0',
            'reports.*.negative_adjustments' => 'nullable|numeric|min:0',
            'reports.*.stockout_days' => 'nullable|integer|min:0|max:31',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->year;
        $month = $request->month;
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        // Get or create the monthly report for this period
        $monthlyReport = FacilityMonthlyReport::firstOrCreate([
            'facility_id' => $facilityId,
            'report_period' => $reportPeriod,
        ], [
            'status' => 'draft',
        ]);
        
        $savedCount = 0;
        $errors = [];

        foreach ($request->reports as $reportData) {
            try {
                // Calculate closing balance
                $closingBalance = $reportData['opening_balance'] 
                    + $reportData['stock_received'] 
                    - $reportData['stock_issued'] 
                    + ($reportData['positive_adjustments'] ?? 0) 
                    - ($reportData['negative_adjustments'] ?? 0);

                $data = [
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                    'opening_balance' => $reportData['opening_balance'],
                    'stock_received' => $reportData['stock_received'],
                    'stock_issued' => $reportData['stock_issued'],
                    'positive_adjustments' => $reportData['positive_adjustments'] ?? 0,
                    'negative_adjustments' => $reportData['negative_adjustments'] ?? 0,
                    'closing_balance' => $closingBalance,
                    'stockout_days' => $reportData['stockout_days'] ?? 0,
                ];

                FacilityMonthlyReportItem::updateOrCreate([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                ], $data);

                $savedCount++;
            } catch (\Exception $e) {
                $errors[] = "Error saving report for product ID {$reportData['product_id']}: " . $e->getMessage();
            }
        }

        if (empty($errors)) {
            return redirect()->route('monthly-reports.index')
                ->with('success', "Successfully saved {$savedCount} monthly reports.");
        } else {
            return redirect()->back()
                ->with('error', 'Some reports could not be saved: ' . implode(', ', $errors))
                ->with('success', "Successfully saved {$savedCount} reports.");
        }
    }

    /**
     * Submit reports for approval
     */
    public function submit(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $reportPeriod = sprintf('%04d-%02d', $request->year, $request->month);
        
        $monthlyReport = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->where('report_period', $reportPeriod)
            ->where('status', 'draft')
            ->first();

        if (!$monthlyReport) {
            return redirect()->back()->with('error', 'No draft report found for this period.');
        }

        $monthlyReport->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "Successfully submitted report for approval.");
    }

    /**
     * Export monthly report as CSV
     */
    public function export(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        $reports = FacilityMonthlyReportItem::with(['product:id,name','product.category:id,name', 'product.dosage:id,name', 'report.facility:id,name'])
            ->whereHas('report', function ($q) use ($facilityId, $reportPeriod) {
                $q->where('facility_id', $facilityId)
                  ->where('report_period', $reportPeriod);
            })
            ->orderBy('product_id')
            ->get();

        if ($reports->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected period.');
        }

        $monthName = $this->getMonthName($month);
        $facilityName = auth()->user()->facility->name ?? 'Unknown Facility';
        
        $filename = "Monthly_Inventory_Report_{$facilityName}_{$monthName}_{$year}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reports, $monthName, $year, $facilityName) {
            $file = fopen('php://output', 'w');
            
            // Add header information
            fputcsv($file, ["Monthly Summary Report Form: Logistic Data Hospitals & HCs"]);
            fputcsv($file, ["Facility: {$facilityName}"]);
            fputcsv($file, ["Report Period: {$monthName} {$year}"]);
            fputcsv($file, []);
            
            // Add CSV headers
            fputcsv($file, [
                'Item Name',
                'Category',
                'Dosage Form',
                'Opening Balance',
                'Stock Received',
                'Stock Issued',
                'Positive Adjustments',
                'Negative Adjustments',
                'Closing Balance',
                'Stockout Days'
            ]);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->product->name ?? 'Unknown Product',
                    $report->product->category->name ?? 'N/A',
                    $report->product->dosage->name ?? 'N/A',
                    $report->opening_balance ?? 0,
                    $report->stock_received ?? 0,
                    $report->stock_issued ?? 0,
                    $report->positive_adjustments ?? 0,
                    $report->negative_adjustments ?? 0,
                    $report->closing_balance ?? 0,
                    $report->stockout_days ?? 0,
                ]);
            }

            fclose($file);
        };

        return ResponseFacade::stream($callback, 200, $headers);
    }

    /**
     * Get summary data for reports
     */
    public function summary(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        $monthlyReport = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->where('report_period', $reportPeriod)
            ->first();

        if (!$monthlyReport) {
            return response()->json([
                'total_products' => 0,
                'total_opening_balance' => 0,
                'total_received' => 0,
                'total_issued' => 0,
                'total_closing_balance' => 0,
                'total_stockout_days' => 0,
                'draft_reports' => 0,
                'submitted_reports' => 0,
                'approved_reports' => 0,
            ]);
        }

        $items = $monthlyReport->items();

        $summary = [
            'total_products' => $items->count(),
            'total_opening_balance' => $items->sum('opening_balance') ?? 0,
            'total_received' => $items->sum('stock_received') ?? 0,
            'total_issued' => $items->sum('stock_issued') ?? 0,
            'total_closing_balance' => $items->sum('closing_balance') ?? 0,
            'total_stockout_days' => $items->sum('stockout_days') ?? 0,
            'draft_reports' => $monthlyReport->status === 'draft' ? 1 : 0,
            'submitted_reports' => $monthlyReport->status === 'submitted' ? 1 : 0,
            'approved_reports' => $monthlyReport->status === 'approved' ? 1 : 0,
        ];

        return response()->json($summary);
    }

    /**
     * Trigger report generation via queue from the web interface
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $reportPeriod = sprintf('%04d-%02d', $year, $month);

        try {
            // Check if facility exists
            if (!$facilityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found for current user.'
                ], 400);
            }

            // Get the current facility to determine its type
            $facility = auth()->user()->facility;
            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found for current user.'
                ], 400);
            }

            // Create or get the monthly report
            $monthlyReport = FacilityMonthlyReport::firstOrCreate([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
            ], [
                'status' => 'draft',
            ]);

            // Get eligible products for this facility type
            $eligibleProducts = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
            
            if ($eligibleProducts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No eligible products found for facility type: {$facility->facility_type}"
                ], 400);
            }
            
            $createdCount = 0;
            $updatedCount = 0;
            
            foreach ($eligibleProducts as $product) {
                $item = FacilityMonthlyReportItem::firstOrCreate([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $product->id,
                ], [
                    'opening_balance' => 0,
                    'stock_received' => 0,
                    'stock_issued' => 0,
                    'positive_adjustments' => 0,
                    'negative_adjustments' => 0,
                    'closing_balance' => 0,
                    'stockout_days' => 0,
                ]);
                
                if ($item->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }

            $message = "Report generation completed successfully.";
            if ($createdCount > 0) {
                $message .= " {$createdCount} new items created.";
            }
            if ($updatedCount > 0) {
                $message .= " {$updatedCount} existing items updated.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                    'total_products' => $eligibleProducts->count(),
                    'report_period' => $reportPeriod,
                    'facility_id' => $facilityId,
                    'facility_type' => $facility->facility_type
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly report generation failed: ' . $e->getMessage(), [
                'facility_id' => $facilityId,
                'year' => $year,
                'month' => $month,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get month name from number
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return $months[$month] ?? 'Unknown';
    }
}
