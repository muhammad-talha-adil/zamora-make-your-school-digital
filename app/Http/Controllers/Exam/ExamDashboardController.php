<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamStudentRegistration;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\GradeSystem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamDashboardController extends Controller
{
    /**
     * Display exam dashboard page.
     */
    public function indexPage()
    {
        return Inertia::render('Exam/Dashboard/Index');
    }

    /**
     * Get dashboard statistics.
     */
    public function getStats(Request $request)
    {
        $examId = $request->query('exam_id');
        
        // Total exams count
        $totalExams = Exam::count();
        
        // Exams by status
        $examsByStatus = Exam::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        // Latest exam
        $latestExam = Exam::orderBy('created_at', 'desc')->first();
        
        // Marking progress (if exam selected)
        $markingProgress = null;
        if ($examId) {
            $exam = Exam::findOrFail($examId);
            
            // Get total registered students
            $totalRegistered = ExamStudentRegistration::where('exam_id', $examId)->count();
            
            // Get students with results
            $markedStudents = ExamResultHeader::where('exam_id', $examId)
                ->whereNotNull('total_obtained_cache')
                ->count();
            
            $markingProgress = [
                'total_registered' => $totalRegistered,
                'marked' => $markedStudents,
                'pending' => $totalRegistered - $markedStudents,
                'percentage' => $totalRegistered > 0 
                    ? round(($markedStudents / $totalRegistered) * 100, 2) 
                    : 0,
            ];
            
            // Grade distribution
            $gradeDistribution = ExamResultHeader::where('exam_id', $examId)
                ->whereNotNull('overall_grade_item_id_cache')
                ->select('overall_grade_item_id_cache')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('overall_grade_item_id_cache')
                ->with('overallGradeItem')
                ->get()
                ->map(function ($item) {
                    return [
                        'grade' => $item->overallGradeItem?->grade_label ?? 'N/A',
                        'count' => $item->count,
                    ];
                });
        }
        
        return response()->json([
            'total_exams' => $totalExams,
            'latest_exam' => $latestExam ? [
                'id' => $latestExam->id,
                'name' => $latestExam->name,
                'status' => $latestExam->status,
                'start_date' => $latestExam->start_date,
                'end_date' => $latestExam->end_date,
            ] : null,
            'exams_by_status' => $examsByStatus,
            'marking_progress' => $markingProgress,
            'grade_distribution' => $gradeDistribution ?? [],
            'active_grade_system' => GradeSystem::getActiveGradeSystem(),
        ]);
    }
}
