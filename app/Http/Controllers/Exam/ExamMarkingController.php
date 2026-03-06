<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\SaveMarksRequest;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\Exam\Exam;
use App\Models\Exam\GradeSystem;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Services\Exam\ExamMarkingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamMarkingController extends Controller
{
    protected $markingService;

    public function __construct(ExamMarkingService $markingService)
    {
        $this->markingService = $markingService;
    }

    /**
     * Show exam selection page.
     */
    public function selectPage(Request $request): Response
    {
        $gradeSystems = GradeSystem::orderBy('name')->get();

        return Inertia::render('Exam/Marking/Select', [
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
            'sections' => Section::all(),
            'gradeSystems' => $gradeSystems,
            'preSelectedExamId' => $request->get('exam_id'),
        ]);
    }

    /**
     * Show grid-based marking page.
     */
    public function gridPage(Request $request): Response
    {
        $examId = $request->get('exam_id');
        $campusId = $request->get('campus_id');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $gradeSystemId = $request->get('grade_system_id');

        // Convert to integer if not null
        $examId = $examId ? (int) $examId : null;
        $campusId = $campusId ? (int) $campusId : null;
        $classId = $classId ? (int) $classId : null;
        $sectionId = $sectionId ? (int) $sectionId : null;
        $gradeSystemId = $gradeSystemId ? (int) $gradeSystemId : null;

        // Get default grade system if not specified
        $defaultGradeSystem = GradeSystem::where('is_default', true)->first();
        $activeGradeSystem = GradeSystem::where('is_active', true)->first();
        $selectedGradeSystemId = $gradeSystemId ?? $defaultGradeSystem?->id ?? $activeGradeSystem?->id;

        // Get all grade systems for dropdown
        $gradeSystems = GradeSystem::orderBy('name')->get();

        return Inertia::render('Exam/Marking/Grid', [
            'exams' => Exam::all()->toArray(),
            'campuses' => Campus::all()->toArray(),
            'classes' => SchoolClass::all()->toArray(),
            'sections' => Section::all()->toArray(),
            'gradeSystems' => $gradeSystems->toArray(),
            'selectedGradeSystemId' => $selectedGradeSystemId,
            'filters' => [
                'exam_id' => $examId,
                'campus_id' => $campusId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'grade_system_id' => $selectedGradeSystemId,
            ],
        ]);
    }

    /**
     * Get grid data for marking (API).
     */
    public function getGrid(Request $request)
    {
        $examId = $request->query('exam_id');
        $campusId = $request->query('campus_id');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        $gradeSystemId = $request->query('grade_system_id');

        $exam = Exam::findOrFail($examId);

        // Check if exam is locked
        if ($exam->is_locked) {
            return response()->json(['error' => 'This exam is locked. Cannot enter marks.'], 403);
        }

        // Get papers filtered by class and section
        $papersQuery = ExamPaper::with(['subject', 'class', 'section', 'campus'])
            ->where('exam_id', $examId)
            ->where('status', '!=', 'cancelled');

        // Filter papers by class if specified
        if ($classId) {
            $papersQuery->where('class_id', $classId);
        }

        // Filter papers by section if specified
        if ($sectionId) {
            $papersQuery->where('section_id', $sectionId);
        }

        $papers = $papersQuery->orderBy('paper_date')
            ->orderBy('start_time')
            ->get();

        // Get the sections that have papers (for "All Sections" filter)
        $paperSectionIds = $papers->pluck('section_id')->filter()->unique()->toArray();
        $paperClassIds = $papers->pluck('class_id')->filter()->unique()->toArray();

        // Check if any papers exist for this class/section combination
        if ($papers->isEmpty()) {
            return response()->json([
                'error' => 'No examination papers have been registered for the selected class and section combination. Please configure papers in the Exam Papers section first.',
                'papers' => [],
                'students' => [],
                'is_locked' => false,
                'total_max_marks' => 0,
            ]);
        }

        // Calculate total max marks for all papers
        $totalMaxMarksAllPapers = $papers->sum('total_marks');

        // Get students based on selection - only those with papers
        $studentsQuery = Student::whereHas('enrollmentRecords', function ($q) use ($exam, $campusId, $classId, $sectionId, $paperSectionIds, $paperClassIds) {
            $q->where('session_id', $exam->session_id)
                ->whereNull('leave_date');

            if ($campusId) {
                $q->where('campus_id', $campusId);
            }

            // If specific section selected, only that section
            if ($sectionId) {
                $q->where('section_id', $sectionId);
            } elseif ($classId) {
                // For "All Sections", only show students in sections that have papers
                if (!empty($paperSectionIds)) {
                    $q->whereIn('section_id', $paperSectionIds);
                }
                // Also filter by class
                $q->where('class_id', $classId);
            } else {
                // No class specified - filter by classes that have papers
                if (!empty($paperClassIds)) {
                    $q->whereIn('class_id', $paperClassIds);
                }
            }
        })->with([
                    'user',
                    'enrollmentRecords.campus',
                    'enrollmentRecords.class',
                    'enrollmentRecords.section'
                ]);

        $students = $studentsQuery->get();

        // Get existing marks
        $studentIds = $students->pluck('id')->toArray();

        $existingMarks = ExamResultLine::whereHas('resultHeader', function ($q) use ($examId, $studentIds) {
            $q->where('exam_id', $examId)
                ->whereIn('student_id', $studentIds);
        })->with('resultHeader')->get()
            ->groupBy('resultHeader.student_id');

        // Get grade system - use selected one or fall back to default/active
        $gradeSystem = null;
        if ($gradeSystemId) {
            $gradeSystem = GradeSystem::with('gradeSystemItems')->find($gradeSystemId);
        }
        
        // Fall back to default or active grade system
        if (!$gradeSystem) {
            $gradeSystem = GradeSystem::where('is_default', true)
                ->orWhere('is_active', true)
                ->first();
            if ($gradeSystem) {
                $gradeSystem->load('gradeSystemItems');
            }
        }
        
        $gradeItems = $gradeSystem?->gradeSystemItems ?? [];

        // Build response
        $gridData = $students->map(function ($student) use ($papers, $existingMarks, $gradeItems) {
            $enrollment = $student->enrollmentRecords->first();

            // Use all papers for this exam (no filtering by scope)
            $applicablePapers = $papers;

            $marks = [];
            $totalObtained = 0;
            $totalMaxMarks = 0;

            foreach ($applicablePapers as $paper) {
                $studentMarks = $existingMarks->get($student->id)?->firstWhere('exam_paper_id', $paper->id);

                $obtained = $studentMarks?->obtained_marks ?? null;

                $marks[$paper->id] = [
                    'obtained' => $obtained,
                    'is_absent' => $studentMarks?->is_absent ?? false,
                ];

                if ($obtained !== null && !($studentMarks?->is_absent ?? false)) {
                    $totalObtained += $obtained;
                    $totalMaxMarks += $paper->total_marks;
                }
            }

            $percentage = $totalMaxMarks > 0 ? round(($totalObtained / $totalMaxMarks) * 100, 2) : 0;

            // Calculate grade
            $grade = null;
            foreach ($gradeItems as $item) {
                if ($percentage >= $item->min_percentage) {
                    $grade = $item;
                    if ($item->max_percentage !== null && $percentage <= $item->max_percentage) {
                        break;
                    }
                }
            }

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->user?->name ?? 'Unknown',
                    'campus' => $enrollment?->campus?->name ?? 'N/A',
                    'class' => $enrollment?->class?->name ?? 'N/A',
                    'section' => $enrollment?->section?->name ?? 'N/A',
                ],
                'enrollment_id' => $enrollment?->id,
                'marks' => $marks,
                'total_obtained' => $totalObtained,
                'total_max_marks' => $totalMaxMarks,
                'percentage' => $percentage,
                'grade' => $grade?->grade_letter ?? 'N/A',
            ];
        });

        return response()->json([
            'papers' => $papers->map(fn($p) => [
                'id' => $p->id,
                'subject' => $p->subject?->name,
                'total_marks' => $p->total_marks,
                'passing_marks' => $p->passing_marks,
                'scope_type' => $p->scope_type,
            ]),
            'students' => $gridData,
            'is_locked' => $exam->is_locked,
            'total_max_marks' => $totalMaxMarksAllPapers,
        ]);
    }

    /**
     * Save a single row of marks (API).
     */
    public function saveRow(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_id' => 'required|exists:students,id',
            'enrollment_id' => 'nullable|exists:student_enrollment_records,id',
            'marks' => 'required|array',
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        if ($exam->is_locked) {
            return response()->json(['error' => 'This exam is locked'], 403);
        }

        $studentId = $validated['student_id'];
        $enrollmentId = $validated['enrollment_id'];
        $marksData = $validated['marks'];

        // Get enrollment info
        $enrollment = \App\Models\StudentEnrollmentRecord::find($enrollmentId);

        // Get or create result header
        $header = ExamResultHeader::firstOrCreate(
            [
                'exam_id' => $validated['exam_id'],
                'student_id' => $studentId,
            ],
            [
                'campus_id' => $enrollment?->campus_id,
                'class_id' => $enrollment?->class_id,
                'section_id' => $enrollment?->section_id,
                'status' => 'draft',
            ]
        );

        // Save each paper's marks
        foreach ($marksData as $paperId => $markData) {
            $paper = ExamPaper::findOrFail($paperId);

            ExamResultLine::updateOrCreate(
                [
                    'result_header_id' => $header->id,
                    'exam_paper_id' => $paperId,
                ],
                [
                    'student_id' => $studentId,
                    'total_marks_snapshot' => $paper->total_marks,
                    'passing_marks_snapshot' => $paper->passing_marks,
                    'obtained_marks' => $markData['obtained'] ?? null,
                    'is_absent' => $markData['is_absent'] ?? false,
                ]
            );
        }

        // Recalculate totals
        $this->recalculateHeader($header);

        return response()->json(['message' => 'Marks saved successfully']);
    }

    /**
     * Save bulk marks (API).
     */
    public function saveBulk(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'students' => 'required|array',
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        if ($exam->is_locked) {
            return response()->json(['error' => 'This exam is locked'], 403);
        }

        foreach ($validated['students'] as $studentData) {
            $request->merge([
                'exam_id' => $validated['exam_id'],
                'student_id' => $studentData['student_id'],
                'enrollment_id' => $studentData['enrollment_id'] ?? null,
                'marks' => $studentData['marks'] ?? [],
            ]);

            // Call saveRow logic for each student
            $this->saveRow($request);
        }

        return response()->json(['message' => 'Bulk save completed successfully']);
    }

    /**
     * Recalculate result header totals and grade.
     */
    private function recalculateHeader(ExamResultHeader $header)
    {
        $lines = $header->examResultLines;

        $totalObtained = 0;
        $totalMaxMarks = 0;

        foreach ($lines as $line) {
            if (!$line->is_absent && $line->obtained_marks !== null) {
                $totalObtained += $line->obtained_marks;
                $totalMaxMarks += $line->total_marks_snapshot;
            }
        }

        $percentage = $totalMaxMarks > 0 ? round(($totalObtained / $totalMaxMarks) * 100, 2) : 0;

        // Calculate grade
        $gradeSystem = GradeSystem::getActiveGradeSystem();
        $gradeItem = null;

        if ($gradeSystem) {
            foreach ($gradeSystem->gradeSystemItems as $item) {
                if ($percentage >= $item->min_percentage) {
                    $gradeItem = $item;
                    if ($item->max_percentage !== null && $percentage <= $item->max_percentage) {
                        break;
                    }
                }
            }
        }

        $header->update([
            'total_obtained_cache' => $totalObtained,
            'overall_percentage_cache' => $percentage,
            'overall_grade_item_id_cache' => $gradeItem?->id,
        ]);
    }

    /**
     * Submit result for verification (API).
     */
    public function submitForVerification($resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $header->update(['status' => 'submitted']);
        return response()->json(['message' => 'Submitted for verification successfully', 'data' => $header]);
    }

    /**
     * Reopen result for editing (API).
     */
    public function reopen($resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $header->update(['status' => 'draft']);
        return response()->json(['message' => 'Result reopened successfully', 'data' => $header]);
    }

    /**
     * Lock student result (API).
     */
    public function lockStudentResult($resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $header->update(['is_locked' => true]);
        return response()->json(['message' => 'Student result locked successfully', 'data' => $header]);
    }

    /**
     * Search students for marking grid (API).
     */
    public function searchStudents(Request $request)
    {
        $query = $request->query('q', '');
        $examId = $request->query('exam_id');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');

        if (empty($query) || !$examId || !$classId) {
            return response()->json([]);
        }

        $exam = Exam::findOrFail($examId);

        $studentsQuery = Student::whereHas('enrollmentRecords', function ($q) use ($exam, $classId, $sectionId) {
            $q->where('session_id', $exam->session_id)
                ->whereNull('leave_date')
                ->where('class_id', $classId);

            if ($sectionId) {
                $q->where('section_id', $sectionId);
            }
        })->whereHas('user', function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%");
        })->with('user');

        $students = $studentsQuery->limit(20)->get();

        $results = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->user?->name ?? 'Unknown',
            ];
        });

        return response()->json($results);
    }
}
