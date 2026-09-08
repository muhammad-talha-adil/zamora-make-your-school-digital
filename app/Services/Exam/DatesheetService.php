<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\User;

/**
 * The datesheet.
 *
 * `exam_papers` has held the date and the times since the beginning, so this is
 * a printable view of what already exists — the sheet pinned to the notice
 * board and sent home three weeks before the exams start.
 *
 * It is grouped by day rather than listed flat, because that is how a child
 * reads it: "Monday the sixth, Maths, nine to twelve".
 *
 * Cancelled papers are shown, struck through, rather than dropped. A paper that
 * has been called off is exactly the thing a family needs to be told about, and
 * a sheet that silently loses a row leaves them turning up for it.
 */
class DatesheetService
{
    /**
     * The datesheet for a class, or a section of one.
     *
     * @return array{
     *     exam: Exam,
     *     days: array<int, array<string, mixed>>,
     *     paper_count: int,
     *     first_date: string|null,
     *     last_date: string|null
     * }
     */
    public function forExam(
        Exam $exam,
        ?int $classId = null,
        ?int $sectionId = null,
        ?int $campusId = null,
        ?User $viewer = null
    ): array {
        $papers = ExamPaper::with(['subject', 'class', 'section', 'campus'])
            ->where('exam_id', $exam->id)
            ->when($campusId, fn ($query) => $query->where('campus_id', $campusId))
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            // A teacher's sheet is their own sections, like every other list in
            // this module.
            ->when($viewer, fn ($query) => $query->visibleTo($viewer))
            ->orderBy('paper_date')
            ->orderBy('start_time')
            ->get();

        $days = $papers
            ->groupBy(fn (ExamPaper $paper) => $paper->paper_date?->toDateString())
            ->map(fn ($onThatDay, $date) => [
                'date' => $date,
                'day_name' => $onThatDay->first()->paper_date?->format('l'),
                'papers' => $onThatDay->map(fn (ExamPaper $paper) => $this->row($paper))->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'exam' => $exam,
            'days' => $days,
            'paper_count' => $papers->count(),
            'first_date' => $papers->first()?->paper_date?->toDateString(),
            'last_date' => $papers->last()?->paper_date?->toDateString(),
        ];
    }

    /**
     * One line of the sheet.
     *
     * @return array<string, mixed>
     */
    private function row(ExamPaper $paper): array
    {
        return [
            'id' => $paper->id,
            'subject' => $paper->subject?->name,
            'class' => $paper->class?->name,
            'section' => $paper->section?->name,
            'campus' => $paper->campus?->name,
            'start_time' => $paper->start_time,
            'end_time' => $paper->end_time,
            'duration' => $this->duration($paper),
            'total_marks' => (float) $paper->total_marks,
            'passing_marks' => (float) $paper->passing_marks,
            'status' => $paper->status,
            'is_cancelled' => $paper->status === 'cancelled',
        ];
    }

    /**
     * "3 hours", "1 hour 30 minutes" — the figure a child looks for after the
     * time.
     */
    private function duration(ExamPaper $paper): ?string
    {
        if (! $paper->start_time || ! $paper->end_time) {
            return null;
        }

        $start = strtotime($paper->start_time);
        $end = strtotime($paper->end_time);

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $minutes = (int) round(($end - $start) / 60);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.($hours === 1 ? 'hour' : 'hours');
        }

        if ($rest > 0) {
            $parts[] = $rest.' minutes';
        }

        return implode(' ', $parts) ?: null;
    }
}
