<?php

namespace App\Services\Staff;

use App\Models\Staff\StaffAssignment;
use App\Models\Staff\StaffEmploymentPeriod;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Joining, leaving, and coming back.
 *
 * The same act the student module already does, for the same reason: a teacher
 * who leaves in June and returns in September has **two periods and one
 * record**, and every historical question — were they here for that payroll,
 * what were they paid then — reads the period rather than the profile.
 *
 * Before this, `staff_profiles.hire_date` was the whole of it. A person who
 * left and came back had their joining date overwritten, and the year they were
 * away vanished.
 */
class StaffEmploymentService
{
    /**
     * Opens the first spell for somebody who has just been taken on.
     *
     * Called from the moment a staff record is created, so nobody exists
     * without a period. `hire_date` on the profile stays as the date they first
     * joined, which is what a certificate prints.
     */
    public function join(StaffProfile $staff, ?string $joinedOn = null): StaffEmploymentPeriod
    {
        $joinedOn ??= $staff->hire_date?->toDateString() ?? now()->toDateString();

        $open = $this->openPeriodOf($staff);

        if ($open) {
            return $open;
        }

        return StaffEmploymentPeriod::create([
            'staff_profile_id' => $staff->id,
            'joined_on' => $joinedOn,
            'previous_period_id' => $this->lastPeriodOf($staff)?->id,
        ]);
    }

    /**
     * Records that somebody has left.
     *
     * The date is asked for. A school entering the register a fortnight later
     * has to be able to say when the person actually went, because the payroll
     * for that month reads it.
     *
     * @throws ValidationException
     */
    public function leave(
        StaffProfile $staff,
        ?string $leftOn = null,
        ?string $reason = null,
        ?string $notes = null,
        ?User $actor = null
    ): StaffProfile {
        $period = $this->openPeriodOf($staff);

        if (! $period) {
            throw ValidationException::withMessages([
                'staff_profile_id' => 'This person is not currently employed, so there is nothing to close.',
            ]);
        }

        $leftOn = $this->leaveDateFor($period, $leftOn);

        return DB::transaction(function () use ($staff, $period, $leftOn, $reason, $notes) {
            $period->update([
                'left_on' => $leftOn,
                'leaving_reason' => $reason,
                'notes' => $notes,
            ]);

            /*
             * Their jobs end with them.
             *
             * A person who has left still holding an open assignment would keep
             * appearing wherever the school asks "who is the driver" — and the
             * primary-job index would refuse to give the post to their
             * replacement.
             */
            StaffAssignment::where('staff_profile_id', $staff->id)
                ->whereNull('ended_on')
                ->update(['ended_on' => $leftOn]);

            $staff->update(['is_active' => false]);

            $this->setLoginActive($staff, false);

            return $staff->fresh();
        });
    }

    /**
     * Takes somebody back on.
     *
     * Closes anything still open first, so the one-open-period rule the
     * database holds is never reached — the lesson from re-admitting a child.
     *
     * @param  array<string, mixed>  $data
     */
    public function rejoin(StaffProfile $staff, array $data = [], ?User $actor = null): StaffProfile
    {
        $joinedOn = $data['joined_on'] ?? now()->toDateString();

        return DB::transaction(function () use ($staff, $joinedOn, $data) {
            $previous = $this->openPeriodOf($staff);

            if ($previous) {
                $previous->update(['left_on' => $joinedOn]);
            }

            StaffEmploymentPeriod::create([
                'staff_profile_id' => $staff->id,
                'joined_on' => $joinedOn,
                // Whatever spell this one follows: the one just closed, or the
                // last one on file for somebody returning after a gap.
                'previous_period_id' => $previous->id ?? $this->lastPeriodOf($staff)?->id,
            ]);

            $staff->update([
                'is_active' => true,
                'campus_id' => $data['campus_id'] ?? $staff->campus_id,
            ]);

            $this->setLoginActive($staff, true);

            return $staff->fresh();
        });
    }

    /**
     * How long somebody has been here, for a certificate or an experience
     * letter.
     *
     * Counted across every spell, because a person who left and returned has
     * served both.
     */
    public function serviceMonths(StaffProfile $staff): int
    {
        return (int) $staff->employmentPeriods()->get()->sum(function (StaffEmploymentPeriod $period) {
            $to = $period->left_on ?? now();

            return $period->joined_on->diffInMonths($to);
        });
    }

    public function openPeriodOf(StaffProfile $staff): ?StaffEmploymentPeriod
    {
        return StaffEmploymentPeriod::where('staff_profile_id', $staff->id)
            ->whereNull('left_on')
            ->orderByDesc('joined_on')
            ->orderByDesc('id')
            ->first();
    }

    public function lastPeriodOf(StaffProfile $staff): ?StaffEmploymentPeriod
    {
        return StaffEmploymentPeriod::where('staff_profile_id', $staff->id)
            ->orderByRaw('CASE WHEN left_on IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('joined_on')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @throws ValidationException
     */
    private function leaveDateFor(StaffEmploymentPeriod $period, ?string $leftOn): string
    {
        $leftOn ??= now()->toDateString();

        if ($leftOn < $period->joined_on->toDateString()) {
            throw ValidationException::withMessages([
                'left_on' => 'Somebody cannot leave before the day they joined ('
                    .$period->joined_on->format('d M Y').').',
            ]);
        }

        if ($leftOn > now()->toDateString()) {
            // A notice period is not a leaving date. Until the day comes they
            // are still on the payroll and still on the register.
            throw ValidationException::withMessages([
                'left_on' => 'A leaving date cannot be in the future.',
            ]);
        }

        return $leftOn;
    }

    private function setLoginActive(StaffProfile $staff, bool $active): void
    {
        if ($staff->user_id) {
            User::where('id', $staff->user_id)->update(['is_active' => $active]);
        }
    }
}
