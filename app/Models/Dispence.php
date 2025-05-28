<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispence extends Model
{
    protected $fillable = [
        'dispence_number',
        'dispence_date',
        'patient_name',
        'patient_phone',
        'facility_id',
        'dispenced_by',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function dispenced_by()
    {
        return $this->belongsTo(User::class, 'dispenced_by');
    }

    public function items()
    {
        return $this->hasMany(DispenceItem::class);
    }
}
