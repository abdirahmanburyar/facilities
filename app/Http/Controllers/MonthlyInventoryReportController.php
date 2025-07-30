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
        
        // Note: Product filtering is handled on the frontend to filter items within the report

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
            'reports.*.stockout_days' => 'nullable|integer|min:0',
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
                // Check if this is an update (editing existing item) or creation (initial generation)
                $existingItem = FacilityMonthlyReportItem::where([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                ])->first();

                $data = [
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                    'opening_balance' => $reportData['opening_balance'],
                    'stock_received' => $reportData['stock_received'],
                    'stock_issued' => $reportData['stock_issued'],
                    'positive_adjustments' => $reportData['positive_adjustments'] ?? 0,
                    'negative_adjustments' => $reportData['negative_adjustments'] ?? 0,
                    'stockout_days' => $reportData['stockout_days'] ?? 0,
                ];

                // For new items (initial generation), calculate closing balance manually
                // For existing items (editing), let the model calculate automatically
                if (!$existingItem) {
                    $data['closing_balance'] = $reportData['opening_balance'] 
                        + $reportData['stock_received'] 
                        - $reportData['stock_issued'] 
                        + ($reportData['positive_adjustments'] ?? 0) 
                        - ($reportData['negative_adjustments'] ?? 0);
                }
                // Note: For existing items, closing_balance will be automatically calculated by model

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
            return redirect()->route('reports.monthly-reports.index')
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
     * Generate reports automatically from facility movements
     */
    public function generateReportFromMovements(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year');
        $month = $request->get('month');
        $reportPeriod = sprintf('%04d-%02d', $year, $month);

        try {
            // Get facility and check eligibility
            $facility = auth()->user()->facility;
            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found for current user.'
                ], 400);
            }

            // Create date range for the month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $previousMonth = $startDate->copy()->subMonth();

            // Check if report already exists
            $existingReport = FacilityMonthlyReport::where([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
            ])->first();

            if ($existingReport) {
                $monthName = $this->getMonthName($month);
                return response()->json([
                    'success' => false,
                    'message' => "Monthly inventory report for {$monthName} {$year} already exists. You cannot regenerate an existing report.",
                    'existing_report' => [
                        'id' => $existingReport->id,
                        'status' => $existingReport->status,
                        'period' => $reportPeriod,
                        'created_at' => $existingReport->created_at->format('Y-m-d H:i:s')
                    ]
                ], 400);
            }

            // Create the monthly report
            $monthlyReport = FacilityMonthlyReport::create([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
                'status' => 'draft',
            ]);

            // Get facility movements for the month grouped by product
            $movements = \App\Models\FacilityInventoryMovement::where('facility_id', $facilityId)
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->with('product')
                ->get()
                ->groupBy('product_id');

            // Get opening balances from previous month's closing balance
            $previousReportItems = FacilityMonthlyReportItem::whereHas('report', function($q) use ($facilityId, $previousMonth) {
                $q->where('facility_id', $facilityId)
                  ->where('report_period', $previousMonth->format('Y-m'));
            })->get()->keyBy('product_id');

            $createdCount = 0;
            $updatedCount = 0;
            
            foreach ($movements as $productId => $productMovements) {
                $product = $productMovements->first()->product;
                
                // Calculate opening balance from previous month or current inventory
                $openingBalance = 0;
                if (isset($previousReportItems[$productId])) {
                    $openingBalance = $previousReportItems[$productId]->closing_balance;
                } else {
                    // Get current facility inventory for this product as fallback
                    $currentInventory = \App\Models\FacilityInventory::where('facility_id', $facilityId)
                        ->whereHas('items', function($q) use ($productId) {
                            $q->where('product_id', $productId);
                        })
                        ->with(['items' => function($q) use ($productId) {
                            $q->where('product_id', $productId);
                        }])
                        ->first();
                    
                    if ($currentInventory && $currentInventory->items->count() > 0) {
                        $openingBalance = $currentInventory->items->sum('quantity');
                    }
                }

                // Calculate movements
                $stockReceived = $productMovements->where('movement_type', 'facility_received')->sum('facility_received_quantity');
                $stockIssued = $productMovements->where('movement_type', 'facility_issued')->sum('facility_issued_quantity');
                
                // Calculate closing balance
                $closingBalance = $openingBalance + $stockReceived - $stockIssued;

                // Create or update report item
                $item = FacilityMonthlyReportItem::updateOrCreate([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $productId,
                ], [
                    'opening_balance' => $openingBalance,
                    'stock_received' => $stockReceived,
                    'stock_issued' => $stockIssued,
                    'positive_adjustments' => 0,
                    'negative_adjustments' => 0,
                    'closing_balance' => $closingBalance,
                    'stockout_days' => 0, // This would need manual input or separate calculation
                ]);
                
                if ($item->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }

            // Also create empty items for products with no movements but are eligible
            $eligibleProducts = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
            $movementProductIds = $movements->keys()->toArray();
            
            foreach ($eligibleProducts as $product) {
                if (!in_array($product->id, $movementProductIds)) {
                    // Get opening balance from previous month
                    $openingBalance = 0;
                    if (isset($previousReportItems[$product->id])) {
                        $openingBalance = $previousReportItems[$product->id]->closing_balance;
                    }

                    $item = FacilityMonthlyReportItem::firstOrCreate([
                        'parent_id' => $monthlyReport->id,
                        'product_id' => $product->id,
                    ], [
                        'opening_balance' => $openingBalance,
                        'stock_received' => 0,
                        'stock_issued' => 0,
                        'positive_adjustments' => 0,
                        'negative_adjustments' => 0,
                        'closing_balance' => $openingBalance,
                        'stockout_days' => 0,
                    ]);
                    
                    if ($item->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            }

            $totalProducts = $createdCount + $updatedCount;
            $message = "Monthly report generated successfully from facility movements.";
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
                    'total_products' => $totalProducts,
                    'report_period' => $reportPeriod,
                    'facility_id' => $facilityId,
                    'movements_processed' => $movements->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly report generation from movements failed: ' . $e->getMessage(), [
                'facility_id' => $facilityId,
                'year' => $year,
                'month' => $month,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report from movements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger report generation via queue from the web interface (legacy - creates empty items)
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

            // Check if report already exists
            $existingReport = FacilityMonthlyReport::where([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
            ])->first();

            if ($existingReport) {
                $monthName = $this->getMonthName($month);
                return response()->json([
                    'success' => false,
                    'message' => "Monthly inventory report for {$monthName} {$year} already exists. You cannot regenerate an existing report.",
                    'existing_report' => [
                        'id' => $existingReport->id,
                        'status' => $existingReport->status,
                        'period' => $reportPeriod,
                        'created_at' => $existingReport->created_at->format('Y-m-d H:i:s')
                    ]
                ], 400);
            }

            // Create the monthly report
            $monthlyReport = FacilityMonthlyReport::create([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
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
