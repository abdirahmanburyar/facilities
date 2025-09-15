<?php

namespace App\Jobs;

use App\Models\MohDispense;
use App\Imports\MohDispenseImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessMohDispenseImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mohDispenseId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($mohDispenseId, $filePath)
    {
        $this->mohDispenseId = $mohDispenseId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $mohDispense = MohDispense::findOrFail($this->mohDispenseId);
            
            // Update status to processing
            $mohDispense->update(['status' => 'processing']);

            // Process the Excel file
            $import = new MohDispenseImport($this->mohDispenseId);
            Excel::import($import, Storage::disk('public')->path($this->filePath));

            // Update status to processed
            $mohDispense->update(['status' => 'processed']);

            // Clean up the temporary file
            Storage::disk('public')->delete($this->filePath);

        } catch (\Exception $e) {
            // Update status to failed
            $mohDispense = MohDispense::find($this->mohDispenseId);
            if ($mohDispense) {
                $mohDispense->update(['status' => 'failed']);
            }

            // Clean up the temporary file
            if (Storage::disk('public')->exists($this->filePath)) {
                Storage::disk('public')->delete($this->filePath);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $mohDispense = MohDispense::find($this->mohDispenseId);
        if ($mohDispense) {
            $mohDispense->update(['status' => 'failed']);
        }

        // Clean up the temporary file
        if (Storage::disk('public')->exists($this->filePath)) {
            Storage::disk('public')->delete($this->filePath);
        }
    }
}
