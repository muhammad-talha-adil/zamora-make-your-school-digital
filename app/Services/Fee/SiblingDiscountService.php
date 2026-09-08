<?php

namespace App\Services\Fee;

use App\Models\Fee\FeePolicy;
use App\Models\Fee\FeeSiblingDiscountRule;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;

/**
 * The sibling concession, worked out rather than typed in.
 *
 * Siblings are already known through `student_guardians`, so "ten percent off
 * the second child" can be applied by the fee run instead of being entered
 * against each child by hand and then forgotten when one of them leaves.
 */
class SiblingDiscountService
{
    /**
     * Ranks worked out once per fee run, since a family's rank does not change
     * between two vouchers in the same batch.
     *
     * @var array<int, int>
     */
    private array $rankCache = [];

    /**
     * Where a child falls among the siblings currently at school.
     *
     * The eldest is first and pays in full. Rank is taken by date of birth, not
     * by admission date: a child admitted later is not thereby the younger one,
     * and a family that moves both children at once would otherwise have their
     * ranks decided by whichever form was filled in first.
     *
     * Only siblings with an open enrollment count, so the ranks move up by
     * themselves when the eldest finishes school.
     */
    public function rankOf(StudentEnrollmentRecord $enrollment): int
    {
        $studentId = (int) $enrollment->student_id;

        if (isset($this->rankCache[$studentId])) {
            return $this->rankCache[$studentId];
        }

        $guardianIds = $enrollment->student
            ?->guardians()
            ->pluck('guardians.id')
            ->all() ?? [];

        if (empty($guardianIds)) {
            return $this->rankCache[$studentId] = 1;
        }

        $siblings = Student::query()
            ->whereHas('guardians', fn ($q) => $q->whereIn('guardians.id', $guardianIds))
            ->whereHas('enrollmentRecords', fn ($q) => $q->whereNull('leave_date'))
            // Oldest first; the id settles twins and missing dates so the order
            // is the same on every run.
            ->orderByRaw('dob is null')
            ->orderBy('dob')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($siblings as $index => $siblingId) {
            $this->rankCache[(int) $siblingId] = $index + 1;
        }

        return $this->rankCache[$studentId] ?? 1;
    }

    /**
     * What the automatic sibling concession takes off one charge.
     *
     * Returns zero unless the campus has switched it on and written a rule for
     * this child's rank. The result competes with the concessions entered by
     * hand under the same "largest wins" rule, rather than stacking on them.
     */
    public function amountOff(StudentEnrollmentRecord $enrollment, int $feeHeadId, float $charge): float
    {
        $policy = FeePolicy::resolve($enrollment->campus_id, $enrollment->session_id);

        if (! $policy->sibling_discount_enabled) {
            return 0.0;
        }

        $rank = $this->rankOf($enrollment);

        if ($rank < 2) {
            return 0.0;
        }

        $rule = FeeSiblingDiscountRule::query()
            ->active()
            ->where('campus_id', $enrollment->campus_id)
            ->where('child_rank', $rank)
            ->where(function ($query) use ($enrollment) {
                $query->where('session_id', $enrollment->session_id)
                    ->orWhereNull('session_id');
            })
            ->where(function ($query) use ($feeHeadId) {
                $query->where('fee_head_id', $feeHeadId)
                    ->orWhereNull('fee_head_id');
            })
            // The most specific rule wins: this session over any session, and
            // this fee head over all heads.
            ->orderByRaw('session_id is null')
            ->orderByRaw('fee_head_id is null')
            ->first();

        return $rule ? $rule->amountOff($charge) : 0.0;
    }

    /**
     * Forgets the ranks worked out so far.
     *
     * A long-running fee run that spans an admission would otherwise keep using
     * the family it saw first.
     */
    public function forget(): void
    {
        $this->rankCache = [];
    }
}
