<?php

namespace App\Imports;

use App\Models\MohDispenseItem;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Carbon\Carbon;

class MohDispenseImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    protected $mohDispenseId;

    public function __construct($mohDispenseId)
    {
        $this->mohDispenseId = $mohDispenseId;
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

        // Validate source field - warn if it looks like product specifications
        $source = $row['source'] ?? '';
        if (!empty($source)) {
            $this->validateSourceField($source, $row['item']);
        }

        return new MohDispenseItem([
            'moh_dispense_id' => $this->mohDispenseId,
            'product_id' => $product->id,
            'source' => $source,
            'batch_no' => $row['batch_no'] ?? '',
            'expiry_date' => $this->parseDate($row['expiry_date']),
            'quantity' => (int) $row['quantity'],
            'dispense_date' => $this->parseDate($row['dispense_date']),
            'dispensed_by' => $row['dispensed_by'] ?? '',
        ]);
    }

    private function validateSourceField($source, $item)
    {
        // Check if source looks like product specifications instead of actual source
        $suspiciousPatterns = [
            '/\d+mg/',  // Dosage like "125mg/5ml"
            '/\d+ml/',  // Volume like "500ml"
            '/\d+cm/',  // Size like "10x10cm"
            '/\d+ply/', // Material like "3ply"
            '/\d+g/',   // Weight like "500g"
            '/\d+mm/',  // Measurement like "3mm"
            '/G\d+/',   // Gauge like "18G", "20G"
            '/\d+\/\d+/', // Suture sizes like "2/0", "3/0"
            '/sterile/', // Sterility info
            '/disposable/', // Type info
            '/surgical/', // Type info
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $source)) {
                \Log::warning("MOH Dispense Import: Source field may contain product specifications instead of source for item: {$item}. Source: {$source}");
                break;
            }
        }
    }

    public function rules(): array
    {
        return [
            'item' => 'required|string',
            'source' => 'nullable|string|max:255',
            'batch_no' => 'nullable|string|max:255',
            'expiry_date' => 'required|date',
            'quantity' => 'required|integer|min:1',
            'dispense_date' => 'required|date',
            'dispensed_by' => 'nullable|string|max:255',
        ];
    }

    private function parseDate($date)
    {
        if (is_numeric($date)) {
            // Handle Excel date serial numbers
            return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d'));
        }
        
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: " . $date);
        }
    }
}
