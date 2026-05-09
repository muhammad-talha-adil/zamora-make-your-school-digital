<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campus_id',
        'transport_vehicle_id',
        'name',
        'monthly_fee',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }

    public function stops(): BelongsToMany
    {
        return $this->belongsToMany(TransportStop::class, 'transport_route_stops')
            ->withPivot('sort_order')
            ->orderBy('transport_route_stops.sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TransportStudentAssignment::class);
    }
}
