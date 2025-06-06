<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'barcode',
        'uom',
        'batch_number',
        'expire_date'
    ];
    
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function backorders()
    {
        return $this->hasMany(FacilityBackorder::class, 'transfer_item_id');
    }

}
