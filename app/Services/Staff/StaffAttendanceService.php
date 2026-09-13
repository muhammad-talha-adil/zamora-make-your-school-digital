<?php

namespace App\Services\Staff;

use App\Enums\AttendanceStatusCode;
use App\Models\Staff\StaffAttendance;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Attendance\LateArrivalResolver;
use App\Services\Attendance\WorkingDayCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A member of staff's own attendance.
 *
 * This did not exist before Phase 5. `attendances` is the student register — it
 * carries `class_id` and `section_id`, and its rows are `attendance_students`.
 *
 * Reused rather than rebuilt: `LateArrivalResolver` (the clock, `AttendanceTiming`)
 * decides whether a check-in counts as late, and `WorkingDayCalculator` decides
 * how many days a campus was open — both already correct for students, and a
 * second copy of either would drift from the first.
 */
class StaffAttendanceService
{
    public function __construct(
        private LateArrivalResolver $lateArrival,
        private WorkingDayCalculator $workingDays
    ) {}

    /**
     * Marks one person for one day.
     *
     * A status marked *present* with a check-in past the campus's deadline is
     * upgraded to *late* by the same rule the student register uses — a
     * teacher who marks *late* directly is never overruled.
     *
     * @throws ValidationException
     */
    public function mark(
        StaffProfile $staff,
        string $date,
        int $statusId,
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?int $campusId = null,
        ?User $actor = null,
        ?string $remarks = null
    ): StaffAttendance {
        $existing = StaffAttendance::where('staff_profile_id', $staff->id)
            ->whereDate('attendance_date', $date)
            ->first();

        if ($existing && $existing->is_locked) {
            throw ValidationException::withMessages([
                'attendance_date' => 'This day is locked and cannot be re-marked.',
            ]);
        }

        $campusId ??= $existing?->campus_id ?? $staff->campus_id;

        $resolvedStatusId = $this->lateArrival->resolve($statusId, $checkIn, $campusId, $date);
        $minutesLate = $this->lateArrival->minutesLate($checkIn, $campusId, $date);

        return DB::transaction(function () use ($existing, $staff, $date, $campusId, $resolvedStatusId, $checkIn, $checkOut, $minutesLate, $remarks, $actor) {
            if ($existing) {
                $existing->fill([
                    'campus_id' => $campusId,
                    'attendance_status_id' => $resolvedStatusId,
                    'check_in_at' => $checkIn,
                    'check_out_at' => $checkOut,
                    'minutes_late' => $minutesLate,
                    'remarks' => $remarks,
                    'marked_by' => $actor?->id,
                ])->save();

                return $existing->fresh();
            }

            return StaffAttendance::create([
                'staff_profile_id' => $staff->id,
                'attendance_date' => $date,
                'campus_id' => $campusId,
                'attendance_status_id' => $resolvedStatusId,
                'check_in_at' => $checkIn,
                'check_out_at' => $checkOut,
                'minutes_late' => $minutesLate,
                'remarks' => $remarks,
                'marked_by' => $actor?->id,
            ]);
        });
    }

    /**
     * A whole campus's register for one day, in one transaction.
     *
     * @param  array<int, array{staff_profile_id: int, attendance_status_id: int, check_in_at?: string|null, check_out_at?: string|null, remarks?: string|null}>  $rows
     * @return int how many were marked
     */
    public function markMany(array $rows, string $date, ?int $campusId, ?User $actor = null): int
    {
        return DB::transaction(function () use ($rows, $date, $campusId, $actor) {
            $marked = 0;

            foreach ($rows as $row) {
                $staff = StaffProfile::find($row['staff_profile_id']);

                if (! $staff) {
                    continue;
                }

                $this->mark(
                    $staff,
                    $date,
                    (int) $row['attendance_status_id'],
                    $row['check_in_at'] ?? null,
                    $row['check_out_at'] ?? null,
                    $campusId,
                    $actor,
                    $row['remarks'] ?? null
                );

                $marked++;
            }

            return $marked;
        });
    }

    /**
     * Closes a day so it cannot be re-marked, the same act as the student
     * register's own lock.
     */
    public function lockDay(string $date, ?int $campusId = null): int
    {
        return StaffAttendance::whereDate('attendance_date', $date)
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->update(['is_locked' => true]);
    }

    /**
     * One person's figures for a span of months — the same shape the student
     * report card reads, so a payroll deduction and a report agree.
     *
     * @return array{present: int, absent: int, leave: int, late: int, expected_days: int, marked_days: int}
     */
    public function monthlySummary(StaffProfile $staff, int $fromMonth, int $toMonth, int $year): array
    {
        $from = Carbon::create($year, min($fromMonth, $toMonth), 1)->startOfDay();
        $to = Carbon::create($year, max($fromMonth, $toMonth), 1)->endOfMonth();

        // `whereDate` on both bounds: a date-cast column compared against a
        // plain Y-m-d string with `whereBetween` has silently failed to match on
        // SQLite before (bug 19 in docs/WORKING-RULES.md).
        $rows = StaffAttendance::where('staff_profile_id', $staff->id)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->with('status')
            ->get();

        $expected = $this->workingDays->workingDaysBetween($from, $to, $staff->campus_id);

        return [
            'present' => $rows->filter(fn (StaffAttendance $r) => $r->status?->hasCode(AttendanceStatusCode::PRESENT))->count(),
            'absent' => $rows->filter(fn (StaffAttendance $r) => $r->status?->hasCode(AttendanceStatusCode::ABSENT))->count(),
            'leave' => $rows->filter(fn (StaffAttendance $r) => $r->status?->hasCode(AttendanceStatusCode::LEAVE))->count(),
            'late' => $rows->filter(fn (StaffAttendance $r) => $r->status?->hasCode(AttendanceStatusCode::LATE))->count(),
            'expected_days' => $expected,
            'marked_days' => $rows->count(),
            'unmarked_days' => max($expected - $rows->count(), 0),
        ];
    }
}
