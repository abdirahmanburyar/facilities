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
        'facility_id',
        'warehouse_id',
        'user_id',
        'status',
        'order_number',
        'number_items',
        'notes',
        'order_date',
        'expected_date',
        'approved_at',
        'rejected_at',
        'processed_at',
        'dispatched_at',
        'delivered_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'processed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'order_date' => 'datetime',
        'expected_date' => 'datetime'
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
