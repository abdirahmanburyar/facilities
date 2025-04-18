<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use App\Events\OrderEvent;
use App\Models\Facility;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Approval;
use App\Models\Warehouse;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_type',
        'facility_id',
        'status',
        'order_number',
        'order_date',
        'expected_date',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    // public function warehouse()
    // {
    //     return $this->belongsTo(Warehouse::class);
    // }
}
