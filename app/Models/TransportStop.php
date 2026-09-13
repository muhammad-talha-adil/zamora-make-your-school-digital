<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportStop extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campus_id',
        'name',
        'pickup_time',
        'drop_time',
        'is_active',
    ];

    protected $casts = [
        'pickup_time' => 'datetime:H:i',
        'drop_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(TransportRoute::class, 'transport_route_stops')
            ->withPivot('sort_order');
    }

    /**
     * Narrows a list to the stops this user may see.
     *
     * A stop with no campus is a school-wide stop and stays visible to
     * anybody who may see transport at all.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $campusId = $user->campusId();

        if ($campusId === null) {
            return $query;
        }

        return $query->where(function ($outer) use ($campusId) {
            $outer->whereNull('campus_id')->orWhere('campus_id', $campusId);
        });
    }
}
