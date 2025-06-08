<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacilityInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'facility_id',
        'quantity',
        'barcode',
        'expiry_date',
        'uom',
        'batch_number',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
