<?php

namespace App\Services\Staff;

use App\Models\Staff\StaffAssignment;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The jobs one person holds.
 *
 * `staff_profiles.designation_id` is singular and a school does not employ
 * people that way: the man who drives the van also does the gardening, the
 * clerk also runs the library, and a teacher is often the hostel warden too.
 *
 * One assignment is **primary** — the job this person *is*. The database allows
 * only one open primary, so this service closes the old one before opening the
 * new one and that constraint is never reached.
 *
 * The profile's `designation_id` and `department_id` are kept in step with the
 * primary job, because a dozen screens read them directly and rewriting all of
 * them to walk a relation would be a lot of churn for no gain.
 */
class StaffAssignmentService
{
    /**
     * Gives somebody a job.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function give(StaffProfile $staff, array $data): StaffAssignment
    {
        $designationId = (int) ($data['designation_id'] ?? 0);

        if (! $designationId) {
            throw ValidationException::withMessages([
                'designation_id' => 'Say which job this is.',
            ]);
        }

        $startedOn = $data['started_on'] ?? now()->toDateString();
        $wantsPrimary = (bool) ($data['is_primary'] ?? false);

        // Their first job is the job they are, whatever the form said.
        $isFirst = ! StaffAssignment::where('staff_profile_id', $staff->id)
            ->whereNull('ended_on')
            ->exists();

        $primary = $wantsPrimary || $isFirst;

        $this->refuseDuplicate($staff, $designationId);

        return DB::transaction(function () use ($staff, $data, $designationId, $startedOn, $primary) {
            if ($primary) {
                $this->demoteCurrentPrimary($staff);
            }

            $assignment = StaffAssignment::create([
                'staff_profile_id' => $staff->id,
                'designation_id' => $designationId,
                'department_id' => $data['department_id'] ?? null,
                'campus_id' => $data['campus_id'] ?? $staff->campus_id,
                'is_primary' => $primary,
                'started_on' => $startedOn,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($primary) {
                $this->syncProfileToPrimary($staff, $assignment);
            }

            return $assignment;
        });
    }

    /**
     * Makes an existing job the primary one.
     *
     * A driver promoted to supervisor keeps the driving as a second job; what
     * changes is which one he *is*.
     *
     * @throws ValidationException
     */
    public function makePrimary(StaffAssignment $assignment): StaffAssignment
    {
        if ($assignment->ended_on) {
            throw ValidationException::withMessages([
                'assignment' => 'That job has already finished, so it cannot be the primary one.',
            ]);
        }

        $staff = $assignment->staffProfile;

        return DB::transaction(function () use ($assignment, $staff) {
            // The old primary is demoted, not ended — the person still does it.
            StaffAssignment::where('staff_profile_id', $staff->id)
                ->where('is_primary', true)
                ->whereNull('ended_on')
                ->where('id', '!=', $assignment->id)
                ->update(['is_primary' => false]);

            $assignment->update(['is_primary' => true]);

            $this->syncProfileToPrimary($staff, $assignment->fresh());

            return $assignment->fresh();
        });
    }

    /**
     * Ends a job somebody no longer does.
     *
     * @throws ValidationException
     */
    public function end(StaffAssignment $assignment, ?string $endedOn = null): StaffAssignment
    {
        $endedOn ??= now()->toDateString();

        if ($assignment->started_on && $endedOn < $assignment->started_on->toDateString()) {
            throw ValidationException::withMessages([
                'ended_on' => 'A job cannot end before it began ('
                    .$assignment->started_on->format('d M Y').').',
            ]);
        }

        $staff = $assignment->staffProfile;

        return DB::transaction(function () use ($assignment, $staff, $endedOn) {
            $wasPrimary = (bool) $assignment->is_primary;

            $assignment->update(['ended_on' => $endedOn, 'is_primary' => false]);

            /*
             * Somebody has to be the primary.
             *
             * Ending the job a person *is* leaves the record with no answer to
             * "what does this person do", so the oldest job they still hold
             * takes over rather than nothing doing.
             */
            if ($wasPrimary) {
                $next = StaffAssignment::where('staff_profile_id', $staff->id)
                    ->whereNull('ended_on')
                    ->orderBy('started_on')
                    ->orderBy('id')
                    ->first();

                if ($next) {
                    $next->update(['is_primary' => true]);
                    $this->syncProfileToPrimary($staff, $next);
                }
            }

            return $assignment->fresh();
        });
    }

    /**
     * The jobs somebody still holds, primary first.
     *
     * @return Collection<int, StaffAssignment>
     */
    public function currentJobsOf(StaffProfile $staff)
    {
        return StaffAssignment::with(['designation', 'department', 'campus'])
            ->where('staff_profile_id', $staff->id)
            ->whereNull('ended_on')
            ->orderByDesc('is_primary')
            ->orderBy('started_on')
            ->get();
    }

    /**
     * Brings the existing record into the new shape without a form being filled
     * in.
     *
     * Every staff record already has a `designation_id` and no assignment at
     * all, so without this the new screens would show five people doing
     * nothing. Safe to run twice.
     */
    public function backfill(StaffProfile $staff): ?StaffAssignment
    {
        if (! $staff->designation_id) {
            return null;
        }

        $existing = StaffAssignment::where('staff_profile_id', $staff->id)
            ->whereNull('ended_on')
            ->first();

        if ($existing) {
            return $existing;
        }

        return StaffAssignment::create([
            'staff_profile_id' => $staff->id,
            'designation_id' => $staff->designation_id,
            'department_id' => $staff->department_id,
            'campus_id' => $staff->campus_id,
            'is_primary' => true,
            'started_on' => $staff->hire_date?->toDateString() ?? now()->toDateString(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function refuseDuplicate(StaffProfile $staff, int $designationId): void
    {
        $already = StaffAssignment::where('staff_profile_id', $staff->id)
            ->where('designation_id', $designationId)
            ->whereNull('ended_on')
            ->exists();

        if ($already) {
            throw ValidationException::withMessages([
                'designation_id' => 'This person already holds that job.',
            ]);
        }
    }

    /**
     * The old primary stops being the job they *are*, and stays a job they do.
     */
    private function demoteCurrentPrimary(StaffProfile $staff): void
    {
        StaffAssignment::where('staff_profile_id', $staff->id)
            ->where('is_primary', true)
            ->whereNull('ended_on')
            ->update(['is_primary' => false]);
    }

    /**
     * Keeps the profile's own columns pointing at the primary job.
     */
    private function syncProfileToPrimary(StaffProfile $staff, StaffAssignment $assignment): void
    {
        $staff->update([
            'designation_id' => $assignment->designation_id,
            'department_id' => $assignment->department_id ?? $staff->department_id,
        ]);
    }
}
