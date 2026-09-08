<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A month's late-arrival charge: worked out by attendance, billed by fee.
 *
 * The count and the working are kept beside the amount on purpose. When a
 * parent asks why there is Rs 300 on the voucher, the answer has to be "six
 * late arrivals, three forgiven, three at a hundred" — not a number nobody can
 * account for.
 */
class StudentLateArrivalFine extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_BILLED = 'billed';

    public const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'student_id',
        'session_id',
        'month',
        'year',
        'late_count',
        'charged_count',
        'amount',
        'status',
        'fee_voucher_item_id',
        'waiver_reason',
        'computed_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'late_count' => 'integer',
        'charged_count' => 'integer',
        'amount' => 'decimal:2',
        'computed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * A charge worth putting on a voucher.
     *
     * A waived one is not, and neither is one already billed — the fee run must
     * not charge the same month twice.
     */
    public function scopeBillable($query)
    {
        return $query->where('status', self::STATUS_PENDING)->where('amount', '>', 0);
    }

    /**
     * How the figure was arrived at, in the words the office would use.
     */
    public function explanation(): string
    {
        $forgiven = max($this->late_count - $this->charged_count, 0);

        return sprintf(
            '%d late arrival(s), %d forgiven, %d charged',
            $this->late_count,
            $forgiven,
            $this->charged_count
        );
    }
}
