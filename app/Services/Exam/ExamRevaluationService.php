<?php

namespace App\Services\Exam;

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultLine;
use App\Models\Exam\ExamRevaluationAction;
use App\Models\Exam\ExamRevaluationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rechecking a paper.
 *
 * `exam_revaluation_requests`, `exam_revaluation_actions` and a controller all
 * existed and none of them worked. The one action that wrote anything —
 * `request()` — wrote `exam_id`, `student_id`, `exam_paper_id` and
 * `expected_marks`, four columns the table does not have, so every submission
 * threw. `index()` filtered on `exam_id` for the same reason. `approve()`,
 * `reject()` and `applyChange()` returned a sentence of English and changed
 * nothing at all.
 *
 * The flow this builds is the one a school here actually runs: the result comes
 * out, a parent applies for a recheck of one paper, somebody looks at the
 * script, and the mark either stands or is corrected — **with the old mark
 * kept**, because the first question a parent asks is what it was before.
 *
 * `exam_revaluation_requests` carries a unique index on the result line: one
 * request per paper. A second application on the same paper therefore reopens
 * the same row, and what happened the first time stays in the actions, which
 * are the history and are never rewritten.
 */
class ExamRevaluationService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_APPLIED = 'applied';

    /**
     * The statuses a request is still being decided under.
     *
     * @var array<int, string>
     */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_IN_REVIEW];

    public function __construct(private ExamMarkingService $marking) {}

    /**
     * Applies for a recheck of one paper.
     *
     * Refused before the result is out — there is nothing to disagree with yet —
     * and refused on a locked exam, which is what locking is for.
     *
     * @throws ValidationException
     */
    public function open(ExamResultLine $line, ?User $requester = null, ?string $reason = null): ExamRevaluationRequest
    {
        $exam = $line->resultHeader?->exam;

        if (! $exam) {
            throw ValidationException::withMessages([
                'exam_result_line_id' => 'This mark does not belong to an exam.',
            ]);
        }

        if ($exam->is_locked) {
            throw ValidationException::withMessages([
                'exam_result_line_id' => 'This exam is locked. Reopen it before accepting a recheck.',
            ]);
        }

        if ($exam->status !== Exam::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'exam_result_line_id' => 'The result has not been published yet, so there is nothing to recheck.',
            ]);
        }

        $existing = ExamRevaluationRequest::where('exam_result_line_id', $line->id)->first();

        if ($existing && in_array((string) $existing->status, self::OPEN_STATUSES, true)) {
            throw ValidationException::withMessages([
                'exam_result_line_id' => 'A recheck of this paper has already been applied for.',
            ]);
        }

        if ($existing) {
            $existing->update([
                'requested_by' => $requester?->id ?? auth()->id(),
                'reason' => $reason,
                'status' => self::STATUS_PENDING,
            ]);

            return $existing->fresh();
        }

        return ExamRevaluationRequest::create([
            'exam_result_line_id' => $line->id,
            'requested_by' => $requester?->id ?? auth()->id(),
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Somebody has the script in front of them.
     */
    public function review(ExamRevaluationRequest $request, ?User $actor = null): ExamRevaluationRequest
    {
        $this->mustBe($request, self::OPEN_STATUSES, 'looked at');

        return $this->settle($request, self::STATUS_IN_REVIEW, $actor, null, null);
    }

    /**
     * The mark stands.
     *
     * Recorded as an action with the mark unchanged on both sides, so the
     * history shows the paper was looked at and nothing was found — which is
     * what the parent is owed.
     */
    public function reject(ExamRevaluationRequest $request, ?User $actor = null, ?string $note = null): ExamRevaluationRequest
    {
        $this->mustBe($request, self::OPEN_STATUSES, 'rejected');

        $current = $request->examResultLine?->obtained_marks;

        return $this->settle($request, self::STATUS_REJECTED, $actor, $current, $current, $note);
    }

    /**
     * The mark is wrong, and this is what it should be.
     *
     * Deciding and applying are separate on purpose: the schema carries both an
     * `approved` and an `applied` status, and a school that approves a batch on
     * Tuesday and reprints result cards on Thursday needs the gap.
     *
     * @throws ValidationException
     */
    public function approve(
        ExamRevaluationRequest $request,
        float $newMarks,
        ?User $actor = null,
        ?string $note = null
    ): ExamRevaluationRequest {
        $this->mustBe($request, self::OPEN_STATUSES, 'approved');

        $line = $request->examResultLine;

        if (! $line) {
            throw ValidationException::withMessages([
                'request' => 'The mark this recheck was about no longer exists.',
            ]);
        }

        if ($newMarks < 0 || $newMarks > (float) $line->total_marks_snapshot) {
            throw ValidationException::withMessages([
                'new_marks' => sprintf(
                    'The corrected mark must be between 0 and %s, the total this paper was sat for.',
                    rtrim(rtrim(number_format((float) $line->total_marks_snapshot, 2), '0'), '.')
                ),
            ]);
        }

        return $this->settle($request, self::STATUS_APPROVED, $actor, $line->obtained_marks, $newMarks, $note);
    }

    /**
     * Writes the corrected mark onto the result.
     *
     * The header is rebuilt through the marking service, so the percentage, the
     * grade and the status are worked out by the same routine that would have
     * worked them out had the mark been right the first time.
     *
     * @throws ValidationException
     */
    public function apply(ExamRevaluationRequest $request, ?User $actor = null): ExamRevaluationRequest
    {
        $this->mustBe($request, [self::STATUS_APPROVED], 'applied');

        $line = $request->examResultLine;

        $decision = $request->examRevaluationActions()
            ->whereNotNull('new_marks')
            ->latest('id')
            ->first();

        if (! $line || ! $decision) {
            throw ValidationException::withMessages([
                'request' => 'There is no corrected mark on this recheck to apply.',
            ]);
        }

        $exam = $line->resultHeader?->exam;

        if ($exam?->is_locked) {
            throw ValidationException::withMessages([
                'request' => 'This exam is locked. Reopen it before correcting a mark.',
            ]);
        }

        return DB::transaction(function () use ($request, $line, $decision, $actor) {
            $header = $line->resultHeader;

            $this->marking->recordLine($header, $line->examPaper, [
                'obtained_marks' => $decision->new_marks,
                'is_absent' => false,
                'is_exempt' => (bool) $line->is_exempt,
                'remarks' => $line->remarks,
            ]);

            $this->marking->recalculate($header->fresh());

            return $this->settle(
                $request,
                self::STATUS_APPLIED,
                $actor,
                $decision->old_marks,
                $decision->new_marks,
                'Applied to the result.'
            );
        });
    }

    /**
     * Everything that has happened to a recheck, oldest first.
     *
     * @return Collection<int, ExamRevaluationAction>
     */
    public function historyOf(ExamRevaluationRequest $request)
    {
        return $request->examRevaluationActions()->with('actionBy')->orderBy('id')->get();
    }

    /**
     * Moves a request, and writes down that it moved.
     */
    private function settle(
        ExamRevaluationRequest $request,
        string $status,
        ?User $actor,
        float|string|null $oldMarks,
        float|string|null $newMarks,
        ?string $note = null
    ): ExamRevaluationRequest {
        return DB::transaction(function () use ($request, $status, $actor, $oldMarks, $newMarks, $note) {
            $request->update(['status' => $status]);

            ExamRevaluationAction::create([
                'request_id' => $request->id,
                'action_by' => $actor?->id ?? auth()->id(),
                'old_marks' => $oldMarks,
                'new_marks' => $newMarks,
                'note' => $note ?? ucfirst(str_replace('_', ' ', $status)),
            ]);

            return $request->fresh();
        });
    }

    /**
     * @param  array<int, string>  $allowed
     *
     * @throws ValidationException
     */
    private function mustBe(ExamRevaluationRequest $request, array $allowed, string $verb): void
    {
        if (! in_array((string) $request->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'A recheck that is already %s cannot be %s.',
                    str_replace('_', ' ', (string) $request->status),
                    $verb
                ),
            ]);
        }
    }
}
