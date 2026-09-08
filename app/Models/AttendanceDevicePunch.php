<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One read of a thumb or a card, exactly as the machine reported it.
 *
 * Never rewritten. A punch the system could not match is not an error to be
 * cleared away — it is the evidence that a card is unregistered, or that
 * somebody used one that is not theirs.
 */
class AttendanceDevicePunch extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_UNMATCHED = 'unmatched';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'device_id',
        'identifier',
        'punched_at',
        'direction',
        'student_id',
        'staff_profile_id',
        'status',
        'note',
        'processed_at',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnmatched($query)
    {
        return $query->where('status', self::STATUS_UNMATCHED);
    }
}
