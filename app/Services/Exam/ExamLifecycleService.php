<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Publishing an exam, and closing it.
 *
 * Both were one-line `update()` calls in the controller and both told only half
 * the truth.
 *
 * `publish()` wrote `published_at` and left `status` saying `marking`, so a
 * screen filtering on status and a screen reading the timestamp gave different
 * answers about the same exam — and it published an exam with no marks in it at
 * all without a murmur.
 *
 * `lock()` wrote `locked_at` and never wrote `locked_by`, a column that exists
 * and has a foreign key to `users`, so "who closed this exam" could not be
 * answered. `unlock()` left `locked_at` behind, so a reopened exam still
 * carried the time it was shut.
 */
class ExamLifecycleService
{
    public function __construct(private ExamPositionService $positions) {}

    /**
     * Publishes an exam's results.
     *
     * Refuses an exam that is not ready, and says exactly what is missing. A
     * school that means it anyway — half a class still to be marked, results
     * needed for the notice board tomorrow — passes `$force`, and that is a
     * decision somebody took rather than one the software made quietly.
     *
     * The result headers move with the exam, so a published exam does not
     * contain results still marked draft.
     *
     * @throws \RuntimeException when the exam is not ready and `$force` is false
     */
    public function publish(Exam $exam, ?User $actor = null, bool $force = false): Exam
    {
        $problems = $this->readinessOf($exam);

        if ($problems !== [] && ! $force) {
            throw new \RuntimeException($problems[0]);
        }

        return DB::transaction(function () use ($exam) {
            $exam->update([
                'status' => Exam::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            // "Position: 3rd of 42" is worked out at the moment the results go
            // out, from the results as they then stand. Recomputing it on every
            // saved mark would mean rewriting the whole section forty times
            // while a teacher works down the grid.
            $this->positions->recomputeFor($exam);

            // Anything already verified, published or locked stays where it is.
            ExamResultHeader::where('exam_id', $exam->id)
                ->whereIn('status', ExamResultHeader::OPEN_STATUSES)
                ->update(['status' => ExamResultHeader::STATUS_PUBLISHED]);

            return $exam->fresh();
        });
    }

    /**
     * Takes a published result back off the board.
     *
     * A mistake found after publishing is the normal reason. The exam returns
     * to marking, and so do the results that were published with it.
     */
    public function unpublish(Exam $exam): Exam
    {
        return DB::transaction(function () use ($exam) {
            $exam->update([
                'status' => Exam::STATUS_MARKING,
                'published_at' => null,
            ]);

            ExamResultHeader::where('exam_id', $exam->id)
                ->where('status', ExamResultHeader::STATUS_PUBLISHED)
                ->update(['status' => ExamResultHeader::STATUS_SUBMITTED]);

            return $exam->fresh();
        });
    }

    /**
     * What stands between this exam and being published.
     *
     * Reported as a list rather than a yes or no, because a school reading
     * "not ready" and nothing else will publish it anyway.
     *
     * @return array<int, string> empty when the exam is ready
     */
    public function readinessOf(Exam $exam): array
    {
        $problems = [];

        $paperCount = $exam->examPapers()->count();

        if ($paperCount === 0) {
            $problems[] = 'This exam has no papers on the timetable yet.';

            return $problems;
        }

        $registered = $exam->studentRegistrations()->count();

        if ($registered === 0) {
            $problems[] = 'No children are registered for this exam.';
        }

        $headers = ExamResultHeader::where('exam_id', $exam->id)->count();

        if ($headers === 0) {
            $problems[] = 'No marks have been entered for this exam.';

            return $problems;
        }

        if ($registered > $headers) {
            $problems[] = sprintf(
                '%d of %d registered children have no marks at all.',
                $registered - $headers,
                $registered
            );
        }

        $unmarked = ExamResultLine::whereIn(
            'result_header_id',
            ExamResultHeader::where('exam_id', $exam->id)->select('id')
        )
            ->whereNull('obtained_marks')
            ->where('is_absent', false)
            ->where('is_exempt', false)
            ->distinct()
            ->count('result_header_id');

        if ($unmarked > 0) {
            $problems[] = sprintf('%d children still have a paper unmarked.', $unmarked);
        }

        return $problems;
    }

    /**
     * Closes an exam to further marking, and records who closed it.
     */
    public function lock(Exam $exam, ?User $actor = null): Exam
    {
        $exam->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $actor?->id ?? auth()->id(),
        ]);

        return $exam->fresh();
    }

    /**
     * Reopens an exam, and clears the record of it having been closed.
     */
    public function unlock(Exam $exam): Exam
    {
        $exam->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return $exam->fresh();
    }
}
