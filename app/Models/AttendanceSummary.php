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
        'half_day_count',
        'total_days',
        'expected_days',
        'present_equivalent',
        'computed_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'present_count' => 'integer',
        'absent_count' => 'integer',
        'leave_count' => 'integer',
        'late_count' => 'integer',
        'half_day_count' => 'integer',
        'total_days' => 'integer',
        'expected_days' => 'integer',
        'present_equivalent' => 'float',
        'computed_at' => 'datetime',
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
     * The attendance percentage a report card prints.
     *
     * Measured against the days the child was **expected**, not the days
     * somebody happened to mark: a child marked on three days out of twenty-two
     * used to read as 100%. Weighted too, so a half day counts as half.
     *
     * Falls back to the marked days when the expected figure has not been
     * computed — an old row from before the summary was rebuildable — so the
     * number is never divided by nothing.
     */
    public function getAttendancePercentageAttribute(): float
    {
        $expected = $this->expected_days ?: $this->total_days;

        if (! $expected) {
            return 0.0;
        }

        $present = $this->present_equivalent ?: $this->present_count;

        return round(($present / $expected) * 100, 2);
    }

    /**
     * Days the child was expected but no register records them either way.
     *
     * A class whose register was never taken shows here rather than quietly
     * flattering everyone's percentage.
     */
    public function getUnmarkedDaysAttribute(): int
    {
        return max((int) $this->expected_days - (int) $this->total_days, 0);
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
