<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceStudent extends Model
{
    /**
     * Attendance status codes.
     */
    public const STATUS_PRESENT = 'P';
    public const STATUS_ABSENT = 'A';
    public const STATUS_LEAVE = 'L';
    public const STATUS_LATE = 'LT';

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

    protected $casts = [
        'check_in' => 'datetime:H:i:s',
        'check_out' => 'datetime:H:i:s',
    ];

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
        return $this->attendanceStatus?->code === self::STATUS_PRESENT;
    }

    /**
     * Check if this student was absent.
     */
    public function isAbsent(): bool
    {
        return $this->attendanceStatus?->code === self::STATUS_ABSENT;
    }

    /**
     * Check if this student was on leave.
     */
    public function isOnLeave(): bool
    {
        return $this->attendanceStatus?->code === self::STATUS_LEAVE || $this->studentLeave !== null;
    }

    /**
     * Check if this student was late.
     */
    public function isLate(): bool
    {
        return $this->attendanceStatus?->code === self::STATUS_LATE;
    }
}
