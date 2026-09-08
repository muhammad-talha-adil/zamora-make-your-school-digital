<?php

namespace App\Services\Exam;

use App\Enums\Exam\SubjectRole;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The one routine that writes marks.
 *
 * There were three. This service, which was injected into the marking
 * controller and **never called**, and two inline copies in that controller
 * which disagreed with it and with each other: one wrote the per-line
 * percentage and grade, the others did not; one recomputed the header status,
 * the others did not; and the header totals were worked out by two different
 * pieces of arithmetic. Which screen a teacher had used decided what ended up
 * in the row, and a result card showed a figure for one class and a blank for
 * the next.
 *
 * Everything that writes a mark now comes through here.
 */
class ExamMarkingService
{
    public function __construct(
        private GradeResolver $grades,
        private ExamRegistrationService $registrations,
        private ResultVerdict $results
    ) {}

    /**
     * Records one student's marks across the papers of an exam.
     *
     * This is the shape the marking grid works in — a student, and the papers
     * in front of them.
     *
     * @param  array<int|string, array<string, mixed>>  $marksByPaper  keyed by paper id
     */
    public function saveStudentMarks(
        Exam $exam,
        int $studentId,
        array $marksByPaper,
        ?StudentEnrollmentRecord $enrollment = null
    ): ExamResultHeader {
        return DB::transaction(function () use ($exam, $studentId, $marksByPaper, $enrollment) {
            $header = $this->headerFor($exam, $studentId, $enrollment);
            $papers = $this->papersOf($exam, array_keys($marksByPaper));

            foreach ($marksByPaper as $paperId => $mark) {
                // A paper that is not this exam's is not written. The id came
                // from the request as an array key and was previously fetched
                // with a bare `findOrFail`, so another exam's paper could be
                // posted into this one's result.
                if (! $papers->has((int) $paperId)) {
                    continue;
                }

                $this->recordLine($header, $papers->get((int) $paperId), $mark);
            }

            $this->recalculate($header);

            return $header->fresh();
        });
    }

    /**
     * Records a whole grid in one pass.
     *
     * The bulk screen used to call `saveStudentMarks()` once per child, so a
     * class of forty opened forty transactions and rebuilt forty headers with
     * forty separate reads of their lines. It is one transaction and one read
     * now.
     *
     * @param  array<int, array{student_id: int|string, marks?: array<int|string, array<string, mixed>>}>  $rows
     * @param  Collection<int, ExamPaper>|null  $papers  the exam's papers, where the caller has already read them
     */
    public function saveBatch(Exam $exam, array $rows, $papers = null): void
    {
        DB::transaction(function () use ($exam, $rows, $papers) {
            $papers ??= $this->papersOf($exam);
            $headers = [];

            foreach ($rows as $row) {
                $header = $this->headerFor($exam, (int) $row['student_id']);

                foreach ((array) ($row['marks'] ?? []) as $paperId => $mark) {
                    if (! $papers->has((int) $paperId)) {
                        continue;
                    }

                    $this->recordLine($header, $papers->get((int) $paperId), $mark);
                }

                $headers[$header->id] = $header;
            }

            $this->recalculateMany($headers);
        });
    }

    /**
     * Records one paper's marks across a class.
     *
     * The same work seen from the other side — a paper, and the students who
     * sat it.
     *
     * @param  array<int, array<string, mixed>>  $students
     */
    public function saveMarks(ExamPaper $paper, array $students): bool
    {
        return DB::transaction(function () use ($paper, $students) {
            $headers = [];

            foreach ($students as $studentData) {
                $header = $this->headerFor(
                    $paper->exam,
                    (int) $studentData['student_id'],
                    null,
                    $paper
                );

                $this->recordLine($header, $paper, $studentData);
                $headers[$header->id] = $header;
            }

            $this->recalculateMany($headers);

            return true;
        });
    }

