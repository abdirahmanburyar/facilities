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
