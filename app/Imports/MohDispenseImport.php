<?php

namespace App\Imports;

use App\Models\MohDispenseItem;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;

class MohDispenseImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnError, WithEvents
{
    use Importable, SkipsErrors;

    protected $mohDispenseId;
    protected $products = [];
    protected $processedCount = 0;

    public function __construct($mohDispenseId)
    {
        $this->mohDispenseId = $mohDispenseId;
    }

    public function chunkSize(): int
    {
        return 50; // Process 50 rows at a time to prevent timeout issues
    }

    public function model(array $row)
    {
        try {
            // Validate required fields
            if (empty($row['item'])) {
                throw new \Exception("Item field is required");
            }

            // Get product from cache or database
            $product = $this->getProduct($row['item']);

            if (!$product) {
                throw new \Exception("Product not found: " . $row['item']);
            }

            // Track progress and log every 100 rows
            $this->processedCount++;
            if ($this->processedCount % 100 === 0) {
                logger()->info('Import progress:', [
                    'processed_rows' => $this->processedCount,
                    'moh_dispense_id' => $this->mohDispenseId
                ]);
            }

            // Debug date values (only log first few rows to avoid performance issues)
            static $rowCount = 0;
            if ($rowCount < 3) {
                logger()->info('Processing row:', [
                    'row' => $rowCount + 1,
                    'item' => $row['item'],
                    'expiry_date_raw' => $row['expiry_date'] ?? 'not set',
                    'dispense_date_raw' => $row['dispense_date'] ?? 'not set',
                    'quantity' => $row['quantity'] ?? 'not set',
                ]);
                $rowCount++;
            }

            return new MohDispenseItem([
                'moh_dispense_id' => $this->mohDispenseId,
                'product_id' => $product->id,
                'source' => $row['source'] ?? '',
                'batch_no' => $row['batch_no'] ?? '',
                'expiry_date' => $this->parseDate($row['expiry_date']),
                'quantity' => (int) $row['quantity'],
                'dispense_date' => $this->parseDate($row['dispense_date']),
                'dispensed_by' => $row['dispensed_by'] ?? '',
            ]);

        } catch (\Exception $e) {
            logger()->error('Error processing row:', [
                'row' => $row,
                'error' => $e->getMessage(),
                'moh_dispense_id' => $this->mohDispenseId
            ]);
            throw $e;
        }
    }


    public function rules(): array
    {
        return [
            'item' => 'required|string',
            'source' => 'nullable|string|max:255',
            'batch_no' => 'nullable|string|max:255',
            'expiry_date' => 'required',
            'quantity' => 'required|integer|min:1',
            'dispense_date' => 'required',
            'dispensed_by' => 'nullable|string|max:255',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                logger()->info('MOH Dispense import completed', [
                    'moh_dispense_id' => $this->mohDispenseId,
                    'total_errors' => $this->getErrors()->count(),
                    'errors' => $this->getErrors()->toArray()
                ]);
            },
        ];
    }

    private function getProduct($item)
    {
        // Check cache first
        if (isset($this->products[$item])) {
            return $this->products[$item];
        }

        // Find product in database
        $product = Product::where('name', $item)
            ->orWhere('id', $item)
            ->orWhere('productID', $item)
            ->first();

        // Cache the result
        $this->products[$item] = $product;

        return $product;
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            throw new \Exception("Date field is required");
        }

        // Handle Excel date serial numbers
        if (is_numeric($date)) {
            try {
                return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d'));
            } catch (\Exception $e) {
                // Fallback to manual calculation
                return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($date - 25569) * 86400));
            }
        }
        
        // Handle string dates - try multiple formats
        $dateString = trim($date);
        
        // Common date formats to try
        $formats = [
            'Y-m-d',           // 2025-12-31
            'd/m/Y',           // 31/12/2025
            'm/d/Y',           // 12/31/2025
            'd-m-Y',           // 31-12-2025
            'm-d-Y',           // 12-31-2025
            'Y/m/d',           // 2025/12/31
            'd.m.Y',           // 31.12.2025
            'm.d.Y',           // 12.31.2025
            'Y.m.d',           // 2025.12.31
        ];

        foreach ($formats as $format) {
            try {
                $parsedDate = Carbon::createFromFormat($format, $dateString);
                if ($parsedDate) {
                    return $parsedDate;
                }
            } catch (\Exception $e) {
                // Continue to next format
                continue;
            }
        }

        // Try Carbon's flexible parsing as last resort
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Unable to parse date: {$date}. Please use format YYYY-MM-DD (e.g., 2025-12-31)");
        }
    }
}
