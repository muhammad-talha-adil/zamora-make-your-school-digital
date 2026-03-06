<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    protected $fillable = [
        'student_id',
        'session_id',
        'month',
        'year',
        'present_count',
        'absent_count',
        'leave_count',
        'late_count',
        'total_days',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'present_count' => 'integer',
        'absent_count' => 'integer',
        'leave_count' => 'integer',
        'late_count' => 'integer',
        'total_days' => 'integer',
    ];

    /**
     * Get the student for this summary.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the session for this summary.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Get the attendance percentage.
     */
    public function getAttendancePercentageAttribute(): float
    {
        if ($this->total_days === 0) {
            return 0;
        }

        return round(($this->present_count / $this->total_days) * 100, 2);
    }

    /**
     * Scope for a specific month and year.
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope for a specific session.
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
