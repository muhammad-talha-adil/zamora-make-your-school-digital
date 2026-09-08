<?php

namespace App\Services\Attendance;

use App\Models\AttendanceSummary;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "Days present out of working days", for a report card.
 *
 * Every result card printed here carries it beside the marks, and it is asked
 * for in the education department's returns. The figures already exist in the
 * monthly summaries; this is the join, stated once, so the exam module does not
 * have to know how attendance is put together and cannot arrive at a different
 * answer from the attendance reports.
 *
 * A term is a range of months rather than a single one, because a result card
 * covers the term it belongs to — first term, mid-year, annual — and a school
 * decides for itself which months those are.
 */
class ReportCardAttendanceService
{
    public function __construct(private WorkingDayCalculator $workingDays) {}

    /**
     * One child's attendance for a span of months.
     *
     * `expected_days` is the honest denominator — the campus's working days,
     * less holidays, less anything outside the child's own enrollment — so a
     * child admitted in November is not shown as absent for October.
     *
     * @return array{
     *     present: float, absent: int, leave: int, late: int, half_day: int,
     *     expected_days: int, marked_days: int, unmarked_days: int, percentage: float
     * }
     */
    public function forStudent(int $studentId, int $sessionId, int $fromMonth, int $toMonth, int $year): array
    {
        $summaries = $this->summariesFor($studentId, $sessionId, $fromMonth, $toMonth, $year);

        /*
         * The denominator is worked out from the calendar, not summed off the
         * summaries. A month whose register was never taken has no summary row,
         * and taking the sum would then report zero expected days — which hides
         * the school's own gap instead of showing it, and is exactly the
         * flattery this figure exists to prevent.
         */
        $expected = $this->expectedDaysFor($studentId, $sessionId, $fromMonth, $toMonth, $year);
        $marked = (int) $summaries->sum('total_days');

        // Weighted, so a half day counts as half — the same arithmetic every
        // other attendance figure in the system uses.
        $present = round((float) $summaries->sum('present_equivalent'), 2);

        return [
            'present' => $present,
            'absent' => (int) $summaries->sum('absent_count'),
            'leave' => (int) $summaries->sum('leave_count'),
            'late' => (int) $summaries->sum('late_count'),
            'half_day' => (int) $summaries->sum('half_day_count'),
            'expected_days' => $expected,
            'marked_days' => $marked,

            // Days nobody took a register at all. Shown rather than hidden: it
            // is the school's own gap, and burying it flatters every child's
            // percentage equally.
            'unmarked_days' => max($expected - $marked, 0),
            'percentage' => $expected > 0 ? round(($present / $expected) * 100, 2) : 0.0,
        ];
    }

    /**
     * The same figures for a whole class, in one pass.
     *
     * A result card run prints a section at a time, so the report card module
     * asks once rather than once per child.
     *
     * @return array<int, array<string, float|int>> keyed by student id
     */
    public function forClass(
        int $classId,
        ?int $sectionId,
        int $sessionId,
        int $fromMonth,
        int $toMonth,
        int $year
    ): array {
        $studentIds = StudentEnrollmentRecord::query()
            ->where('session_id', $sessionId)
            ->where('class_id', $classId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->overlappingPeriod(
                Carbon::create($year, $fromMonth, 1)->startOfDay(),
                Carbon::create($year, $toMonth, 1)->endOfMonth()
            )
            ->pluck('student_id')
            ->unique();

        $figures = [];

        foreach ($studentIds as $studentId) {
            $figures[(int) $studentId] = $this->forStudent(
                (int) $studentId,
                $sessionId,
                $fromMonth,
                $toMonth,
                $year
            );
        }

        return $figures;
    }

    /**
     * The line a result card prints: "182 / 195 (93.33%)".
     */
    public function line(int $studentId, int $sessionId, int $fromMonth, int $toMonth, int $year): string
    {
        $figures = $this->forStudent($studentId, $sessionId, $fromMonth, $toMonth, $year);

        return sprintf(
            '%s / %d (%.2f%%)',
            rtrim(rtrim(number_format($figures['present'], 2), '0'), '.'),
            $figures['expected_days'],
            $figures['percentage']
        );
    }

    /**
     * The days the child was expected across the span.
     *
     * Their own enrollment decides the ends of it, so a child admitted in
     * November is not shown absent for October.
     */
    private function expectedDaysFor(int $studentId, int $sessionId, int $fromMonth, int $toMonth, int $year): int
    {
        $from = Carbon::create($year, min($fromMonth, $toMonth), 1)->startOfDay();
        $to = Carbon::create($year, max($fromMonth, $toMonth), 1)->endOfMonth();

        $enrollment = StudentEnrollmentRecord::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->overlappingPeriod($from, $to)
            ->orderByDesc('admission_date')
            ->first();

        return $enrollment
            ? $this->workingDays->expectedDaysFor($enrollment, $from, $to)
            : 0;
    }

    /**
     * @return Collection<int, AttendanceSummary>
     */
    private function summariesFor(int $studentId, int $sessionId, int $fromMonth, int $toMonth, int $year): Collection
    {
        return AttendanceSummary::query()
            ->where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('year', $year)
            ->whereBetween('month', [min($fromMonth, $toMonth), max($fromMonth, $toMonth)])
            ->get();
    }
}
