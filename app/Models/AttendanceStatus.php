<?php

namespace App\Models;

use App\Enums\AttendanceStatusCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceStatus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'weight',
        'counts_as_expected',
        'sort_order',
        // Omitted before, so the column could never be set through the model
        // and a status could not be switched off.
        'is_active',
    ];

    protected $casts = [
        'weight' => 'float',
        'counts_as_expected' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all attendance records with this status.
     */
    public function attendanceStudents(): HasMany
    {
        return $this->hasMany(AttendanceStudent::class);
    }

    /**
     * Whether this row is one of the statuses the application reasons about.
     *
     * A school's own status matches nothing here and still works everywhere —
     * it is counted by its weight like any other.
     */
    public function hasCode(AttendanceStatusCode $code): bool
    {
        return $this->code === $code->value;
    }

    /**
     * The status this row is, or null for one the school added itself.
     */
    public function knownCode(): ?AttendanceStatusCode
    {
        return AttendanceStatusCode::tryFrom((string) $this->code);
    }

    /**
     * The share of a day a student on this status counts as present.
     *
     * Reading it from the row rather than testing the code means a school can
     * add "Short Leave" at 0.5 and every report follows, instead of the
     * arithmetic having to be edited in four places.
     */
    public function presentWeight(): float
    {
        return (float) ($this->weight ?? 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
