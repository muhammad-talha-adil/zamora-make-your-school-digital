<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'attendance_date',
        'campus_id',
        'session_id',
        'class_id',
        'section_id',
        'taken_by',
        'is_locked',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_locked' => 'boolean',
    ];

    /**
     * Get the campus for this attendance.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the session for this attendance.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Get the class for this attendance.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section for this attendance.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the user who took this attendance.
     */
    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    /**
     * Get all student attendance records for this attendance session.
     */
    public function attendanceStudents(): HasMany
    {
        return $this->hasMany(AttendanceStudent::class);
    }

    /**
     * Get the total number of students in this attendance session.
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->attendanceStudents()->count();
    }

    /**
     * Get the number of present students.
     */
    public function getPresentCountAttribute(): int
    {
        return $this->attendanceStudents()
            ->whereHas('attendanceStatus', function ($query) {
                $query->where('code', 'P');
            })->count();
    }

    /**
     * Get the number of absent students.
     */
    public function getAbsentCountAttribute(): int
    {
        return $this->attendanceStudents()
            ->whereHas('attendanceStatus', function ($query) {
                $query->where('code', 'A');
            })->count();
    }

    /**
     * Get the number of late students.
     */
    public function getLateCountAttribute(): int
    {
        return $this->attendanceStudents()
            ->whereHas('attendanceStatus', function ($query) {
                $query->where('code', 'LT');
            })->count();
    }

    /**
     * Scope for locked attendance records.
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope for unlocked attendance records.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Check if this attendance is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    /**
     * Lock this attendance record.
     */
    public function lock(): bool
    {
        return $this->update(['is_locked' => true]);
    }

    /**
     * Unlock this attendance record.
     */
    public function unlock(): bool
    {
        return $this->update(['is_locked' => false]);
    }
}
