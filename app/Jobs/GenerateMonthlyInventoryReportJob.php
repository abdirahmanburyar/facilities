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
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateMonthlyInventoryReportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Set to 2 hours for large facilities with many products
     */
    public $timeout = 7200;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public $failOnTimeout = true;

    /**
     * The unique ID of the job.
     * This prevents multiple reports for the same facility/period from running simultaneously
     */
    public function uniqueId(): string
    {
        return "monthly-report-{$this->facilityId}-{$this->year}-{$this->month}";
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public $uniqueFor = 7200; // 2 hours

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
        // Set memory limit for large datasets
        ini_set('memory_limit', '1024M');
        
        // Log job start with memory usage
        Log::info("Starting monthly inventory report generation", [
            'facility_id' => $this->facilityId,
            'year' => $this->year,
            'month' => $this->month,
            'memory_usage' => memory_get_usage(true),
            'job_id' => $this->job->getJobId() ?? 'sync'
        ]);

        try {
            DB::transaction(function () {
                $facilities = $this->facilityId ? 
                    Facility::where('id', $this->facilityId)->get() : 
                    Facility::where('is_active', true)->get();

                Log::info("Processing {$facilities->count()} facilities for report generation");

                foreach ($facilities as $facility) {
                    $this->generateReportForFacility($facility);
                    
                    // Force garbage collection after each facility to manage memory
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                    
                    // Log memory usage after each facility
                    Log::info("Completed facility {$facility->id}, memory usage: " . memory_get_usage(true));
                }
            });
            
            Log::info("Monthly inventory report generation completed successfully", [
                'facility_id' => $this->facilityId,
                'year' => $this->year,
                'month' => $this->month,
                'peak_memory' => memory_get_peak_usage(true)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Monthly inventory report generation failed", [
                'facility_id' => $this->facilityId,
                'year' => $this->year,
                'month' => $this->month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => memory_get_usage(true)
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate report for a facility
     */
    private function generateReportForFacility(Facility $facility): void
    {
        Log::info("Processing facility: {$facility->name} (ID: {$facility->id})");
        
        // Check if report already exists for this facility and period
        $existingReport = FacilityMonthlyReport::where([
            'facility_id' => $facility->id,
            'report_period' => $this->reportPeriod,
        ])->first();

        if ($existingReport && !$this->force) {
            Log::info("Report already exists for facility {$facility->id}, skipping");
            return;
        }

        // Create or get the report header
        if ($existingReport) {
            $report = $existingReport;
            Log::info("Deleting existing items for facility {$facility->id}");
            $report->items()->delete();
        } else {
            Log::info("Creating new report for facility {$facility->id}");
            $report = FacilityMonthlyReport::create([
                'facility_id' => $facility->id,
                'report_period' => $this->reportPeriod,
                'status' => 'draft',
            ]);
        }

        // Generate report items from movement data
        $this->generateReportItems($facility, $report);
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

        // Get all aggregated movement data first (since GROUP BY conflicts with chunk)
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

        // Process in chunks to manage memory usage
        $chunkSize = 100;
        $totalProcessed = 0;
        $itemsCreated = 0;

        // Use Laravel collection chunking instead of database chunking
        $movementData->chunk($chunkSize)->each(function ($movementChunk, $chunkIndex) use ($facility, $report, &$totalProcessed, &$itemsCreated) {
            Log::info("Processing chunk " . ($chunkIndex + 1) . " with {$movementChunk->count()} products for facility {$facility->id}");

            foreach ($movementChunk as $movement) {
                try {
                    // Get product information (use select to minimize memory usage)
                    $product = Product::select(['id', 'name'])->find($movement->product_id);
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
                    $totalProcessed++;

                    // Log progress every 50 items to avoid log spam
                    if ($itemsCreated % 50 == 0) {
                        Log::info("Created {$itemsCreated} report items for facility {$facility->id}");
                    }

                } catch (\Exception $e) {
                    Log::error("Error processing movement for product {$movement->product_id} in facility {$facility->id}: " . $e->getMessage());
                    throw $e;
                }
            }

            // Force garbage collection after each chunk to manage memory
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            Log::info("Completed chunk " . ($chunkIndex + 1) . ": processed {$movementChunk->count()} products, created {$itemsCreated} items total");
        });

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
        Log::error("Monthly inventory report job failed permanently", [
            'facility_id' => $this->facilityId,
            'year' => $this->year,
            'month' => $this->month,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'memory_usage' => memory_get_usage(true)
        ]);
        
        // Clean up any partially created reports
        $this->cleanupPartialReports();
    }

    /**
     * Clean up any partially created reports on failure
     */
    private function cleanupPartialReports(): void
    {
        try {
            $facilities = $this->facilityId ? 
                collect([Facility::find($this->facilityId)])->filter() : 
                Facility::where('is_active', true)->get();

            foreach ($facilities as $facility) {
                // Find and delete incomplete reports (reports without items)
                $incompleteReports = FacilityMonthlyReport::where([
                    'facility_id' => $facility->id,
                    'year' => $this->year,
                    'month' => $this->month
                ])->whereDoesntHave('items')->get();

                foreach ($incompleteReports as $report) {
                    Log::info("Cleaning up incomplete report {$report->id} for facility {$facility->id}");
                    $report->delete();
                }
            }
        } catch (\Exception $e) {
            Log::error("Error during cleanup: " . $e->getMessage());
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        // Don't retry after 4 hours total
        return now()->addHours(4);
    }
}
