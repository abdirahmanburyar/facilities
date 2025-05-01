<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reorder_level',
        'manufacturing_date',
        'expiry_date',
        'batch_number',
        'location',
        'notes',
        'is_active',
    ];
}
