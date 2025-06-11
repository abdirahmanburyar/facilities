<?php

namespace App\Jobs;

use App\Models\Facility;
use App\Models\Product;
use App\Models\FacilityMonthlyReport;
use App\Models\FacilityMonthlyReportItem;
use App\Models\FacilityInventoryMovement;
use App\Models\FacilityInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateMonthlyInventoryReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes timeout
    public $tries = 3;

    protected $facilityId;
    protected $year;
    protected $month;
    protected $reportPeriod;
    protected $force;

    /**
     * Create a new job instance.
     */
    public function __construct($facilityId = null, $year = null, $month = null, $force = false)
    {
        $this->facilityId = $facilityId;
        $this->year = $year ?? now()->year;
        $this->month = $month ?? now()->month;
        $this->force = $force;
        $this->reportPeriod = $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting monthly inventory report generation job", [
            'facility_id' => $this->facilityId,
            'report_period' => $this->reportPeriod,
            'force' => $this->force
        ]);

        try {
            DB::transaction(function () {
                $facilities = $this->facilityId 
                    ? Facility::where('id', $this->facilityId)->get()
                    : Facility::all();

                $totalReports = 0;
                $createdReports = 0;
                $updatedReports = 0;

                foreach ($facilities as $facility) {
                    Log::info("Processing facility: {$facility->name} (ID: {$facility->id})");
                    
                    // Check if report already exists for this facility and period
                    $existingReport = FacilityMonthlyReport::where([
                        'facility_id' => $facility->id,
                        'report_period' => $this->reportPeriod,
                    ])->first();

                    if ($existingReport && !$this->force) {
                        Log::info("Report already exists for facility {$facility->id}, skipping");
                        continue;
                    }

                    // Create or get the report header
                    if ($existingReport) {
                        $report = $existingReport;
                        Log::info("Deleting existing items for facility {$facility->id}");
                        $report->items()->delete();
                        $updatedReports++;
                    } else {
                        Log::info("Creating new report for facility {$facility->id}");
                        $report = FacilityMonthlyReport::create([
                            'facility_id' => $facility->id,
                            'report_period' => $this->reportPeriod,
                            'status' => 'draft',
                        ]);
                        $createdReports++;
                    }

                    // Generate report items from movement data
                    $this->generateReportItems($facility, $report);
                    $totalReports++;
                }

                Log::info("Monthly inventory report generation completed successfully", [
                    'total_processed' => $totalReports,
                    'created' => $createdReports,
                    'updated' => $updatedReports,
                    'facilities_count' => $facilities->count()
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Error generating monthly inventory reports", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate report items from facility inventory movements
     */
    private function generateReportItems(Facility $facility, FacilityMonthlyReport $report): void
    {
        Log::info("Generating report items from movements for facility {$facility->id}");

        // Define the date range for the report month
        $startDate = Carbon::createFromFormat('Y-m', $this->reportPeriod)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $this->reportPeriod)->endOfMonth();

        Log::info("Date range: {$startDate} to {$endDate}");

        // Get aggregated movement data for this facility and period
        $movementData = FacilityInventoryMovement::select([
                'product_id',
                DB::raw('SUM(COALESCE(facility_received_quantity, 0)) as total_received'),
                DB::raw('SUM(COALESCE(facility_issued_quantity, 0)) as total_issued'),
                DB::raw('COUNT(*) as movement_count')
            ])
            ->where('facility_id', $facility->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('product_id')
            ->get();

        Log::info("Found {$movementData->count()} products with movements for facility {$facility->id}");

        $itemsCreated = 0;

        foreach ($movementData as $movement) {
            try {
                // Get product information
                $product = Product::find($movement->product_id);
                if (!$product) {
                    Log::warning("Product {$movement->product_id} not found, skipping");
                    continue;
                }

                // Calculate opening balance (closing balance from previous month or current inventory)
                $openingBalance = $this->calculateOpeningBalance($facility, $product);

                // Get the aggregated quantities
                $stockReceived = (float) $movement->total_received;
                $stockIssued = (float) $movement->total_issued;

                // Set default adjustments (can be manually updated later)
                $positiveAdjustments = 0.0;
                $negativeAdjustments = 0.0;

                // Calculate closing balance
                $closingBalance = $openingBalance + $stockReceived - $stockIssued + $positiveAdjustments - $negativeAdjustments;

                // Calculate stockout days (simplified)
                $stockoutDays = $this->calculateStockoutDays($facility, $product);

                // Create the report item
                FacilityMonthlyReportItem::create([
                    'parent_id' => $report->id,
                    'product_id' => $product->id,
                    'opening_balance' => $openingBalance,
                    'stock_received' => $stockReceived,
                    'stock_issued' => $stockIssued,
                    'positive_adjustments' => $positiveAdjustments,
                    'negative_adjustments' => $negativeAdjustments,
                    'closing_balance' => $closingBalance,
                    'stockout_days' => $stockoutDays,
                ]);

                $itemsCreated++;

                if ($itemsCreated % 50 == 0) {
                    Log::info("Created {$itemsCreated} report items for facility {$facility->id}");
                }

            } catch (\Exception $e) {
                Log::error("Error processing movement for product {$movement->product_id} in facility {$facility->id}: " . $e->getMessage());
                throw $e;
            }
        }

        Log::info("Completed facility {$facility->id}: Created {$itemsCreated} items from movement data");
    }

    /**
     * Calculate opening balance from previous month's closing balance or current inventory
     */
    private function calculateOpeningBalance(Facility $facility, Product $product): float
    {
        // Get previous month
        $reportDate = Carbon::createFromFormat('Y-m', $this->reportPeriod);
        $prevMonth = $reportDate->copy()->subMonth()->format('Y-m');
        
        $previousReport = FacilityMonthlyReport::where([
            'facility_id' => $facility->id,
            'report_period' => $prevMonth,
        ])->first();

        if ($previousReport) {
            $previousReportItem = FacilityMonthlyReportItem::where([
                'parent_id' => $previousReport->id,
                'product_id' => $product->id,
            ])->first();

            return $previousReportItem ? (float) $previousReportItem->closing_balance : 0.0;
        }

        // If no previous report, get current inventory balance
        $inventory = FacilityInventory::where([
            'facility_id' => $facility->id,
            'product_id' => $product->id,
        ])->first();

        return $inventory && $inventory->quantity_available !== null ? (float) $inventory->quantity_available : 0.0;
    }

    /**
     * Calculate stockout days (simplified - can be enhanced with daily tracking)
     */
    private function calculateStockoutDays(Facility $facility, Product $product): int
    {
        // Simple calculation - check if current inventory is 0
        $inventory = FacilityInventory::where([
            'facility_id' => $facility->id,
            'product_id' => $product->id,
        ])->first();

        // If current inventory is 0 or doesn't exist, assume stockout for some days
        if (!$inventory || $inventory->quantity_available === null || $inventory->quantity_available <= 0) {
            return 5; // Assume 5 days of stockout as default
        }

        return 0;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Monthly inventory report generation job failed", [
            'facility_id' => $this->facilityId,
            'report_period' => $this->reportPeriod,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
