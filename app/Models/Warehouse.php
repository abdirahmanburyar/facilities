<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'capacity',
        'temperature_min',
        'temperature_max',
        'humidity_min',
        'humidity_max',
        'status',
        'has_cold_storage',
        'has_hazardous_storage',
        'is_active',
        'manager_name',
        'manager_email',
        'manager_phone',
        'notes'
    ];
}
