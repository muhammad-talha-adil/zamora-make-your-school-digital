<?php

namespace App\Services\Attendance;

use App\Models\AttendancePolicy;
use App\Models\AttendanceSummary;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentLateArrivalFine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Charging for repeated late arrival — where a school does that.
 *
 * Most schools here do not, so this does nothing at all until a campus turns it
 * on. That is the point of it being a policy: a fine nobody asked for,
 * appearing on a parent's voucher, is worse than no feature.
 *
 * Nobody is charged for being late once. The charge is for a habit, so a school
 * says how many are forgiven each month, what each one past that costs, and —
 * because a difficult month should not produce a bill out of all proportion to
 * it — the most that may be charged in one.
 *
 * The figure is **recomputed** from the month's summary rather than added to,
 * so a corrected register corrects the charge with it. Once the fee run has
 * billed it, it is left alone: a charge a parent has already been given is not
 * rewritten behind them.
 */
class LateArrivalFineService
{
    /**
     * Works out one student's charge for a month.
     */
    public function computeFor(int $studentId, int $sessionId, int $month, int $year): ?StudentLateArrivalFine
    {
        $enrollment = StudentEnrollmentRecord::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->orderByDesc('admission_date')
            ->first();

        if (! $enrollment) {
            return null;
        }

        $policy = AttendancePolicy::resolve($enrollment->campus_id, $sessionId);

        if (! $policy->late_fine_enabled) {
            return null;
        }

        $existing = StudentLateArrivalFine::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        // A charge already on a voucher, or deliberately waived, is left alone.
        if ($existing && $existing->status !== StudentLateArrivalFine::STATUS_PENDING) {
            return $existing;
        }

        $summary = AttendanceSummary::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $lateCount = (int) ($summary?->late_count ?? 0);
        $charged = max($lateCount - (int) $policy->late_fine_grace_count, 0);
        $amount = round($charged * (float) $policy->late_fine_amount, 2);

        if ($policy->late_fine_monthly_cap !== null) {
            $amount = min($amount, (float) $policy->late_fine_monthly_cap);
        }

        return StudentLateArrivalFine::updateOrCreate(
            [
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'month' => $month,
                'year' => $year,
            ],
            [
                'late_count' => $lateCount,
                'charged_count' => $charged,
                'amount' => $amount,
                'status' => StudentLateArrivalFine::STATUS_PENDING,
                'computed_at' => now(),
            ]
        );
    }

    /**
     * Works out a whole month, for every child who was late in it.
     *
     * @return array{computed: int, charged: int, total: float}
     */
    public function computeMonth(int $sessionId, int $month, int $year): array
    {
        $summaries = AttendanceSummary::query()
            ->where('session_id', $sessionId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('late_count', '>', 0)
            ->get();

        $computed = 0;
        $charged = 0;
        $total = 0.0;

        foreach ($summaries as $summary) {
            $fine = $this->computeFor($summary->student_id, $sessionId, $month, $year);

            if (! $fine) {
                continue;
            }

            $computed++;

            if ((float) $fine->amount > 0) {
                $charged++;
                $total += (float) $fine->amount;
            }
        }

        return ['computed' => $computed, 'charged' => $charged, 'total' => round($total, 2)];
    }

    /**
     * Forgives a charge, with the reason recorded.
     *
     * The count stays: what is being said is "this happened and we are not
     * charging for it", which is a different statement from "this did not
     * happen".
     */
    public function waive(StudentLateArrivalFine $fine, string $reason): StudentLateArrivalFine
    {
        $fine->update([
            'status' => StudentLateArrivalFine::STATUS_WAIVED,
            'waiver_reason' => $reason,
        ]);

        return $fine->fresh();
    }

    /**
     * The charges a fee run should put on this month's vouchers.
     *
     * @return Collection<int, StudentLateArrivalFine>
     */
    public function billableFor(int $studentId, int $sessionId, Carbon $upTo)
    {
        return StudentLateArrivalFine::query()
            ->billable()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where(function ($query) use ($upTo) {
                $query->where('year', '<', $upTo->year)
                    ->orWhere(function ($q) use ($upTo) {
                        $q->where('year', $upTo->year)->where('month', '<=', $upTo->month);
                    });
            })
            ->get();
    }
}
