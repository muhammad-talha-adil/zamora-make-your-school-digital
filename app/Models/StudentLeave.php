<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentLeave extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'description',
        'status',
        'applied_by',
        'applied_at',
        'approved_by',
        'decided_at',
        'decision_note',
        'attachment_path',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applied_at' => 'datetime',
        'decided_at' => 'datetime',
        'status' => LeaveStatus::class,
    ];

    /**
     * Get the student that requested this leave.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the type of this leave.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the user who approved this leave.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get attendance records associated with this leave.
     */
    public function attendanceStudents(): HasMany
    {
        return $this->hasMany(AttendanceStudent::class);
    }

    /**
     * Get the number of days for this leave.
     */
    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Scope for pending leaves.
     */
    public function scopePending($query)
    {
        return $query->where('status', LeaveStatus::PENDING);
    }

    /**
     * Scope for approved leaves.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', LeaveStatus::APPROVED);
    }

    /**
     * Scope for rejected leaves.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', LeaveStatus::REJECTED);
    }

    /**
     * Check if this leave is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === LeaveStatus::APPROVED;
    }

    /**
     * Check if this leave is pending.
     */
    public function isPending(): bool
    {
        return $this->status === LeaveStatus::PENDING;
    }
}
