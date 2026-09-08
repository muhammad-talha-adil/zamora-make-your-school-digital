<?php

namespace App\Models\Fee;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StudentPaidOneTimeFee Model
 *
 * Tracks which one-time fees (like Admission Fee, Enrollment Fee)
 * have been paid by each student.
 * This prevents duplicate charges for readmitted students.
 */
class StudentPaidOneTimeFee extends Model
{
    protected $table = 'student_paid_one_time_fees';

    protected $fillable = [
        'student_id',
        'fee_head_id',
        'amount_paid',
        'refunded_amount',
        'payment_date',
        'refunded_at',
        'refund_reason',
        'refunded_by',
        'voucher_id',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'payment_date' => 'date',
        'refunded_at' => 'date',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the fee head
     */
    public function feeHead(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class);
    }

    /**
     * Get the voucher (if paid through voucher)
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class);
    }

    /**
     * What is still with the school out of what was paid.
     */
    public function amountHeld(): float
    {
        return round((float) $this->amount_paid - (float) $this->refunded_amount, 2);
    }

    public function isFullyRefunded(): bool
    {
        return $this->amountHeld() <= 0;
    }

    /**
     * Payments the school is still holding.
     *
     * A refunded admission fee stops standing as proof of payment: if the child
     * comes back, they are admitted afresh and charged afresh. Without this the
     * refund would quietly buy them a free readmission.
     */
    public function scopeNotRefunded($query)
    {
        return $query->whereColumn('refunded_amount', '<', 'amount_paid');
    }

    /**
     * Check if a student has paid a specific one-time fee
     */
    public static function hasPaid(int $studentId, int $feeHeadId): bool
    {
        return self::where('student_id', $studentId)
            ->where('fee_head_id', $feeHeadId)
            ->notRefunded()
            ->exists();
    }

    /**
     * Record a one-time fee payment
     */
    public static function recordPayment(
        int $studentId,
        int $feeHeadId,
        float $amount,
        ?int $voucherId = null,
        ?string $notes = null
    ): self {
        return self::create([
            'student_id' => $studentId,
            'fee_head_id' => $feeHeadId,
            'amount_paid' => $amount,
            'payment_date' => now()->toDateString(),
            'voucher_id' => $voucherId,
            'notes' => $notes,
        ]);
    }
}
