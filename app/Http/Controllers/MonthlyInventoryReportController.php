<?php

namespace App\Http\Controllers;

use App\Models\FacilityMonthlyInventoryReport;
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
        
        $query = FacilityMonthlyInventoryReport::with(['product:id,name,strength,dosage_form,unit', 'facility:id,name'])
            ->where('facility_id', $facilityId);

        // Apply filters
        if ($request->filled('year')) {
            $query->where('report_year', $request->year);
        }
        
        if ($request->filled('month')) {
            $query->where('report_month', $request->month);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('product_id')) {
            if (is_array($request->product_id)) {
                $query->whereIn('product_id', $request->product_id);
            } else {
                $query->where('product_id', $request->product_id);
            }
        }

        $perPage = $request->get('per_page', 15);
        $reports = $query->orderBy('report_year', 'desc')
                        ->orderBy('report_month', 'desc')
                        ->orderBy('product_id')
                        ->paginate($perPage)
                        ->withQueryString();

        // Get filter options
        $products = Product::select('id', 'name', 'strength', 'dosage_form')->get();
        $years = range(date('Y'), date('Y') - 5);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('MonthlyInventoryReport/Index', [
            'reports' => $reports,
            'filters' => $request->only(['year', 'month', 'status', 'product_id', 'per_page']),
            'products' => $products,
            'years' => $years,
            'months' => $months,
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
        
        // Get all products for the facility
        $products = Product::select('id', 'name', 'strength', 'dosage_form', 'unit')->get();
        
        // Get existing reports for this period
        $existingReports = FacilityMonthlyInventoryReport::where('facility_id', $facilityId)
            ->where('report_year', $year)
            ->where('report_month', $month)
            ->with('product:id,name,strength,dosage_form,unit')
            ->get()
            ->keyBy('product_id');

        $reportData = [];
        foreach ($products as $product) {
            $existing = $existingReports->get($product->id);
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
                'comments' => $existing ? $existing->comments : '',
                'status' => $existing ? $existing->status : 'draft',
                'id' => $existing ? $existing->id : null,
            ];
        }

        return Inertia::render('MonthlyInventoryReport/Create', [
            'reportData' => $reportData,
            'year' => $year,
            'month' => $month,
            'monthName' => $this->getMonthName($month),
            'facility' => auth()->user()->facility,
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
            'reports.*.comments' => 'nullable|string|max:1000',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->year;
        $month = $request->month;
        
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
                    'facility_id' => $facilityId,
                    'product_id' => $reportData['product_id'],
                    'report_year' => $year,
                    'report_month' => $month,
                    'opening_balance' => $reportData['opening_balance'],
                    'stock_received' => $reportData['stock_received'],
                    'stock_issued' => $reportData['stock_issued'],
                    'positive_adjustments' => $reportData['positive_adjustments'] ?? 0,
                    'negative_adjustments' => $reportData['negative_adjustments'] ?? 0,
                    'closing_balance' => $closingBalance,
                    'stockout_days' => $reportData['stockout_days'] ?? 0,
                    'comments' => $reportData['comments'] ?? null,
                    'status' => 'draft',
                ];

                FacilityMonthlyInventoryReport::updateOrCreate([
                    'facility_id' => $facilityId,
                    'product_id' => $reportData['product_id'],
                    'report_year' => $year,
                    'report_month' => $month,
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
        
        $updated = FacilityMonthlyInventoryReport::where('facility_id', $facilityId)
            ->where('report_year', $request->year)
            ->where('report_month', $request->month)
            ->where('status', 'draft')
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => auth()->id(),
            ]);

        return redirect()->back()->with('success', "Successfully submitted {$updated} reports for approval.");
    }

    /**
     * Export monthly report as CSV
     */
    public function export(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        
        $reports = FacilityMonthlyInventoryReport::with(['product:id,name,strength,dosage_form,unit', 'facility:id,name'])
            ->where('facility_id', $facilityId)
            ->where('report_year', $year)
            ->where('report_month', $month)
            ->orderBy('product_id')
            ->get();

        $monthName = $this->getMonthName($month);
        $facilityName = auth()->user()->facility->name;
        
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
                'Strength/Dose',
                'Dosage Form', 
                'Unit of Measurement',
                'Opening Balance',
                'Stock Received',
                'Stock Issued',
                'Positive Adjustments',
                'Negative Adjustments',
                'Closing Balance',
                'Stockout Days',
                'Comments'
            ]);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->product->name,
                    $report->product->strength ?? '',
                    $report->product->dosage_form ?? '',
                    $report->product->unit ?? '',
                    $report->opening_balance,
                    $report->stock_received,
                    $report->stock_issued,
                    $report->positive_adjustments,
                    $report->negative_adjustments,
                    $report->closing_balance,
                    $report->stockout_days,
                    $report->comments ?? '',
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
        
        $query = FacilityMonthlyInventoryReport::where('facility_id', $facilityId)
            ->where('report_year', $year)
            ->where('report_month', $month);

        $summary = [
            'total_products' => $query->count(),
            'total_opening_balance' => $query->sum('opening_balance'),
            'total_received' => $query->sum('stock_received'),
            'total_issued' => $query->sum('stock_issued'),
            'total_closing_balance' => $query->sum('closing_balance'),
            'total_stockout_days' => $query->sum('stockout_days'),
            'draft_reports' => $query->clone()->where('status', 'draft')->count(),
            'submitted_reports' => $query->clone()->where('status', 'submitted')->count(),
            'approved_reports' => $query->clone()->where('status', 'approved')->count(),
        ];

        return response()->json($summary);
    }

    /**
     * Trigger report generation via queue from the web interface
     */
    public function generateReport(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));

        GenerateMonthlyInventoryReportJob::dispatch($facilityId, $year, $month);

        return redirect()->back()->with('success', 'Report generation triggered successfully.');
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
