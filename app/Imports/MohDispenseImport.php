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
use Carbon\Carbon;

class MohDispenseImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnError
{
    use Importable, SkipsErrors;

    protected $mohDispenseId;

    public function __construct($mohDispenseId)
    {
        $this->mohDispenseId = $mohDispenseId;
    }

    public function chunkSize(): int
    {
        return 1000; // Process 1000 rows at a time
    }

    public function model(array $row)
    {
        // Find product by name or ID
        $product = Product::where('name', $row['item'])
            ->orWhere('id', $row['item'])
            ->orWhere('productID', $row['item'])
            ->first();

        if (!$product) {
            throw new \Exception("Product not found: " . $row['item']);
        }

        // Debug date values
        \Log::info('Processing row dates:', [
            'expiry_date_raw' => $row['expiry_date'] ?? 'not set',
            'dispense_date_raw' => $row['dispense_date'] ?? 'not set',
            'expiry_date_type' => gettype($row['expiry_date'] ?? null),
            'dispense_date_type' => gettype($row['dispense_date'] ?? null),
        ]);

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
