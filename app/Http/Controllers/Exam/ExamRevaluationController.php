<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\Exam\ExamRevaluationRequest;
use App\Models\SchoolClass;
use App\Services\Exam\ExamRevaluationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Rechecking a paper.
 *
 * Everything here used to be a stub or worse: `request()` wrote four columns
 * the table does not have, `index()` filtered on a fifth, and approve, reject
 * and apply returned a sentence of English and changed nothing. The decisions
 * live in `ExamRevaluationService` now; this only translates them to and from
 * HTTP.
 */
class ExamRevaluationController extends Controller
{
    public function __construct(private ExamRevaluationService $revaluations) {}

    /**
     * Display revaluations page.
     */
    public function indexPage(Request $request)
    {
        return Inertia::render('Exam/Revaluations/Index', [
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
            'can' => [
                'manage' => $request->user()?->can('exam.revaluation.manage') ?? false,
            ],
        ]);
    }

    /**
     * Apply for a recheck of one paper (API).
     *
     * The screen posts the child and the paper; the result line is the thing
     * the recheck is actually about, and either shape is accepted.
     */
    public function request(Request $request)
    {
        $validated = $request->validate([
            'exam_result_line_id' => ['nullable', 'integer', 'exists:exam_result_lines,id'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'exam_paper_id' => ['nullable', 'integer', 'exists:exam_papers,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $line = $this->resolveLine($validated);

        // A family applies for their own child's paper; a member of staff
        // applies on anybody's within their reach. Both go through the policy.
        $header = $line->resultHeader;

        if ($header && ! $request->user()->can('viewOwn', $header)) {
            $this->authorize('manageRevaluation', $header);
        }

        $revaluation = $this->revaluations->open(
            $line,
            $request->user(),
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Revaluation request submitted',
            'data' => $revaluation,
        ], 201);
    }

    /**
     * Display revaluation index (API).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ExamResultHeader::class);

        $query = ExamRevaluationRequest::with([
            'requestedBy',
            'examResultLine.examPaper.subject',
            'examResultLine.resultHeader.student.user',
            'examResultLine.resultHeader.exam',
        ]);

        // `exam_id` is not on this table — it is reached through the result
        // line. Filtering on it directly threw `Unknown column` every time.
        if ($examId = $request->query('exam_id')) {
            $query->whereHas('examResultLine.resultHeader', fn ($q) => $q->where('exam_id', $examId));
        }

        // Only the rechecks whose results this user may read.
        $query->whereHas(
            'examResultLine.resultHeader',
            fn ($q) => $q->visibleTo($request->user())
        );

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json(['data' => $query->orderByDesc('created_at')->get()]);
    }

    /**
     * Mark a recheck as being looked at (API).
     */
    public function review(int|string $id, Request $request)
    {
        $revaluationRequest = ExamRevaluationRequest::findOrFail($id);
        $this->authorizeRecheck($revaluationRequest);

        $revaluation = $this->revaluations->review(
            $revaluationRequest,
            $request->user()
        );

        return response()->json(['message' => 'Revaluation under review', 'data' => $revaluation]);
    }

    /**
     * The mark is wrong, and this is what it should be (API).
     */
    public function approve(int|string $id, Request $request)
    {
        $validated = $request->validate([
            'new_marks' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $revaluationRequest = ExamRevaluationRequest::findOrFail($id);
        $this->authorizeRecheck($revaluationRequest);

        $revaluation = $this->revaluations->approve(
            $revaluationRequest,
            (float) $validated['new_marks'],
            $request->user(),
            $validated['note'] ?? null
        );

        return response()->json(['message' => 'Revaluation approved', 'data' => $revaluation]);
    }

    /**
     * The mark stands (API).
     */
    public function reject(int|string $id, Request $request)
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $revaluationRequest = ExamRevaluationRequest::findOrFail($id);
        $this->authorizeRecheck($revaluationRequest);

        $revaluation = $this->revaluations->reject(
            $revaluationRequest,
            $request->user(),
            $validated['note'] ?? null
        );

        return response()->json(['message' => 'Revaluation rejected', 'data' => $revaluation]);
    }

    /**
     * Write the corrected mark onto the result (API).
     */
    public function applyChange(int|string $id, Request $request)
    {
        $revaluationRequest = ExamRevaluationRequest::findOrFail($id);
        $this->authorizeRecheck($revaluationRequest);

        $revaluation = $this->revaluations->apply(
            $revaluationRequest,
            $request->user()
        );

        return response()->json(['message' => 'Change applied', 'data' => $revaluation]);
    }

    /**
     * Everything that has happened to a recheck (API).
     */
    public function history(int|string $id)
    {
        $revaluation = ExamRevaluationRequest::findOrFail($id);
        $header = $revaluation->examResultLine?->resultHeader;

        if ($header && ! request()->user()->can('viewOwn', $header)) {
            $this->authorize('manageRevaluation', $header);
        }

        return response()->json(['data' => $this->revaluations->historyOf($revaluation)]);
    }

    /**
     * Whether this user may decide a recheck.
     *
     * The recheck is about one child's mark, so the reach is the result's:
     * their campus, their class. Approve, reject and apply used to be open to
     * every signed-in user, and returned a sentence of English regardless.
     */
    private function authorizeRecheck(ExamRevaluationRequest $revaluation): void
    {
        $header = $revaluation->examResultLine?->resultHeader;

        if ($header) {
            $this->authorize('manageRevaluation', $header);
        }
    }

    /**
     * The result line a recheck is about.
     *
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    private function resolveLine(array $validated): ExamResultLine
    {
        if (! empty($validated['exam_result_line_id'])) {
            return ExamResultLine::findOrFail($validated['exam_result_line_id']);
        }

        if (empty($validated['exam_id']) || empty($validated['student_id']) || empty($validated['exam_paper_id'])) {
            throw ValidationException::withMessages([
                'exam_result_line_id' => 'Say which mark is being rechecked: the result line, or the exam, child and paper.',
            ]);
        }

        $line = ExamResultLine::where('exam_paper_id', $validated['exam_paper_id'])
            ->whereIn('result_header_id', ExamResultHeader::where('exam_id', $validated['exam_id'])
                ->where('student_id', $validated['student_id'])
                ->select('id'))
            ->first();

        if (! $line) {
            throw ValidationException::withMessages([
                'exam_paper_id' => 'This child has no mark for that paper, so there is nothing to recheck.',
            ]);
        }

        return $line;
    }
}
