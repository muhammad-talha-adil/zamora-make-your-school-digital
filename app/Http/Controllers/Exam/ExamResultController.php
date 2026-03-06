<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamResultController extends Controller
{
    /**
     * Display results page.
     */
    public function indexPage(Request $request)
    {
        return Inertia::render('Exam/Results/Index', [
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
            'sections' => Section::all(),
            'preSelectedExamId' => $request->get('exam_id'),
        ]);
    }

    /**
     * Get filter options (campuses, classes, sections) for a specific exam
     */
    public function getFilterOptions(Request $request)
    {
        $examId = $request->query('exam_id');
        
        if (!$examId) {
            return response()->json([
                'campuses' => Campus::all(),
                'classes' => SchoolClass::all(),
                'sections' => Section::all(),
            ]);
        }
        
        // Get unique campuses, classes, sections that have results for this exam
        $resultHeaders = ExamResultHeader::where('exam_id', $examId);
        
        $campuses = ExamResultHeader::where('exam_id', $examId)
            ->whereNotNull('campus_id')
            ->with('campus')
            ->get()
            ->pluck('campus')
            ->filter()
            ->unique('id')
            ->values();
            
        $classes = ExamResultHeader::where('exam_id', $examId)
            ->whereNotNull('class_id')
            ->with('class')
            ->get()
            ->pluck('class')
            ->filter()
            ->unique('id')
            ->values();
            
        $sections = ExamResultHeader::where('exam_id', $examId)
            ->whereNotNull('section_id')
            ->with('section')
            ->get()
            ->pluck('section')
            ->filter()
            ->unique('id')
            ->values();
            
        return response()->json([
            'campuses' => $campuses,
            'classes' => $classes,
            'sections' => $sections,
        ]);
    }

    /**
     * Display result index (API).
     */
    public function index(Request $request)
    {
        $examId = $request->query('exam_id');
        $campusId = $request->query('campus_id');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);

        // Handle "all" values as null for filtering
        $campusId = $campusId === 'all' || $campusId === '' || $campusId === null ? null : $campusId;
        $classId = $classId === 'all' || $classId === '' || $classId === null ? null : $classId;
        $sectionId = $sectionId === 'all' || $sectionId === '' || $sectionId === null ? null : $sectionId;

        if (!$examId) {
            return response()->json([
                'success' => false,
                'message' => 'Exam ID is required',
                'data' => [],
                'toppers' => [],
                'pagination' => null,
            ], 400);
        }

        $query = ExamResultHeader::with(['student.user', 'campus', 'class', 'section', 'exam', 'overallGradeItem', 'examResultLines'])
            ->where('exam_id', $examId);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        // Student search
        if ($search) {
            $query->whereHas('student.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination for sorting
        $allResults = $query->get()->map(function ($header) {
            $totalMaxMarks = $header->examResultLines->sum('total_marks_snapshot');
            return [
                'id' => $header->id,
                'student' => $header->student?->user?->name,
                'student_id' => $header->student_id,
                'campus' => $header->campus ? ['id' => $header->campus->id, 'name' => $header->campus->name] : null,
                'class' => $header->class ? ['id' => $header->class->id, 'name' => $header->class->name] : null,
                'section' => $header->section ? ['id' => $header->section->id, 'name' => $header->section->name] : null,
                'total_max_marks' => $totalMaxMarks,
                'total_obtained' => $header->total_obtained_cache,
                'percentage' => $header->overall_percentage_cache,
                'overallGradeItem' => $header->overallGradeItem ? ['grade_letter' => $header->overallGradeItem->grade_letter] : null,
                'status' => $header->status,
                'exam_id' => $header->exam_id,
            ];
        });

        // Sort by percentage descending
        $sortedResults = $allResults->sortByDesc('percentage')->values();
        
        // Get top 5 toppers (from all results, not just current page)
        $toppers = $sortedResults->take(5);

        // Paginate the sorted results
        $total = $sortedResults->count();
        $perPage = min(max(intval($perPage), 1), 100); // Clamp between 1 and 100
        $totalPages = ceil($total / $perPage);
        $currentPage = min(max(intval($page), 1), max($totalPages, 1));
        
        $paginatedResults = $sortedResults->forPage($currentPage, $perPage)->values();

        // Build pagination response
        $pagination = [
            'data' => $paginatedResults,
            'current_page' => $currentPage,
            'last_page' => $totalPages,
            'per_page' => $perPage,
            'total' => $total,
            'from' => $total > 0 ? (($currentPage - 1) * $perPage) + 1 : null,
            'to' => min($currentPage * $perPage, $total),
            'links' => [],
        ];

        // Generate pagination links
        $pagination['links'] = $this->generatePaginationLinks($currentPage, $totalPages);

        return response()->json([
            'success' => true,
            'data' => $paginatedResults,
            'toppers' => $toppers,
            'pagination' => $pagination,
            'total' => $total,
        ]);
    }

    /**
     * Generate pagination links
     */
    private function generatePaginationLinks(int $currentPage, int $totalPages): array
    {
        $links = [];
        
        // Previous page link
        $links[] = [
            'url' => $currentPage > 1 ? $currentPage - 1 : null,
            'label' => '&laquo; Previous',
            'active' => false,
        ];

        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);

        for ($i = $start; $i <= $end; $i++) {
            $links[] = [
                'url' => $i,
                'label' => (string) $i,
                'active' => $i === $currentPage,
            ];
        }

        // Next page link
        $links[] = [
            'url' => $currentPage < $totalPages ? $currentPage + 1 : null,
            'label' => 'Next &raquo;',
            'active' => false,
        ];

        return $links;
    }

    public function studentResult($studentId, $examId)
    {
        return response()->json(['message' => 'Student result', 'studentId' => $studentId, 'examId' => $examId]);
    }

    public function groupResultSheet($groupId)
    {
        return response()->json(['message' => 'Group result sheet', 'groupId' => $groupId]);
    }

    public function paperWiseReport($paperId)
    {
        return response()->json(['message' => 'Paper wise report', 'paperId' => $paperId]);
    }

    public function exportPDF($groupId)
    {
        return response()->json(['message' => 'PDF exported', 'groupId' => $groupId]);
    }

    public function exportExcel($groupId)
    {
        return response()->json(['message' => 'Excel exported', 'groupId' => $groupId]);
    }

    public function publishPreview($offeringId)
    {
        return response()->json(['message' => 'Publish preview', 'offeringId' => $offeringId]);
    }
}
