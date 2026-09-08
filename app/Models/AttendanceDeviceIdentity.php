<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a machine calls a person.
 *
 * A scanner knows a thumb by its own enrolment number and a card by the number
 * printed on it, and neither is the student's registration number — so the two
 * are tied together once, here.
 *
 * A lost card is deactivated rather than deleted: the punches it made are still
 * the record of who came through the gate and when.
 */
class AttendanceDeviceIdentity extends Model
{
    protected $fillable = [
        'student_id',
        'staff_profile_id',
        'identifier',
        'identity_type',
        'issued_on',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
