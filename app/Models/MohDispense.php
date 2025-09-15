<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MohDispense extends Model
{
    protected $fillable = [
        'moh_dispense_number',
        'facility_id',
        'created_by',
        'status',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mohDispense) {
            do {
                $lastDispense = self::whereDate('created_at', today())
                    ->latest('moh_dispense_number')
                    ->first();
                
                $number = $lastDispense ? (int)substr($lastDispense->moh_dispense_number, 12) + 1 : 1;
                $mohDispense->moh_dispense_number = 'MOH-DISP-' . date('Ymd') . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
            } while (self::where('moh_dispense_number', $mohDispense->moh_dispense_number)->exists());
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MohDispenseItem::class);
    }
}
