<?php

namespace App\Models;

use App\Enums\AttendanceStatusCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceStudent extends Model
{
    protected $fillable = [
        'attendance_id',
        'student_id',
        'attendance_status_id',
        'student_leave_id',
        'leave_type_id',
        'check_in',
        'check_out',
        'remarks',
    ];

    /**
     * `check_in` and `check_out` are `TIME` columns and are left as strings.
     *
     * They were cast to datetimes, which gave a clock time an arbitrary date
     * part — Eloquent then wrote a full `Y-m-d H:i:s` back into a column that
     * holds only a time. A time of day is not a moment in history, and nothing
     * in the module wants the date half; the JSON the screens receive is the
     * same `HH:MM:SS` either way.
     */
    protected $casts = [];

    /**
     * Get the attendance session for this student record.
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the student for this attendance record.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the attendance status for this record.
     */
    public function attendanceStatus(): BelongsTo
    {
        return $this->belongsTo(AttendanceStatus::class);
    }

    /**
     * Get the student leave associated with this record (if any).
     */
    public function studentLeave(): BelongsTo
    {
        return $this->belongsTo(StudentLeave::class);
    }

    /**
     * Get the leave type for this attendance record (if leave status).
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Check if this student was present.
     */
    public function isPresent(): bool
    {
        return (bool) $this->attendanceStatus?->hasCode(AttendanceStatusCode::PRESENT);
    }

    /**
     * Check if this student was absent.
     */
    public function isAbsent(): bool
    {
        return (bool) $this->attendanceStatus?->hasCode(AttendanceStatusCode::ABSENT);
    }

    /**
     * Check if this student was on leave.
     */
    public function isOnLeave(): bool
    {
        return $this->attendanceStatus?->hasCode(AttendanceStatusCode::LEAVE) || $this->studentLeave !== null;
    }

    /**
     * Check if this student was late.
     */
    public function isLate(): bool
    {
        return (bool) $this->attendanceStatus?->hasCode(AttendanceStatusCode::LATE);
    }
}
