<?php

namespace App\Services\Fee;

use App\Models\Fee\FeePolicy;
use App\Models\Fee\StudentPaidOneTimeFee;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Giving back an admission or registration charge when a child leaves at once.
 *
 * `student_paid_one_time_fees` recorded that the charge was paid and must never
 * be billed again, which is right, but there was no way to say the money went
 * back. A child who left a fortnight after admission kept a paid admission fee
 * on record for ever and the office had nothing to reconcile the cash against.
 *
 * How long the window is, is the school's own decision and lives on the campus
 * fee policy. Zero days — the default — means these charges are never refunded,
 * which is how the system behaved before this existed.
 */
class OneTimeFeeRefundService
{
    /**
     * The charges that could be given back if this child left on this date.
     *
     * @return Collection<int, StudentPaidOneTimeFee>
     */
    public function refundableFor(StudentEnrollmentRecord $enrollment, ?Carbon $leftOn = null): Collection
    {
        $policy = FeePolicy::resolve($enrollment->campus_id, $enrollment->session_id);
        $window = (int) ($policy->one_time_refund_days ?? 0);

        if ($window <= 0) {
            return collect();
        }

        $admittedOn = $enrollment->admission_date
            ? Carbon::parse($enrollment->admission_date)
            : null;

        if (! $admittedOn) {
            return collect();
        }

        $leftOn ??= $enrollment->leave_date ? Carbon::parse($enrollment->leave_date) : now();

        // Counted from admission, not from the payment: a family that paid late
        // does not thereby get a longer window than one that paid on the day.
        if ($admittedOn->diffInDays($leftOn, absolute: false) > $window) {
            return collect();
        }

        return StudentPaidOneTimeFee::query()
            ->where('student_id', $enrollment->student_id)
            ->notRefunded()
            ->where('payment_date', '>=', $admittedOn->toDateString())
            ->with('feeHead')
            ->get();
    }

    /**
     * Gives back every refundable charge, and says what was returned.
     *
     * @return array{refunded: int, amount: float}
     */
    public function refundAll(
        StudentEnrollmentRecord $enrollment,
        string $reason,
        ?int $refundedBy = null,
        ?Carbon $leftOn = null
    ): array {
        $refundable = $this->refundableFor($enrollment, $leftOn);

        if ($refundable->isEmpty()) {
            return ['refunded' => 0, 'amount' => 0.0];
        }

        return DB::transaction(function () use ($refundable, $reason, $refundedBy, $leftOn) {
            $amount = 0.0;

            foreach ($refundable as $payment) {
                $amount += $this->refund($payment, $payment->amountHeld(), $reason, $refundedBy, $leftOn);
            }

            return ['refunded' => $refundable->count(), 'amount' => round($amount, 2)];
        });
    }

    /**
     * Gives back part or all of one charge.
     *
     * The payment row stays as the record of what was taken; the refund is
     * written alongside it. Once the whole amount is back the charge stops
     * counting as settled, so a child who returns is charged afresh — otherwise
     * the refund would quietly buy them a free readmission.
     *
     * @return float the amount actually returned
     */
    public function refund(
        StudentPaidOneTimeFee $payment,
        float $amount,
        string $reason,
        ?int $refundedBy = null,
        ?Carbon $refundedAt = null
    ): float {
        $amount = round(min($amount, $payment->amountHeld()), 2);

        if ($amount <= 0) {
            return 0.0;
        }

        $payment->update([
            'refunded_amount' => round((float) $payment->refunded_amount + $amount, 2),
            'refunded_at' => ($refundedAt ?? now())->toDateString(),
            'refund_reason' => $reason,
            'refunded_by' => $refundedBy ?? auth()->id(),
        ]);

        return $amount;
    }
}
