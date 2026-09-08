<?php

namespace App\Services\Exam;

use App\Enums\Exam\SubjectRole;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\School;
use App\Services\Attendance\ReportCardAttendanceService;

/**
 * The result card.
 *
 * The module could compute a result and could not print one, which is the only
 * artefact of the whole exam that a family ever sees.
 *
 * This assembles it. Everything on it comes from a record rather than being
 * worked out here for the first time — the marks, the grace, the pass or fail,
 * the position, the attendance — so the card and the screens agree, and the
 * office is never in the position of explaining why the printout says something
 * different.
 *
 * The attendance line is attendance **S8**, which was finished and tested and
 * has been waiting for this: `ReportCardAttendanceService` gives days present
 * out of the working days a child was actually enrolled for.
 */
class ReportCardService
{
    public function __construct(
        private ReportCardAttendanceService $attendance,
        private ExamGraceService $grace
    ) {}

    /**
     * One child's card.
     *
     * @param  array{from_month?: int, to_month?: int, year?: int}  $attendanceSpan
     * @return array<string, mixed>
     */
    public function forResult(ExamResultHeader $header, array $attendanceSpan = []): array
    {
        $header->loadMissing([
            'student.user',
            'exam.examType',
            'campus',
            'class',
            'section',
            'overallGradeItem',
            'examResultLines.examPaper.subject',
            'examResultLines.gradeItem',
        ]);

        $lines = $header->examResultLines->sortBy(fn (ExamResultLine $line) => $line->examPaper?->paper_date);

        [$counted, $additional] = $lines->partition(
            fn (ExamResultLine $line) => $line->countsTowardsTotal()
        );

        return [
            'school' => School::where('is_active', true)->first(),
            'student' => $header->student,
            'exam' => $header->exam,
            'campus' => $header->campus,
            'class' => $header->class,
            'section' => $header->section,

            'subjects' => $counted->map(fn (ExamResultLine $line) => $this->row($line))->values()->all(),

            // Below the total, with their own grades, changing neither the
            // percentage nor the position.
            'additional_subjects' => $additional
                ->map(fn (ExamResultLine $line) => $this->row($line))
                ->values()
                ->all(),

            'totals' => [
                'obtained' => $header->total_obtained_cache === null
                    ? null
                    : (float) $header->total_obtained_cache,
                'maximum' => (float) $counted->sum('total_marks_snapshot'),
                'percentage' => $header->overall_percentage_cache === null
                    ? null
                    : (float) $header->overall_percentage_cache,
                'grade' => $header->overallGradeItem?->grade_letter,
                'grade_point' => $header->overallGradeItem?->grade_point,
                'grace_total' => round((float) $counted->sum('grace_marks'), 2),
            ],

            'result' => [
                'status' => $header->result_status,
                'failed_subjects' => (int) $header->failed_subject_count,
                'is_pass' => $header->result_status === ExamResultHeader::RESULT_PASS,
            ],

            'position' => [
                'in_section' => $header->position_in_section,
                'in_class' => $header->position_in_class,
                'out_of' => $header->ranked_out_of,
                // A card printed before the results were published has no
                // position on it, and says so rather than printing a blank.
                'line' => $this->positionLine($header),
            ],

            'attendance' => $this->attendanceFor($header, $attendanceSpan),
            'remarks' => $header->remarks,
            'printed_at' => now(),
        ];
    }

    /**
     * A section's cards, in roll order, for the run that prints forty at once.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forSection(Exam $exam, int $classId, ?int $sectionId = null, array $attendanceSpan = []): array
    {
        $headers = ExamResultHeader::where('exam_id', $exam->id)
            ->where('class_id', $classId)
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->with([
                'student.user',
                'exam.examType',
                'campus', 'class', 'section', 'overallGradeItem',
                'examResultLines.examPaper.subject',
                'examResultLines.gradeItem',
            ])
            ->get()
            ->sortBy('position_in_section');

        return $headers->map(fn (ExamResultHeader $header) => $this->forResult($header, $attendanceSpan))
            ->values()
            ->all();
    }

    /**
     * One row of the subject table.
     *
     * @return array<string, mixed>
     */
    private function row(ExamResultLine $line): array
    {
        $role = $line->subject_role ?? SubjectRole::Core;

        return [
            'subject' => $line->examPaper?->subject?->name,
            'role' => $role->value,
            'role_label' => $role->label(),
            'total' => (float) $line->total_marks_snapshot,
            'passing' => (float) $line->passing_marks_snapshot,

            // What the child wrote, and what the school added, kept apart. The
            // parent comparing the card with the answer sheet is the reason.
            'obtained' => $line->obtained_marks === null ? null : (float) $line->obtained_marks,
            'grace' => $line->grace_marks === null ? null : (float) $line->grace_marks,
            'grace_reason' => $line->grace_reason,
            'marks' => $line->effectiveMarks(),

            'percentage' => $line->percentage_cache === null ? null : (float) $line->percentage_cache,
            'grade' => $line->gradeItem?->grade_letter,
            'is_absent' => (bool) $line->is_absent,
            'is_exempt' => (bool) $line->is_exempt,
            'is_pass' => $line->is_pass,
            'shortfall' => $this->grace->shortfallOn($line),
            'remarks' => $line->remarks,
        ];
    }

    /**
     * "3rd of 42", or an honest blank.
     */
    private function positionLine(ExamResultHeader $header): ?string
    {
        if (! $header->position_in_section || ! $header->ranked_out_of) {
            return null;
        }

        return $this->ordinal((int) $header->position_in_section).' of '.$header->ranked_out_of;
    }

    /**
     * The attendance line.
     *
     * Defaults to the child's own session, from its first month to the month
     * the exam ended — which is the span a term's card covers.
     *
     * @param  array{from_month?: int, to_month?: int, year?: int}  $span
     * @return array<string, mixed>|null
     */
    private function attendanceFor(ExamResultHeader $header, array $span): ?array
    {
        $sessionId = $header->exam?->session_id;

        if (! $sessionId || ! $header->student_id) {
            return null;
        }

        $end = $header->exam?->end_date ?? $header->exam?->start_date ?? now();

        $figures = $this->attendance->forStudent(
            (int) $header->student_id,
            (int) $sessionId,
            $span['from_month'] ?? 1,
            $span['to_month'] ?? (int) $end->format('n'),
            $span['year'] ?? (int) $end->format('Y'),
        );

        return $figures + [
            'line' => $this->attendance->line(
                (int) $header->student_id,
                (int) $sessionId,
                $span['from_month'] ?? 1,
                $span['to_month'] ?? (int) $end->format('n'),
                $span['year'] ?? (int) $end->format('Y'),
            ),
        ];
    }

    private function ordinal(int $number): string
    {
        $suffix = match (true) {
            in_array($number % 100, [11, 12, 13], true) => 'th',
            $number % 10 === 1 => 'st',
            $number % 10 === 2 => 'nd',
            $number % 10 === 3 => 'rd',
            default => 'th',
        };

        return $number.$suffix;
    }
}
