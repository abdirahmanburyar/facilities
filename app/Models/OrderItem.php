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
        'warehouse_id',
        'quantity_on_order',
        'qer',
        'quantity_to_release',
        'received_quantity',
        'no_of_days',
        'days',
    ];

    public function inventory_allocations(){
        return $this->hasMany(InventoryAllocation::class, 'order_item_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    /**
     * Get the back orders for this order item
     */
    public function backorders()
    {
        return $this->hasMany(FacilityBackorder::class, 'order_item_id');
    }
}
