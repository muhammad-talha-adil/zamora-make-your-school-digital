<?php

namespace App\Services\Staff;

use App\Models\Staff\StaffLeave;
use App\Models\Staff\StaffLeaveType;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Leave a member of staff asks for.
 *
 * `is_paid` on the leave type is what reaches payroll: unpaid leave is a
 * deduction, paid leave is not — `unpaidDaysBetween()` is what Phase 6's
 * payroll run reads.
 */
class StaffLeaveService
{
    /**
     * Applies for leave.
     *
     * @throws ValidationException
     */
    public function apply(
        StaffProfile $staff,
        int $leaveTypeId,
        string $fromDate,
        string $toDate,
        ?float $days = null,
        ?string $reason = null,
        ?User $actor = null
    ): StaffLeave {
        if ($toDate < $fromDate) {
            throw ValidationException::withMessages([
                'to_date' => 'The leave cannot end before it starts.',
            ]);
        }

        $type = StaffLeaveType::find($leaveTypeId);

        if (! $type || ! $type->is_active) {
            throw ValidationException::withMessages([
                'staff_leave_type_id' => 'That leave type is not available.',
            ]);
        }

        $this->refuseOverlap($staff, $fromDate, $toDate);

        // A whole number of calendar days unless the form says otherwise — a
        // half day is the normal exception, for a hospital appointment.
        $days ??= Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;

        return StaffLeave::create([
            'staff_profile_id' => $staff->id,
            'staff_leave_type_id' => $leaveTypeId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'days' => $days,
            'reason' => $reason,
            'status' => $type->requires_approval ? StaffLeave::STATUS_PENDING : StaffLeave::STATUS_APPROVED,
            'decided_by' => $type->requires_approval ? null : $actor?->id,
            'decided_at' => $type->requires_approval ? null : now(),
        ]);
    }

    /**
     * Approves or rejects a pending application.
     *
     * @throws ValidationException
     */
    public function decide(StaffLeave $leave, bool $approve, User $actor, ?string $note = null): StaffLeave
    {
        if ($leave->status !== StaffLeave::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'This application has already been decided.',
            ]);
        }

        $leave->update([
            'status' => $approve ? StaffLeave::STATUS_APPROVED : StaffLeave::STATUS_REJECTED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);

        return $leave->fresh();
    }

    /**
     * Withdraws an application that has not been decided yet.
     *
     * @throws ValidationException
     */
    public function cancel(StaffLeave $leave): StaffLeave
    {
        if ($leave->status !== StaffLeave::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Only a pending application can be cancelled.',
            ]);
        }

        $leave->update(['status' => StaffLeave::STATUS_CANCELLED]);

        return $leave->fresh();
    }

    /**
     * What is left of a year's entitlement.
     *
     * Null where the type has no fixed entitlement — unpaid leave is "as
     * needed", and asking for its balance would be a made-up number.
     */
    public function balanceRemaining(StaffProfile $staff, StaffLeaveType $type, int $year): ?int
    {
        if ($type->days_per_year === null) {
            return null;
        }

        $taken = (float) StaffLeave::where('staff_profile_id', $staff->id)
            ->where('staff_leave_type_id', $type->id)
            ->approved()
            ->whereYear('from_date', $year)
            ->sum('days');

        return (int) max($type->days_per_year - $taken, 0);
    }

    /**
     * Unpaid leave days falling inside a span — what a payroll run deducts.
     */
    public function unpaidDaysBetween(StaffProfile $staff, string $from, string $to): float
    {
        return (float) StaffLeave::where('staff_profile_id', $staff->id)
            ->approved()
            ->overlapping($from, $to)
            ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
            ->sum('days');
    }

    /**
     * @throws ValidationException
     */
    private function refuseOverlap(StaffProfile $staff, string $from, string $to): void
    {
        $overlap = StaffLeave::where('staff_profile_id', $staff->id)
            ->whereIn('status', [StaffLeave::STATUS_PENDING, StaffLeave::STATUS_APPROVED])
            ->overlapping($from, $to)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'from_date' => 'This overlaps a leave application already on file for these dates.',
            ]);
        }
    }
}
