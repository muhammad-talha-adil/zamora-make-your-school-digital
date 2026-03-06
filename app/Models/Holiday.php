<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Holiday extends Model
{
    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'campus_id',
        'is_national',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_national' => 'boolean',
    ];

    /**
     * Get the campus this holiday belongs to (nullable for national holidays).
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Check if this is a national holiday.
     */
    public function isNational(): bool
    {
        return $this->is_national;
    }

    /**
     * Get the number of days for this holiday.
     */
    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
