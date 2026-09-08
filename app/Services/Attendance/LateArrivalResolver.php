<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatusCode;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\AttendanceTiming;
use Illuminate\Support\Carbon;

/**
 * Turns a recorded arrival time into "late", against the clock in force.
 *
 * The timings knew the answer and nothing asked them. This is the write path
 * that does — one place for it, because the register is saved down three
 * different routes and a rule copied into three places is a rule that ends up
 * meaning three things.
 *
 * **What it does and, more importantly, what it will not do.** It upgrades a
 * child marked *present* to *late* when their check-in is past the deadline.
 * That is all. It never goes the other way, and it never touches any other
 * status:
 *
 *  - A teacher who marked *late* meant it. A child may reach the gate on time
 *    and the classroom ten minutes later, and the teacher can see that where a
 *    clock cannot.
 *  - Absent, leave and half day are statements about the whole day. An arrival
 *    time cannot contradict them, and a system that overruled a teacher on that
 *    would be worse than one that did nothing.
 *
 * With no timing set for the campus — which is every campus until one is
 * configured — nothing happens at all.
 */
class LateArrivalResolver
{
    /**
     * The status a child should hold, given when they actually arrived.
     *
     * Returns the status it was given unless there is a clear reason to change
     * it, so a caller can use the result unconditionally.
     */
    public function resolve(
        int $statusId,
        ?string $checkIn,
        ?int $campusId,
        $onDate,
        ?int $sessionId = null
    ): int {
        if (! $checkIn || ! $campusId) {
            return $statusId;
        }

        $status = $this->statusById($statusId);

        // Only an arrival marked present is up for reconsideration.
        if (! $status || ! $status->hasCode(AttendanceStatusCode::PRESENT)) {
            return $statusId;
        }

        $timing = AttendanceTiming::inForce($campusId, $onDate, $sessionId);

        if (! $timing || ! $timing->isLate($checkIn)) {
            return $statusId;
        }

        $late = AttendanceStatus::where('code', AttendanceStatusCode::LATE->value)
            ->active()
            ->first();

        // A school that has retired the late status has said it does not use
        // one; the arrival stands as present rather than failing.
        return $late?->id ?? $statusId;
    }

    /**
     * The same, for a register that is already known.
     */
    public function resolveForRegister(Attendance $attendance, int $statusId, ?string $checkIn): int
    {
        return $this->resolve(
            $statusId,
            $checkIn,
            $attendance->campus_id,
            $attendance->attendance_date,
            $attendance->session_id
        );
    }

    /**
     * How late an arrival was, in minutes, for a screen to show.
     */
    public function minutesLate(?string $checkIn, ?int $campusId, $onDate, ?int $sessionId = null): int
    {
        if (! $checkIn || ! $campusId) {
            return 0;
        }

        return AttendanceTiming::inForce($campusId, $onDate, $sessionId)
            ?->minutesLate($checkIn) ?? 0;
    }

    /**
     * The clock a screen should show while a register is being marked.
     */
    public function timingFor(?int $campusId, $onDate, ?int $sessionId = null): ?AttendanceTiming
    {
        return $campusId
            ? AttendanceTiming::inForce($campusId, $onDate instanceof Carbon ? $onDate : $onDate, $sessionId)
            : null;
    }

    private function statusById(int $statusId): ?AttendanceStatus
    {
        return AttendanceStatus::find($statusId);
    }
}
