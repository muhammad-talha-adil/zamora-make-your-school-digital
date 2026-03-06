<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\ExamRevaluationRequest;
use App\Models\Exam\Exam;
use App\Models\Campus;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamRevaluationController extends Controller
{
    /**
     * Display revaluations page.
     */
    public function indexPage()
    {
        return Inertia::render('Exam/Revaluations/Index', [
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
        ]);
    }

    /**
     * Submit revaluation request (API).
     */
    public function request(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'exam_paper_id' => 'required|exists:exam_papers,id',
            'expected_marks' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        $revaluation = ExamRevaluationRequest::create([
            ...$validated,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Revaluation request submitted', 'data' => $revaluation], 201);
    }

    /**
     * Display revaluation index (API).
     */
    public function index(Request $request)
    {
        $examId = $request->query('exam_id');
        $status = $request->query('status');

        $query = ExamRevaluationRequest::with(['student.user', 'exam', 'examResultLine.examPaper.subject']);

        if ($examId) {
            $query->where('exam_id', $examId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $revaluations = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $revaluations]);
    }

    public function review($id)
    {
        return response()->json(['message' => 'Revaluation review', 'id' => $id]);
    }

    public function approve($id)
    {
        return response()->json(['message' => 'Revaluation approved', 'id' => $id]);
    }

    public function reject($id)
    {
        return response()->json(['message' => 'Revaluation rejected', 'id' => $id]);
    }

    public function applyChange($id)
    {
        return response()->json(['message' => 'Change applied', 'id' => $id]);
    }

    public function history($id)
    {
        return response()->json(['message' => 'Revaluation history', 'id' => $id]);
    }
}
