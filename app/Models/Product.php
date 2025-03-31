<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'description',
        'category_id',
        'dosage_id',
        'is_active',
    ];
}
