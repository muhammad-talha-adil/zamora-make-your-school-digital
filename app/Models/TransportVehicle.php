<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportVehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campus_id',
        'vehicle_no',
        'vehicle_type',
        'capacity',
        'driver_name',
        'attendant_name',
        'status',
        'purchase_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'purchase_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TransportVehicleExpense::class);
    }
}
