<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamType;
use App\Models\Session;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    protected $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * Display a listing of exams (Page).
     */
    public function indexPage(Request $request): Response
    {
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
        $exam = $this->examService->create($request->validated());
        return to_route('exam.index-page')->with('success', 'Exam created successfully!');
    }

    /**
     * Display the specified exam (API).
     */
    public function show($id)
    {
        $exam = Exam::findOrFail($id);
        return response()->json(['data' => $exam]);
    }

    /**
     * Update the specified exam (API).
     */
    public function update(UpdateExamRequest $request, $id)
    {
        $exam = Exam::findOrFail($id);
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
        $exam = $this->examService->changeStatus($exam, $request->status);
        return response()->json(['message' => 'Status changed successfully', 'data' => $exam]);
    }

    /**
     * Publish an exam.
     */
    public function publish(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update([
            'published_at' => now(),
        ]);
        return response()->json(['message' => 'Exam published successfully', 'data' => $exam]);
    }

    /**
     * Lock an exam.
     */
    public function lock(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update([
            'is_locked' => true,
            'locked_at' => now(),
        ]);
        return response()->json(['message' => 'Exam locked successfully', 'data' => $exam]);
    }

    /**
     * Unlock an exam.
     */
    public function unlock(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $exam->update([
            'is_locked' => false,
            'locked_at' => null,
        ]);
        return response()->json(['message' => 'Exam unlocked successfully', 'data' => $exam]);
    }

    /**
     * Remove the specified exam (API).
     */
    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $this->examService->delete($exam);
        return response()->json(['message' => 'Exam deleted successfully']);
    }
}
