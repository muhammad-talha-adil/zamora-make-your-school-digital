<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamType;
use App\Models\Session;
use App\Services\Exam\ExamLifecycleService;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    protected $examService;

    public function __construct(ExamService $examService, private ExamLifecycleService $lifecycle)
    {
        $this->examService = $examService;
    }

    /**
     * Display a listing of exams (Page).
     */
    public function indexPage(Request $request): Response
    {
        $this->authorize('viewAny', Exam::class);

        $filters = $request->all();
        $exams = $this->examService->list($filters);

        return Inertia::render('Exam/Exams/Index', [
            'exams' => $exams,
            'filters' => $filters,
            'examTypes' => ExamType::where('is_active', true)->get(),
            'sessions' => Session::where('is_active', true)->get(),
        ]);
    }

    /**
     * Show the form for creating a new exam (Page).
     */
    public function createPage(): Response
    {
        $this->authorize('create', Exam::class);

        return Inertia::render('Exam/Exams/Create', [
            'examTypes' => ExamType::where('is_active', true)->get(),
            'sessions' => Session::where('is_active', true)->get(),
        ]);
    }

    /**
     * Display the specified exam (Page).
     */
    public function showPage($id): Response
    {
        $exam = Exam::with(['examType', 'session', 'examPapers.subject', 'examPapers.class', 'examPapers.section'])
            ->findOrFail($id);

        $this->authorize('view', $exam);

        return Inertia::render('Exam/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    /**
     * Show the form for editing the specified exam (Page).
     */
    public function editPage($id): Response
    {
        $exam = Exam::findOrFail($id);
        $this->authorize('update', $exam);

        $examData = $exam->toArray();

        // Format dates for the form (Y-m-d for HTML date input)
        $examData['start_date'] = $exam->start_date ? $exam->start_date->format('Y-m-d') : '';
        $examData['end_date'] = $exam->end_date ? $exam->end_date->format('Y-m-d') : '';

        return Inertia::render('Exam/Exams/Edit', [
            'exam' => $examData,
            'examTypes' => ExamType::where('is_active', true)->get(),
            'sessions' => Session::where('is_active', true)->get(),
        ]);
    }

    /**
     * Display a listing of exams (API).
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        $exams = $this->examService->list($filters);

        return response()->json(['data' => $exams]);
    }

    /**
     * Store a newly created exam (API).
     */
    public function store(StoreExamRequest $request)
    {
        $this->authorize('create', Exam::class);

        $exam = $this->examService->create($request->validated());

        return to_route('exam.index-page')->with('success', 'Exam created successfully!');
    }

    /**
     * Display the specified exam (API).
     */
    public function show($id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorize('view', $exam);

        return response()->json(['data' => $exam]);
    }

    /**
     * Update the specified exam (API).
     */
    public function update(UpdateExamRequest $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorize('update', $exam);

        $exam = $this->examService->update($exam, $request->validated());

        return to_route('exam.index-page')->with('success', 'Exam updated successfully!');
    }

    /**
     * Change exam status (API).
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:scheduled,active,marking,published,completed,cancelled',
        ]);

        $exam = Exam::findOrFail($id);
        $this->authorize('update', $exam);
        $exam = $this->examService->changeStatus($exam, $request->status);

        return response()->json(['message' => 'Status changed successfully', 'data' => $exam]);
    }

    /**
     * Publish an exam.
     *
     * The status moves with the timestamp — they used to disagree — and an exam
     * that is not ready is refused with the reason, unless the school says to
     * publish it anyway.
     */
    public function publish(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorize('publish', $exam);

        try {
            $exam = $this->lifecycle->publish($exam, $request->user(), (bool) $request->boolean('force'));
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['exam' => $this->lifecycle->readinessOf($exam)],
            ], 422);
        }

        return response()->json(['message' => 'Exam published successfully', 'data' => $exam]);
    }

    /**
     * Take a published result back off the board.
     */
    public function unpublish(Request $request, $id)
    {
        $exam = $this->lifecycle->unpublish(Exam::findOrFail($id));
        $this->authorize('unpublish', $exam);

        return response()->json(['message' => 'Exam unpublished successfully', 'data' => $exam]);
    }

    /**
     * What stands between this exam and being published.
     */
    public function readiness($id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorize('view', $exam);
        $problems = $this->lifecycle->readinessOf($exam);

        return response()->json(['data' => ['is_ready' => $problems === [], 'problems' => $problems]]);
    }

    /**
     * Lock an exam.
     */
    public function lock(Request $request, $id)
    {
        $exam = $this->lifecycle->lock(Exam::findOrFail($id), $request->user());
        $this->authorize('lock', $exam);

        return response()->json(['message' => 'Exam locked successfully', 'data' => $exam]);
    }

    /**
     * Unlock an exam.
     */
    public function unlock(Request $request, $id)
    {
        $exam = $this->lifecycle->unlock(Exam::findOrFail($id));
        $this->authorize('unlock', $exam);

        return response()->json(['message' => 'Exam unlocked successfully', 'data' => $exam]);
    }

    /**
     * Remove the specified exam (API).
     */
    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorize('delete', $exam);
        $this->examService->delete($exam);

        return response()->json(['message' => 'Exam deleted successfully']);
    }
}
