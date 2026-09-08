<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatusCode;
use App\Models\Attendance;
use App\Models\AttendanceStudent;
use App\Models\AttendanceSummary;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Keeps the monthly attendance summary in step with the register.
 *
 * The summary table existed and nothing ever wrote to it. It is kept rather
 * than dropped because a report card and a government return both want "days
 * present out of working days" per child per month, and recomputing a year of
 * registers for a whole school on every request is not something to do twice.
 *
 * It is a **cache, never a source of truth**. Every figure is derived from
 * `attendance_students`, and any row can be thrown away and rebuilt with
 * `php artisan attendance:rebuild-summaries`. Nothing is ever incremented in
 * place: a month is always recomputed from its records, so a correction to an
 * old register cannot leave the summary quietly wrong.
 */
class AttendanceSummaryService
{
    public function __construct(private WorkingDayCalculator $workingDays) {}

    /**
     * Rebuilds the summaries every student on a register belongs to.
     *
     * Called after a register is saved. One register touches one month, so this
     * is a handful of rows, not a batch job.
     */
    public function refreshForRegister(Attendance $attendance): void
    {
        $date = Carbon::parse($attendance->attendance_date);

        $studentIds = AttendanceStudent::where('attendance_id', $attendance->id)
            ->pluck('student_id')
            ->unique();

        foreach ($studentIds as $studentId) {
            $this->refresh(
                (int) $studentId,
                (int) $attendance->session_id,
                (int) $date->month,
                (int) $date->year
            );
        }
    }

    /**
     * Recomputes one student's month from the registers.
     */
    public function refresh(int $studentId, int $sessionId, int $month, int $year): ?AttendanceSummary
    {
        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth();

        $enrollment = $this->enrollmentFor($studentId, $sessionId, $from, $to);

        if (! $enrollment) {
            return null;
        }

        $records = AttendanceStudent::with('attendanceStatus')
            ->where('student_id', $studentId)
            ->whereHas('attendance', function ($query) use ($from, $to, $sessionId) {
                $query->whereDate('attendance_date', '>=', $from->toDateString())
                    ->whereDate('attendance_date', '<=', $to->toDateString())
                    ->where('session_id', $sessionId);
            })
            ->get();

        $counts = $this->countBy($records);

        return AttendanceSummary::updateOrCreate(
            [
                'student_id' => $studentId,
                'session_id' => $sessionId,
                'month' => $month,
                'year' => $year,
            ],
            array_merge($counts, [
                'total_days' => $records->count(),
                'expected_days' => $this->workingDays->expectedDaysFor($enrollment, $from, $to),
                'computed_at' => now(),
            ])
        );
    }

    /**
     * Tallies a month's records by what each status is worth.
     *
     * The counts are read off the status row rather than tested against a code,
     * so a school that adds its own status gets it counted without the
     * arithmetic being edited.
     *
     * @param  Collection<int, AttendanceStudent>  $records
     * @return array<string, int|float>
     */
    private function countBy($records): array
    {
        $counts = [
            'present_count' => 0,
            'absent_count' => 0,
            'leave_count' => 0,
            'late_count' => 0,
            'half_day_count' => 0,
            'present_equivalent' => 0.0,
        ];

        foreach ($records as $record) {
            $status = $record->attendanceStatus;

            if (! $status) {
                continue;
            }

            $counts['present_equivalent'] += $status->presentWeight();

            match ($status->code) {
                AttendanceStatusCode::PRESENT->value => $counts['present_count']++,
                AttendanceStatusCode::ABSENT->value => $counts['absent_count']++,
                AttendanceStatusCode::LEAVE->value => $counts['leave_count']++,
                AttendanceStatusCode::LATE->value => $counts['late_count']++,
                AttendanceStatusCode::HALF_DAY->value => $counts['half_day_count']++,

                // A status the school added itself still counts towards the
                // weighted total above; it simply has no column of its own.
                default => null,
            };
        }

        $counts['present_equivalent'] = round($counts['present_equivalent'], 2);

        return $counts;
    }

    /**
     * The enrollment a student held during the month.
     *
     * Taken as at the month rather than as "the open one", so a child who has
     * since left still has their months summarised.
     */
    private function enrollmentFor(int $studentId, int $sessionId, Carbon $from, Carbon $to): ?StudentEnrollmentRecord
    {
        return StudentEnrollmentRecord::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->whereDate('admission_date', '<=', $to->toDateString())
            ->where(function ($query) use ($from) {
                $query->whereNull('leave_date')
                    ->orWhereDate('leave_date', '>=', $from->toDateString());
            })
            ->orderByDesc('admission_date')
            ->first();
    }
}
