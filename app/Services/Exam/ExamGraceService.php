<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamResultLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Grace marks.
 *
 * Widely given here: a child failing one subject by two or three marks is
 * lifted to the pass mark rather than held back a year. The module had nowhere
 * to put it, so a school doing it at all was editing the obtained marks — and
 * then could not answer the parent standing at the counter with the answer
 * sheet, because the number on the card no longer matched the number on the
 * paper.
 *
 * So grace is recorded **as grace**, in its own column, with a reason and the
 * person who gave it. The card can print both, and the school can always say
 * "you scored 31, we gave 2".
 *
 * Two limits, both real:
 *
 *  - The mark cannot pass the paper's total. 48 out of 50 plus 3 grace is 51,
 *    which is not a mark.
 *  - `exams.grace_marks_limit`, where the school has set one. Schools set a cap
 *    precisely so grace does not become a negotiation.
 */
class ExamGraceService
{
    public function __construct(
        private ExamMarkingService $marking,
        private GradeResolver $grades
    ) {}

    /**
     * Gives grace on one paper.
     *
     * Passing null or zero takes it away again, which is the same act done
     * backwards and is recorded the same way.
     *
     * @throws ValidationException
     */
    public function give(
        ExamResultLine $line,
        ?float $marks,
        ?string $reason = null,
        ?User $actor = null
    ): ExamResultLine {
        $this->refuseIfClosed($line);

        $marks = ($marks === null || $marks <= 0) ? null : round($marks, 2);

        if ($marks !== null) {
            $this->refuseIfBeyondLimits($line, $marks);
        }

        return DB::transaction(function () use ($line, $marks, $reason, $actor) {
            $line->grace_marks = $marks;
            $line->grace_reason = $marks === null ? null : $reason;
            $line->grace_by = $marks === null ? null : ($actor?->id ?? auth()->id());
            $line->grace_at = $marks === null ? null : now();

            $header = $line->resultHeader;
            $paper = $line->examPaper;

            // The same arithmetic that read the mark the first time, so a
            // paper that gains three grace marks gets its percentage, grade and
            // pass mark from one routine rather than two.
            $this->marking->stampLine($line, $paper, $header);
            $line->save();

            $this->marking->recalculate($header->fresh());

            return $line->fresh();
        });
    }

    /**
     * How much grace this paper could still take.
     *
     * Offered to the screen so a teacher is told the ceiling before they type,
     * rather than after.
     */
    public function headroomOn(ExamResultLine $line): float
    {
        $obtained = (float) ($line->obtained_marks ?? 0);
        $toTheTotal = max((float) $line->total_marks_snapshot - $obtained, 0);

        $limit = $line->resultHeader?->exam?->grace_marks_limit;

        return $limit === null ? $toTheTotal : min($toTheTotal, (float) $limit);
    }

    /**
     * The marks needed to lift this paper to a pass.
     *
     * The figure a school actually wants: "this child is two short". Zero when
     * they already passed, and null where the paper is not counted or is
     * unmarked.
     */
    public function shortfallOn(ExamResultLine $line): ?float
    {
        if (! $line->countsTowardsTotal() || $line->is_absent || $line->obtained_marks === null) {
            return null;
        }

        return round(max((float) $line->passing_marks_snapshot - (float) $line->effectiveMarks(), 0), 2);
    }

    /**
     * @throws ValidationException
     */
    private function refuseIfClosed(ExamResultLine $line): void
    {
        $header = $line->resultHeader;

        if (! $header) {
            throw ValidationException::withMessages([
                'grace_marks' => 'This mark does not belong to a result.',
            ]);
        }

        if ($header->exam?->is_locked || $header->is_locked) {
            throw ValidationException::withMessages([
                'grace_marks' => 'This exam is locked. Reopen it before giving grace marks.',
            ]);
        }

        if ($line->is_absent) {
            throw ValidationException::withMessages([
                'grace_marks' => 'This child was absent for this paper, so there is no mark to lift.',
            ]);
        }

        if (! $line->countsTowardsTotal()) {
            throw ValidationException::withMessages([
                'grace_marks' => 'This paper does not count towards the result, so grace marks would change nothing.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function refuseIfBeyondLimits(ExamResultLine $line, float $marks): void
    {
        $limit = $line->resultHeader?->exam?->grace_marks_limit;

        if ($limit !== null && $marks > (float) $limit) {
            throw ValidationException::withMessages([
                'grace_marks' => sprintf(
                    'This school gives at most %s grace marks on a paper.',
                    $this->trim((float) $limit)
                ),
            ]);
        }

        $ceiling = (float) $line->total_marks_snapshot;
        $wouldBe = (float) ($line->obtained_marks ?? 0) + $marks;

        if ($wouldBe > $ceiling) {
            throw ValidationException::withMessages([
                'grace_marks' => sprintf(
                    'That would make the mark %s out of %s.',
                    $this->trim($wouldBe),
                    $this->trim($ceiling)
                ),
            ]);
        }
    }

    private function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.');
    }
}
