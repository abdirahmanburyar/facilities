<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'lost_quantity',
        'damaged_quantity',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'in_process',
        'dispatched_by',
        'dispatched_at',
        'quantity_on_order',
        'delivered',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
