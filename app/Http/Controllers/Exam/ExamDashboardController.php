<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamStudentRegistration;
use App\Models\Exam\GradeSystem;
use App\Models\Exam\GradeSystemItem;
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

        // ============================
        // Overview Statistics
        // ============================

        // Total exams count
        $totalExams = Exam::count();

        // Total papers count
        $totalPapers = ExamPaper::count();

        // Total registrations count
        $totalRegistrations = ExamStudentRegistration::count();

        // Total results entered
        $totalResults = ExamResultHeader::count();

        // Exams by status
        $examsByStatus = Exam::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Papers by status
        $papersByStatus = ExamPaper::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // ============================
        // Recent Exams
        // ============================
        $recentExams = Exam::with(['examType', 'session'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'status' => $exam->status,
                    'start_date' => $exam->start_date?->format('Y-m-d'),
                    'end_date' => $exam->end_date?->format('Y-m-d'),
                    'exam_type' => $exam->examType?->name,
                    'papers_count' => $exam->examPapers()->count(),
                    'registrations_count' => $exam->studentRegistrations()->count(),
                    'results_count' => $exam->resultHeaders()->count(),
                ];
            });

        // ============================
        // Upcoming Papers (next 7 days)
        // ============================
        $upcomingPapers = ExamPaper::with(['exam', 'subject', 'class', 'section'])
            ->where('paper_date', '>=', now()->toDateString())
            ->where('paper_date', '<=', now()->addDays(7)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('paper_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get()
            ->map(function ($paper) {
                return [
                    'id' => $paper->id,
                    'exam_name' => $paper->exam?->name,
                    'subject_name' => $paper->subject?->name,
                    'class_name' => $paper->class?->name,
                    'section_name' => $paper->section?->name,
                    'paper_date' => $paper->paper_date?->format('Y-m-d'),
                    'start_time' => $paper->start_time,
                    'end_time' => $paper->end_time,
                    'total_marks' => $paper->total_marks,
                    'status' => $paper->status,
                ];
            });

        // ============================
        // Today's Papers
        // ============================
        $todayPapers = ExamPaper::with(['exam', 'subject', 'class', 'section'])
            ->where('paper_date', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->map(function ($paper) {
                return [
                    'id' => $paper->id,
                    'exam_name' => $paper->exam?->name,
                    'subject_name' => $paper->subject?->name,
                    'class_name' => $paper->class?->name,
                    'section_name' => $paper->section?->name,
                    'start_time' => $paper->start_time,
                    'end_time' => $paper->end_time,
                    'total_marks' => $paper->total_marks,
                ];
            });

        // ============================
        // Active Exams
        // ============================
        $activeExams = Exam::with(['examType'])
            ->whereIn('status', ['scheduled', 'active', 'marking'])
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($exam) {
                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'status' => $exam->status,
                    'start_date' => $exam->start_date?->format('Y-m-d'),
                    'end_date' => $exam->end_date?->format('Y-m-d'),
                    'exam_type' => $exam->examType?->name,
                    'papers_count' => $exam->examPapers()->count(),
                    'registrations_count' => $exam->studentRegistrations()->count(),
                ];
            });

        // ============================
        // Marking Progress (if exam selected)
        // ============================
        $markingProgress = null;
        $toppers = [];
        $gradeDistribution = [];

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
            $gradeDistributionData = ExamResultHeader::where('exam_id', $examId)
                ->whereNotNull('overall_grade_item_id_cache')
                ->select('overall_grade_item_id_cache')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('overall_grade_item_id_cache')
                ->get()
                ->map(function ($item) {
                    $gradeItem = GradeSystemItem::find($item->overall_grade_item_id_cache);

                    return [
                        'grade' => $gradeItem?->grade_label ?? 'N/A',
                        'count' => $item->count,
                    ];
                });
            $gradeDistribution = $gradeDistributionData;

            // Toppers for this exam
            $toppers = ExamResultHeader::where('exam_id', $examId)
                ->whereNotNull('total_obtained_cache')
                ->with(['student.user', 'class', 'section'])
                ->orderByDesc('overall_percentage_cache')
                ->limit(5)
                ->get()
                ->map(function ($result) {
                    return [
                        'id' => $result->id,
                        'student_name' => $result->student?->user?->name,
                        'class_name' => $result->class?->name,
                        'section_name' => $result->section?->name,
                        'total_marks' => $result->total_obtained_cache,
                        'percentage' => $result->overall_percentage_cache,
                        'grade' => $result->overallGradeItem?->grade_label,
                    ];
                });
        }

        return response()->json([
            // Overview
            'total_exams' => $totalExams,
            'total_papers' => $totalPapers,
            'total_registrations' => $totalRegistrations,
            'total_results' => $totalResults,

            // Status breakdowns
            'exams_by_status' => $examsByStatus,
            'papers_by_status' => $papersByStatus,

            // Recent & Active
            'recent_exams' => $recentExams,
            'active_exams' => $activeExams,

            // Papers
            'upcoming_papers' => $upcomingPapers,
            'today_papers' => $todayPapers,

            // Selected exam data
            'selected_exam' => $examId ? Exam::find($examId) : null,
            'marking_progress' => $markingProgress,
            'grade_distribution' => $gradeDistribution,
            'toppers' => $toppers,

            // Settings
            'active_grade_system' => GradeSystem::getActiveGradeSystem(),
        ]);
    }
}
