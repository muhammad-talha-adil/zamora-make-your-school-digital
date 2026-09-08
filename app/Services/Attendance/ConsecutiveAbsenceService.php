<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatusCode;
use App\Models\AttendanceStudent;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Children who have stopped coming.
 *
 * Three or more unexplained days in a row is the standard signal here that a
 * child is drifting out of school, and it is asked for in government returns.
 * Left to a person to notice, it is noticed late or not at all — a class
 * teacher sees one absence at a time, not the pattern across a fortnight.
 *
 * "Unexplained" is the important word: approved leave does not count, and
 * neither does a day the school was shut. A child absent on Thursday and
 * Saturday with Friday a holiday between them has been away twice running, not
 * once — which is why the days the school was open are counted rather than the
 * days on the calendar.
 */
class ConsecutiveAbsenceService
{
    public function __construct(private WorkingDayCalculator $workingDays) {}

    /**
     * Children with a run of unexplained absences ending on or near a date.
     *
     * @return Collection<int, array{student: Student, days: int, since: string, last: string}>
     */
    public function runsFor(
        int $classId,
        ?int $sectionId,
        ?int $sessionId,
        Carbon $upTo,
        int $threshold = 3,
        int $lookBackDays = 30
    ): Collection {
        $from = $upTo->copy()->subDays($lookBackDays);

        $records = AttendanceStudent::query()
            ->with(['attendanceStatus', 'student.user'])
            ->whereHas('attendance', function ($query) use ($classId, $sectionId, $sessionId, $from, $upTo) {
                $query->where('class_id', $classId)
                    ->whereDate('attendance_date', '>=', $from->toDateString())
                    ->whereDate('attendance_date', '<=', $upTo->toDateString());

                if ($sectionId !== null) {
                    $query->where('section_id', $sectionId);
                }

                if ($sessionId !== null) {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get()
            ->sortBy(fn (AttendanceStudent $row) => $row->attendance?->attendance_date)
            ->groupBy('student_id');

        return $records
            ->map(fn (Collection $rows) => $this->trailingRun($rows, $threshold))
            ->filter()
            ->values();
    }

    /**
     * The unbroken run of absences at the **end** of a child's records.
     *
     * Only a run that is still going matters: a child absent for a week in
     * September who has been in class ever since is not drifting away, and
     * flagging them buries the child who is.
     *
     * @param  Collection<int, AttendanceStudent>  $rows
     * @return array{student: Student, days: int, since: string, last: string}|null
     */
    private function trailingRun(Collection $rows, int $threshold): ?array
    {
        $run = [];

        foreach ($rows->reverse() as $row) {
            $code = $row->attendanceStatus?->code;

            // Approved leave breaks nothing and counts as nothing: the family
            // told the school, which is the opposite of the signal being looked
            // for.
            if ($code === AttendanceStatusCode::LEAVE->value) {
                continue;
            }

            if ($code !== AttendanceStatusCode::ABSENT->value) {
                break;
            }

            $run[] = $row;
        }

        if (count($run) < $threshold) {
            return null;
        }

        $dates = collect($run)
            ->map(fn (AttendanceStudent $row) => Carbon::parse($row->attendance->attendance_date))
            ->sort()
            ->values();

        return [
            'student' => $run[0]->student,
            'days' => count($run),
            'since' => $dates->first()->toDateString(),
            'last' => $dates->last()->toDateString(),
        ];
    }
}
