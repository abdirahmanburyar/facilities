<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAllocation extends Model
{
    protected $fillable = [
        'order_item_id',
        'product_id',
        'warehouse_id',
        'location_id',
        'batch_number',
        'expiry_date',
        'allocated_quantity',
        'allocation_type',
        'notes',
        'uom',
        'unit_cost',
        'total_cost'
    ];

    public function location(){
        return $this->belongsTo(Location::class);
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }

    public function backorders(){
        return $this->hasMany(FacilityBackorder::class, 'inventory_allocation_id');
    }
}
