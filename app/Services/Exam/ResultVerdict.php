<?php

namespace App\Services\Exam;

use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use Illuminate\Support\Collection;

/**
 * Whether the child passed.
 *
 * The module recorded marks and never said. `passing_marks_snapshot` was
 * stored on every line from the beginning and compared to nothing, so the one
 * question the whole exam exists to answer had no answer in the database.
 *
 * The rule this applies is the ordinary one here, and it has two halves:
 *
 *  1. **Every counted subject on its own passing marks.** Failing one subject
 *     is failing, which is why grace marks exist.
 *  2. **An aggregate**, where the school has set one. 33% is the usual figure.
 *     It is `exams.aggregate_pass_percentage`, and a school that leaves it
 *     empty is judged on the subjects alone.
 *
 * Two things it deliberately does **not** do. It does not decide promotion —
 * that is a decision about the child's enrollment, taken on the annual result,
 * and it belongs to whoever builds "move this class up". And it never calls an
 * unfinished result a failure: a counted paper still unmarked is `pending`.
 */
class ResultVerdict
{
    public function __construct(private GradeResolver $grades) {}

    /**
     * The verdict on one result.
     *
     * @param  Collection<int, ExamResultLine>  $counted  the lines that count
     * @return array{result_status: string, failed_subject_count: int}
     */
    public function verdictFor(
        ExamResultHeader $header,
        $counted,
        ?float $percentage,
        ?int $gradeItemId = null
    ): array {
        if ($counted->isEmpty()) {
            return ['result_status' => ExamResultHeader::RESULT_PENDING, 'failed_subject_count' => 0];
        }

        $failed = $counted->filter(fn (ExamResultLine $line) => $line->is_pass === false)->count();

        // A paper nobody has marked yet is not a failure. The child has not
        // finished, and a card printed now would be wrong either way.
        $awaiting = $counted->contains(fn (ExamResultLine $line) => $line->is_pass === null);

        if ($awaiting) {
            return [
                'result_status' => ExamResultHeader::RESULT_PENDING,
                'failed_subject_count' => $failed,
            ];
        }

        $passed = $failed === 0
            && $this->meetsAggregate($header, $percentage)
            && $this->gradeIsAPass($gradeItemId);

        return [
            'result_status' => $passed ? ExamResultHeader::RESULT_PASS : ExamResultHeader::RESULT_FAIL,
            'failed_subject_count' => $failed,
        ];
    }

    /**
     * Whether the overall percentage clears the school's aggregate.
     *
     * A school that has not set one is judged on the subjects alone, which is
     * the more forgiving reading and the right default: inventing 33% for a
     * school that never asked for it would fail children the school considers
     * passed.
     */
    private function meetsAggregate(ExamResultHeader $header, ?float $percentage): bool
    {
        $required = $header->exam?->aggregate_pass_percentage;

        if ($required === null) {
            return true;
        }

        return $percentage !== null && $percentage >= (float) $required;
    }

    /**
     * Whether the grade band the child landed in is one the school calls a pass.
     *
     * `grade_system_items.is_pass` has existed the whole time. A school that
     * marks its F band as not-a-pass gets that respected here; one that has
     * filled in nothing is not second-guessed.
     */
    private function gradeIsAPass(?int $gradeItemId): bool
    {
        if ($gradeItemId === null) {
            return true;
        }

        $item = $this->grades->bandById($gradeItemId);

        // Null means the school never said, which is not the same as "no".
        return $item?->is_pass === null || (bool) $item->is_pass;
    }
}