    /**
     * The result header a student holds for an exam, created if it is their
     * first mark.
     *
     * The child's registration is settled first. `exam_student_registrations`
     * was populated and then never consulted, so marks could be entered for a
     * child nobody had registered and the two tables disagreed about who sat
     * the exam. Registration is now implicit **and recorded** — a teacher
     * marking a child who was missed off the list is not stopped, but the
     * register is written.
     *
     * The header is placed where the registration says the child was, which is
     * the roll as it stood when the exam was sat, rather than where the paper
     * happens to be timetabled.
     */
    public function headerFor(
        Exam $exam,
        int $studentId,
        ?StudentEnrollmentRecord $enrollment = null,
        ?ExamPaper $paper = null
    ): ExamResultHeader {
        $registration = $this->registrations->registrationFor($exam, $studentId);

        return ExamResultHeader::firstOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $studentId,
            ],
            [
                'campus_id' => $registration->campus_id ?? $enrollment?->campus_id ?? $paper?->campus_id,
                'class_id' => $registration->class_id ?? $enrollment?->class_id ?? $paper?->class_id,
                'section_id' => $registration->section_id ?? $enrollment?->section_id ?? $paper?->section_id,
                'status' => ExamResultHeader::STATUS_DRAFT,
            ]
        );
    }

    /**
     * Writes one mark.
     *
     * The paper's totals are snapshotted onto the line, so a paper whose marks
     * are corrected later does not silently restate every result already given
     * out. The per-line percentage and grade are written here for every path —
     * one of the old routines left them empty.
     *
     * @param  array<string, mixed>  $mark
     */
    public function recordLine(ExamResultHeader $header, ExamPaper $paper, array $mark): ExamResultLine
    {
        $isAbsent = (bool) ($mark['is_absent'] ?? false);
        $isExempt = (bool) ($mark['is_exempt'] ?? false);

        // `obtained` is what the grid posts; `obtained_marks` what the paper
        // screen posts. Both mean the same thing.
        $obtained = $mark['obtained_marks'] ?? $mark['obtained'] ?? null;
        $obtained = ($isAbsent || $obtained === null || $obtained === '') ? null : (float) $obtained;

        $line = ExamResultLine::firstOrNew([
            'result_header_id' => $header->id,
            'exam_paper_id' => $paper->id,
        ]);

        /*
         * What the subject is to *this* child. It comes from the paper, which
         * says what the subject normally is, and the marking screen may say
         * otherwise for one child — the same subject is core for one and an
         * extra for another.
         */
        $role = $this->roleFor($mark, $paper, $line);

        $line->fill([
            'total_marks_snapshot' => $paper->total_marks,
            'passing_marks_snapshot' => $paper->passing_marks,
            'subject_role' => $role,
            'obtained_marks' => $obtained,
            'is_absent' => $isAbsent,
            'is_exempt' => $isExempt,
            'remarks' => $mark['remarks'] ?? null,
        ]);

        /*
         * Grace is given deliberately, through `ExamGraceService`, and is not
         * part of a mark entry. It is left alone here rather than blanked:
         * `updateOrCreate` used to write a fixed set of attributes, so anybody
         * correcting a mark would have wiped the grace off the line without
         * knowing it.
         */
        if (array_key_exists('grace_marks', $mark)) {
            $line->grace_marks = $mark['grace_marks'] === null || $mark['grace_marks'] === ''
                ? null
                : (float) $mark['grace_marks'];
        }

        // An absence carries no grace with it.
        if ($isAbsent) {
            $line->grace_marks = null;
            $line->grace_reason = null;
        }

        $this->stampLine($line, $paper, $header);

        $line->save();

        return $line;
    }

    /**
     * Writes the per-line percentage, grade and pass mark onto a line.
     *
     * Shared with the grace service, so a mark that gains three grace marks is
     * re-read by the same arithmetic that read it the first time.
     */
    public function stampLine(ExamResultLine $line, ExamPaper $paper, ExamResultHeader $header): void
    {
        $effective = $line->effectiveMarks();
        $total = (float) $line->total_marks_snapshot;
        $percentage = null;

        // The percentage is on the mark that goes on the card — what the child
        // wrote, plus whatever grace the school gave.
        if (! $line->is_absent && ! $line->is_exempt && $effective !== null && $total > 0) {
            $percentage = round(($effective / $total) * 100, 2);
        }

        $line->percentage_cache = $percentage;
        $line->grade_item_id_cache = $this->grades->idFor(
            $percentage,
            $paper->campus_id ?? $header->campus_id,
            $paper->exam?->session_id ?? $header->exam?->session_id
        );

        /*
         * Whether the child passed this paper. `passing_marks_snapshot` was
         * stored on every line from the beginning and compared to nothing.
         *
         * Null where the paper is not part of the reckoning — exempt, or an
         * additional subject — because "did not pass" would be a lie about a
         * paper that was never being counted.
         */
        $line->is_pass = $line->countsTowardsTotal()
            ? ($line->is_absent
                ? false
                : ($effective === null ? null : $effective >= (float) $line->passing_marks_snapshot))
            : null;
    }

    /**
     * What this subject is to this child.
     */
    private function roleFor(array $mark, ExamPaper $paper, ExamResultLine $line): SubjectRole
    {
        $given = $mark['subject_role'] ?? null;

        if ($given instanceof SubjectRole) {
            return $given;
        }

        if (is_string($given) && $role = SubjectRole::tryFrom($given)) {
            return $role;
        }

        // What it already was, then what the paper says, then core.
        return $line->subject_role ?? $paper->subject_role ?? SubjectRole::Core;
    }

    /**
     * Rebuilds a header from its lines.
     *
     * Always recomputed, never adjusted, so a corrected mark corrects the total
     * with it.
     *
     * **Absent counts as zero.** The old arithmetic took a missed paper out of
     * *both* sides, so a child who sat one paper of eight and scored 45 of 50
     * was reported at 90% and graded A. That is not how it works here: missing
     * a paper costs you its marks, which is the whole reason a child drags
     * themselves in with a fever.
     *
     * **Exempt is excluded from both sides**, which is the statement that was
     * being confused with absence — a paper the child was never required to sit
     * cannot count against them.
     */
    public function recalculate(ExamResultHeader $header, $lines = null): void
    {
        $lines ??= $header->examResultLines()->get();

        /*
         * Exempt papers, and additional subjects, are not part of the
         * reckoning at all — for two different reasons. Exempt means the child
         * was never required to sit it. Additional means they sat it for its
         * own sake, and a school does not let an extra subject pull a total up
         * or down.
         */
        $counted = $lines->filter(fn (ExamResultLine $line) => $line->countsTowardsTotal());

        // An absence contributes nothing to the numerator...
        $totalObtained = (float) $counted
            ->where('is_absent', false)
            ->sum(fn (ExamResultLine $line) => $line->effectiveMarks() ?? 0);

        // ...and its full marks to the denominator.
        $totalMax = (float) $counted->sum('total_marks_snapshot');

        $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : null;

        $gradeItemId = $this->grades->idFor(
            $percentage,
            $header->campus_id,
            $header->exam?->session_id
        );

        $verdict = $this->results->verdictFor($header, $counted, $percentage, $gradeItemId);

        $header->update([
            'total_obtained_cache' => $totalObtained,
            'overall_percentage_cache' => $percentage,
            'overall_grade_item_id_cache' => $gradeItemId,
            'result_status' => $verdict['result_status'],
            'failed_subject_count' => $verdict['failed_subject_count'],
            'status' => $this->statusFor($header, $lines),
        ]);
    }

    /**
     * The status a header should hold after a mark is written.
     *
     * **A header is never moved backwards.** The old routine set the status
     * unconditionally, so a result that had reached verified, published or
     * locked dropped back to submitted — or to draft — the moment anybody
     * edited a mark. Reopening a published result is a deliberate act, not a
     * side effect of a keystroke.
     *
     * @param  Collection<int, ExamResultLine>  $lines
     */
    private function statusFor(ExamResultHeader $header, $lines): string
    {
        $current = (string) ($header->status ?? ExamResultHeader::STATUS_DRAFT);

        if (! in_array($current, [ExamResultHeader::STATUS_DRAFT, ExamResultHeader::STATUS_SUBMITTED], true)) {
            return $current;
        }

        $awaitingMarks = $lines->contains(
            fn (ExamResultLine $line) => $line->obtained_marks === null
                && ! $line->is_absent
                && ! $line->is_exempt
        );

        return $awaitingMarks ? ExamResultHeader::STATUS_DRAFT : ExamResultHeader::STATUS_SUBMITTED;
    }

    /**
     * Rebuilds a set of headers, reading their lines in one query.
     *
     * Saving a class of forty went back to the database forty times for the
     * lines and, before the grade bands were held in memory, once more for
     * every mark. It is one query for the lot now.
     *
     * @param  iterable<int, ExamResultHeader>  $headers
     */
    public function recalculateMany(iterable $headers): void
    {
        $headers = collect($headers)->keyBy('id');

        if ($headers->isEmpty()) {
            return;
        }

        $linesByHeader = ExamResultLine::whereIn('result_header_id', $headers->keys())
            ->get()
            ->groupBy('result_header_id');

        foreach ($headers as $header) {
            $this->recalculate($header, $linesByHeader->get($header->id, collect()));
        }
    }

    /**
     * The papers of an exam, keyed by id.
     *
     * @param  array<int, int|string>  $paperIds
     * @return Collection<int, ExamPaper>
     */
    public function papersOf(Exam $exam, array $paperIds = [])
    {
        return ExamPaper::where('exam_id', $exam->id)
            ->when($paperIds !== [], fn ($query) => $query->whereIn('id', $paperIds))
            ->get()
            ->keyBy('id');
    }
}
