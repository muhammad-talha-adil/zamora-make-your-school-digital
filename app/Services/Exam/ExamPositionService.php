<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Position: 3rd of 42".
 *
 * It is on every result card printed in this country and nothing computed it.
 *
 * The sort is the easy half. The rules are the half that has to be written
 * down, because a school will be asked about every one of them by a parent:
 *
 *  - **Only finished results are ranked.** A child with a paper still unmarked
 *    is not third of forty-two; they are not yet in the reckoning at all.
 *    Their position is cleared rather than guessed.
 *  - **A child who failed is still ranked.** They sat the same papers. Leaving
 *    them out silently improves everybody below them, and a school that wants
 *    to print positions only for the passes can do that on the card.
 *  - **Absent counts as zero**, as it does everywhere else here, so a child who
 *    missed a paper ranks below one who sat it and did badly.
 *  - **Ties share a position and the next one is skipped** — 1, 2, 2, 4. This
 *    is what a school here prints, and it is the only tie rule that does not
 *    require somebody to break the tie by hand.
 *  - **Additional subjects do not count**, because the total they are ranked on
 *    already excludes them.
 *
 * Positions are a derived cache and always rebuildable: this recomputes a whole
 * section from its results and never adjusts one in place.
 */
class ExamPositionService
{
    /**
     * Recomputes positions for an exam.
     *
     * Both widths at once — the section a child sits in and the class as a
     * whole — because a school with three sections prints both on the card and
     * computing them separately means reading the same rows twice.
     *
     * @return int the number of results ranked
     */
    public function recomputeFor(Exam $exam, ?int $classId = null): int
    {
        $headers = ExamResultHeader::where('exam_id', $exam->id)
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->get();

        if ($headers->isEmpty()) {
            return 0;
        }

        [$ranked, $unranked] = $headers->partition(fn (ExamResultHeader $header) => $this->isRankable($header));

        $updates = [];

        // Within the section they sat in.
        foreach ($ranked->groupBy(fn ($header) => $header->class_id.':'.($header->section_id ?? 'none')) as $group) {
            foreach ($this->rank($group) as $id => $position) {
                $updates[$id]['position_in_section'] = $position;
                $updates[$id]['ranked_out_of'] = $group->count();
            }
        }

        // And within the whole class, across its sections.
        foreach ($ranked->groupBy('class_id') as $group) {
            foreach ($this->rank($group) as $id => $position) {
                $updates[$id]['position_in_class'] = $position;
            }
        }

        DB::transaction(function () use ($updates, $unranked) {
            foreach ($updates as $id => $values) {
                ExamResultHeader::where('id', $id)->update($values);
            }

            // A result that is no longer finished loses the position it had.
            // Leaving a stale one behind is worse than leaving it blank: the
            // card would print a rank the child no longer holds.
            if ($unranked->isNotEmpty()) {
                ExamResultHeader::whereIn('id', $unranked->pluck('id'))->update([
                    'position_in_section' => null,
                    'position_in_class' => null,
                    'ranked_out_of' => null,
                ]);
            }
        });

        return $ranked->count();
    }

    /**
     * Standard competition ranking: 1, 2, 2, 4.
     *
     * @param  Collection<int, ExamResultHeader>  $headers
     * @return array<int, int> position, keyed by result header id
     */
    public function rank($headers): array
    {
        $sorted = $headers
            ->sortByDesc(fn (ExamResultHeader $header) => (float) $header->total_obtained_cache)
            ->values();

        $positions = [];
        $position = 0;
        $seen = 0;
        $previous = null;

        foreach ($sorted as $header) {
            $seen++;
            $total = (float) $header->total_obtained_cache;

            // Equal totals share a position; the next child takes the number
            // this run would have reached, so two seconds are followed by a
            // fourth.
            if ($previous === null || abs($total - $previous) > 0.001) {
                $position = $seen;
                $previous = $total;
            }

            $positions[$header->id] = $position;
        }

        return $positions;
    }

    /**
     * Whether this result belongs in the ranking.
     *
     * A result nobody has finished marking is not ranked, and neither is one
     * with no marks in it at all.
     */
    private function isRankable(ExamResultHeader $header): bool
    {
        return $header->total_obtained_cache !== null
            && $header->result_status !== null
            && $header->result_status !== ExamResultHeader::RESULT_PENDING;
    }
}
