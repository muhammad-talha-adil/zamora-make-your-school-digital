<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A thumb scanner or card reader at a gate.
 */
class AttendanceDevice extends Model
{
    public const READS_IN = 'in';

    public const READS_OUT = 'out';

    public const READS_BOTH = 'both';

    protected $fillable = [
        'campus_id',
        'code',
        'name',
        'device_type',
        'location',
        'serial_no',
        'reads',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendanceDevicePunch::class, 'device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Which way a punch on this machine should be read.
     *
     * A gate machine records arrivals and an exit machine departures; one that
     * does both has to be told by the punch itself.
     */
    public function directionFor(?string $reported): ?string
    {
        return $this->reads === self::READS_BOTH ? $reported : $this->reads;
    }
}
