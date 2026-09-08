<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The message a guardian is owed because their child was not in school.
 *
 * The row survives sending: it is the answer to "nobody told me", which is why
 * the text is kept as it went out rather than regenerated later from a template
 * that may since have changed.
 */
class AttendanceAbsenceAlert extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'student_id',
        'attendance_student_id',
        'absence_date',
        'guardian_id',
        'recipient_phone',
        'message',
        'status',
        'sent_at',
        'failure_reason',
    ];

    protected $casts = [
        'absence_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attendanceStudent(): BelongsTo
    {
        return $this->belongsTo(AttendanceStudent::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function markSent(): void
    {
        $this->update(['status' => self::STATUS_SENT, 'sent_at' => now(), 'failure_reason' => null]);
    }

    public function markFailed(string $reason): void
    {
        $this->update(['status' => self::STATUS_FAILED, 'failure_reason' => $reason]);
    }
}
