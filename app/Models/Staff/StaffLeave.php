<?php

namespace App\Models\Staff;

use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Leave a member of staff asked for.
 *
 * Halves are ordinary — a morning off for a hospital appointment — so `days` is
 * a decimal and not a count of dates.
 */
class StaffLeave extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'staff_profile_id', 'staff_leave_type_id', 'from_date', 'to_date', 'days',
        'reason', 'status', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'days' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<StaffLeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(StaffLeaveType::class, 'staff_leave_type_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Leave that actually happened, which is what payroll counts. */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /** Leave falling inside a span, for a payroll month or a year's balance. */
    public function scopeOverlapping($query, $from, $to)
    {
        return $query->whereDate('from_date', '<=', $to)->whereDate('to_date', '>=', $from);
    }
}
