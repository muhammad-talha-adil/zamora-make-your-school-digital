<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * The year's result, assembled from the terms.
 *
 * "First term 25%, mid 25%, annual 50%" is the normal arrangement here, and the
 * module could not do it. `exam_papers.paper_weight` weighted one paper against
 * another inside a single exam; nothing weighted one **exam** against another,
 * so an annual result could not be built from the terms at all and schools were
 * doing it in a register.
 *
 * `exams.result_weight` is what an exam is worth. An exam with none is not part
 * of the annual reckoning — a class test does not belong in it.
 *
 * Nothing here is stored. The annual result is a view of results that already
 * exist, and a figure computed on demand cannot drift away from the marks it
 * came from. It is recomputed rather than cached for the same reason the header
 * totals are recomputed rather than adjusted.
 */
class AnnualResultService
{
    public function __construct(private GradeResolver $grades) {}

    /**
     * The exams that make up a session's annual result, in the order they were
     * sat.
     *
     * @return Collection<int, Exam>
     */
    public function examsIn(int $sessionId): Collection
    {
        return Exam::where('session_id', $sessionId)
            ->whereNotNull('result_weight')
            ->where('result_weight', '>', 0)
            ->orderBy('start_date')
            ->get();
    }

    /**
     * One child's year.
     *
     * @return array{
     *     session_id: int,
     *     student_id: int,
     *     terms: array<int, array<string, mixed>>,
     *     subjects: array<int, array<string, mixed>>,
     *     weight_used: float,
     *     weight_configured: float,
     *     percentage: float|null,
     *     grade: string|null,
     *     result_status: string,
     *     is_complete: bool
     * }
     */
    public function forStudent(int $studentId, int $sessionId, ?int $campusId = null): array
    {
        $exams = $this->examsIn($sessionId);

        $headers = ExamResultHeader::where('student_id', $studentId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->with('examResultLines.examPaper.subject')
            ->get()
            ->keyBy('exam_id');

        $terms = [];
        $weighted = 0.0;
        $weightUsed = 0.0;
        $weightConfigured = 0.0;
        $anyFailed = false;

        foreach ($exams as $exam) {
            $weight = (float) $exam->result_weight;
            $weightConfigured += $weight;

            $header = $headers->get($exam->id);
            $percentage = $header?->overall_percentage_cache;

            $terms[] = [
                'exam_id' => $exam->id,
                'exam_name' => $exam->name,
                'weight' => $weight,
                'percentage' => $percentage === null ? null : (float) $percentage,
                'result_status' => $header?->result_status,
                'sat' => $percentage !== null,
            ];

            if ($percentage === null) {
                continue;
            }

            $weighted += ((float) $percentage) * $weight;
            $weightUsed += $weight;

            if ($header?->result_status === ExamResultHeader::RESULT_FAIL) {
                $anyFailed = true;
            }
        }

        /*
         * Divided by the weight actually used, not by the weight the school
         * configured. A child whose mid-term result is not in yet is shown
         * against the terms they have sat — the alternative is reporting a
         * child at half their real percentage and calling it an annual result.
         *
         * `is_complete` says which of the two the reader is looking at.
         */
        $percentage = $weightUsed > 0 ? round($weighted / $weightUsed, 2) : null;

        $isComplete = $weightUsed > 0 && abs($weightUsed - $weightConfigured) < 0.001;

        return [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'terms' => $terms,
            'subjects' => $this->subjectsAcross($exams, $headers),
            'weight_used' => round($weightUsed, 2),
            'weight_configured' => round($weightConfigured, 2),
            'percentage' => $percentage,
            'grade' => $this->grades->itemFor($percentage, $campusId, $sessionId)?->grade_letter,
            'result_status' => $this->annualVerdict($exams, $percentage, $anyFailed, $isComplete),
            'is_complete' => $isComplete,
        ];
    }

    /**
     * The same figures for a whole section, keyed by student id.
     *
     * A school assembling annual results does a section at a time, so this asks
     * once rather than once per child.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forSection(int $sessionId, int $classId, ?int $sectionId = null, ?int $campusId = null): array
    {
        $exams = $this->examsIn($sessionId);

        $studentIds = ExamResultHeader::whereIn('exam_id', $exams->pluck('id'))
            ->where('class_id', $classId)
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->distinct()
            ->pluck('student_id');

        // The names, read once. A sheet of forty asking per child would be
        // forty queries for something one `whereIn` answers.
        $students = Student::with('user:id,name')
            ->whereIn('id', $studentIds)
            ->get(['id', 'admission_no', 'user_id'])
            ->keyBy('id');

        $results = [];

        foreach ($studentIds as $studentId) {
            $student = $students->get($studentId);

            $results[(int) $studentId] = $this->forStudent((int) $studentId, $sessionId, $campusId) + [
                'name' => $student?->user?->name,
                'admission_no' => $student?->admission_no,
            ];
        }

        return $results;
    }

    /**
     * Subject by subject across the year.
     *
     * The figure a parent looks for first: "how has he done in Maths all year",
     * not "what was his aggregate in the mid-term".
     *
     * @param  Collection<int, Exam>  $exams
     * @param  Collection<int, ExamResultHeader>  $headers  keyed by exam id
     * @return array<int, array<string, mixed>>
     */
    private function subjectsAcross(Collection $exams, Collection $headers): array
    {
        $bySubject = [];

        foreach ($exams as $exam) {
            $weight = (float) $exam->result_weight;
            $header = $headers->get($exam->id);

            if (! $header) {
                continue;
            }

            foreach ($header->examResultLines as $line) {
                if (! $line->countsTowardsTotal() || $line->percentage_cache === null) {
                    continue;
                }

                $subjectId = (int) $line->examPaper?->subject_id;

                $bySubject[$subjectId] ??= [
                    'subject_id' => $subjectId,
                    'subject' => $line->examPaper?->subject?->name,
                    'weighted' => 0.0,
                    'weight' => 0.0,
                    'terms' => [],
                ];

                $bySubject[$subjectId]['weighted'] += ((float) $line->percentage_cache) * $weight;
                $bySubject[$subjectId]['weight'] += $weight;
                $bySubject[$subjectId]['terms'][$exam->name] = (float) $line->percentage_cache;
            }
        }

        return array_values(array_map(function (array $subject) {
            $subject['percentage'] = $subject['weight'] > 0
                ? round($subject['weighted'] / $subject['weight'], 2)
                : null;

            unset($subject['weighted'], $subject['weight']);

            return $subject;
        }, $bySubject));
    }

    /**
     * Whether the year is a pass.
     *
     * Failing a term is not by itself failing the year — the whole point of
     * weighting the terms is that a bad first term can be recovered — and the
     * year is not declared at all until every term is in.
     *
     * The aggregate is the one the school set on the heaviest exam of the year,
     * which is the annual paper. A school that set none is not second-guessed
     * with an invented 33%; the year passes on the terms having been passed.
     *
     * @param  Collection<int, Exam>  $exams
     */
    private function annualVerdict(Collection $exams, ?float $percentage, bool $anyFailed, bool $isComplete): string
    {
        if ($percentage === null || ! $isComplete) {
            return ExamResultHeader::RESULT_PENDING;
        }

        $required = $exams
            ->sortByDesc(fn (Exam $exam) => (float) $exam->result_weight)
            ->first()?->aggregate_pass_percentage;

        if ($required !== null) {
            return $percentage >= (float) $required
                ? ExamResultHeader::RESULT_PASS
                : ExamResultHeader::RESULT_FAIL;
        }

        return $anyFailed ? ExamResultHeader::RESULT_FAIL : ExamResultHeader::RESULT_PASS;
    }
}
