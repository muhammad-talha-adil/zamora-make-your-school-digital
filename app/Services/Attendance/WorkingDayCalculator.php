<?php

namespace App\Services\Attendance;

use App\Models\AttendancePolicy;
use App\Models\StudentEnrollmentRecord;
use App\Services\AttendanceService;
use Illuminate\Support\Carbon;

/**
 * How many days a child was actually expected at school in a period.
 *
 * Without this a percentage has no denominator, and the class report was
 * dividing by "days somebody happened to mark" — so a child marked on three
 * days out of twenty-two showed 100%.
 *
 * A day counts only if all of these hold:
 *
 *  - the campus works that weekday (`attendance_policies`);
 *  - it is not a holiday, unless the school declared that holiday a working day;
 *  - the child was enrolled on it — nothing before admission, nothing after
 *    leaving.
 *
 * The last one is what makes the figure fair. A child admitted on the 20th is
 * not absent for the first nineteen days of the month.
 */
class WorkingDayCalculator
{
    public function __construct(private AttendanceService $attendance) {}

    /**
     * The days a child was expected between two dates, inclusive.
     */
    public function expectedDaysFor(
        StudentEnrollmentRecord $enrollment,
        Carbon $from,
        Carbon $to
    ): int {
        $window = $this->enrolledWindow($enrollment, $from, $to);

        if ($window === null) {
            return 0;
        }

        [$start, $end] = $window;

        return $this->workingDaysBetween($start, $end, $enrollment->campus_id, $enrollment->session_id);
    }

    /**
     * The days a campus works between two dates, ignoring any one child.
     */
    public function workingDaysBetween(Carbon $from, Carbon $to, ?int $campusId, ?int $sessionId = null): int
    {
        $policy = AttendancePolicy::resolve($campusId, $sessionId);
        $days = 0;

        for ($date = $from->copy(); $date->lessThanOrEqualTo($to); $date->addDay()) {
            if ($this->isWorkingDay($date, $policy, $campusId)) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Whether the school was open to this campus on this date.
     */
    public function isWorkingDay(Carbon $date, AttendancePolicy $policy, ?int $campusId): bool
    {
        if (! $policy->isWorkingWeekday($date)) {
            return false;
        }

        // A holiday the school declared a working day — an exam day in the
        // break, a make-up class — is open, and the same method the register
        // guard uses decides it, so the two cannot drift apart.
        return $this->attendance->isAttendanceAllowed($date->toDateString(), $campusId);
    }

    /**
     * The part of a period a child was actually on the roll for.
     *
     * @return array{0: Carbon, 1: Carbon}|null null when they were not enrolled at all
     */
    private function enrolledWindow(StudentEnrollmentRecord $enrollment, Carbon $from, Carbon $to): ?array
    {
        $start = $from->copy();
        $end = $to->copy();

        if ($enrollment->admission_date) {
            $admitted = Carbon::parse($enrollment->admission_date)->startOfDay();

            if ($admitted->greaterThan($start)) {
                $start = $admitted;
            }
        }

        if ($enrollment->leave_date) {
            $left = Carbon::parse($enrollment->leave_date)->startOfDay();

            if ($left->lessThan($end)) {
                $end = $left;
            }
        }

        return $start->greaterThan($end) ? null : [$start, $end];
    }
}
