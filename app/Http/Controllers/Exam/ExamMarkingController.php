<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\SaveMarksRequest;
use App\Models\Campus;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\Exam\GradeSystem;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Services\Exam\ExamGraceService;
use App\Services\Exam\ExamMarkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $this->authorize('viewAny', ExamResultHeader::class);

        // Check if exam is locked
        if ($exam->is_locked) {
            return response()->json(['error' => 'This exam is locked. Cannot enter marks.'], 403);
        }

        // Get papers filtered by class and section
        $papersQuery = ExamPaper::with(['subject', 'class', 'section', 'campus'])
            ->where('exam_id', $examId)
            // The grid shows the sections this user is responsible for, and no
            // others. The policy guards one record; this filters the list.
            ->visibleTo($request->user())
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
                if (! empty($paperSectionIds)) {
                    $q->whereIn('section_id', $paperSectionIds);
                }
                // Also filter by class
                $q->where('class_id', $classId);
            } else {
                // No class specified - filter by classes that have papers
                if (! empty($paperClassIds)) {
                    $q->whereIn('class_id', $paperClassIds);
                }
            }
        })->with([
            'user',
            'enrollmentRecords.campus',
            'enrollmentRecords.class',
            'enrollmentRecords.section',
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
        if (! $gradeSystem) {
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

                if ($obtained !== null && ! ($studentMarks?->is_absent ?? false)) {
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
            'papers' => $papers->map(fn ($p) => [
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
     *
     * The work is the service's. This used to carry its own copy of it, which
     * disagreed with the service in what it wrote and with the bulk path in how
     * it added things up.
     */
    public function saveRow(SaveMarksRequest $request)
    {
        $exam = Exam::findOrFail($request->validated('exam_id'));

        if ($exam->is_locked) {
            return response()->json(['error' => 'This exam is locked'], 403);
        }

        $this->authorizeMarking($exam, [
            [
                'student_id' => $request->validated('student_id'),
                'marks' => $request->validated('marks'),
            ],
        ]);

        $this->markingService->saveStudentMarks(
            $exam,
            (int) $request->validated('student_id'),
            (array) $request->validated('marks'),
            StudentEnrollmentRecord::find($request->validated('enrollment_id'))
        );

        return response()->json(['message' => 'Marks saved successfully']);
    }

    /**
     * Save bulk marks (API).
     *
     * It used to `merge()` each student into the request and call `saveRow()`
     * again, so one bad row aborted the rest half-written. Each student is now
     * validated on the way in and saved in one transaction.
     */
    public function saveBulk(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'students.*.enrollment_id' => ['nullable', 'integer', 'exists:student_enrollment_records,id'],
            'students.*.marks' => ['required', 'array'],
            'students.*.marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
            'students.*.marks.*.obtained_marks' => ['nullable', 'numeric', 'min:0'],
            'students.*.marks.*.is_absent' => ['nullable', 'boolean'],
            'students.*.marks.*.is_exempt' => ['nullable', 'boolean'],
            'students.*.marks.*.subject_role' => ['nullable', 'string', 'in:core,elective,additional'],
            'students.*.marks.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $exam = Exam::findOrFail($validated['exam_id']);

        if ($exam->is_locked) {
            return response()->json(['error' => 'This exam is locked'], 403);
        }

        $papers = $this->markingService->papersOf($exam);

        // Bounded by the paper, and the paper has to be this exam's — checked
        // for the whole batch before any of it is written.
        foreach ($validated['students'] as $index => $studentData) {
            foreach ($studentData['marks'] as $paperId => $mark) {
                if (! $papers->has((int) $paperId)) {
                    return response()->json([
                        'error' => "Paper {$paperId} does not belong to this exam.",
                    ], 422);
                }

                $obtained = $mark['obtained_marks'] ?? $mark['obtained'] ?? null;

                if ($obtained !== null && $obtained !== '' &&
                    (float) $obtained > (float) $papers->get((int) $paperId)->total_marks) {
                    return response()->json([
                        'error' => "Marks for paper {$paperId} are more than the paper total.",
                    ], 422);
                }
            }
        }

        // The same read of the papers serves the bounds check, the
        // authorisation and the save.
        $this->authorizeMarking($exam, $validated['students'], $papers);

        $this->markingService->saveBatch($exam, $validated['students'], $papers);

        return response()->json(['message' => 'Bulk save completed successfully']);
    }

    /**
     * Give (or take away) grace marks on one paper.
     *
     * Grace is deliberately not part of a mark entry. It is a decision somebody
     * takes about a child who missed the pass mark by two, and it is recorded
     * as grace with a reason and the person who gave it — never merged into the
     * obtained marks, or the school cannot answer the parent holding the answer
     * sheet.
     *
     * It sits behind `exam.marks.verify` rather than `exam.marks.enter`: the
     * teacher who marked the paper is not the person who decides to lift it.
     */
    public function giveGrace(Request $request, int|string $lineId, ExamGraceService $grace)
    {
        $validated = $request->validate([
            'grace_marks' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $line = ExamResultLine::findOrFail($lineId);
        $this->authorize('verify', $line->resultHeader);

        $line = $grace->give(
            $line,
            $validated['grace_marks'] === null ? null : (float) $validated['grace_marks'],
            $validated['reason'] ?? null,
            $request->user()
        );

        return response()->json([
            'message' => 'Grace marks saved',
            'data' => [
                'line' => $line,
                'headroom' => $grace->headroomOn($line),
                'shortfall' => $grace->shortfallOn($line),
            ],
        ]);
    }

    /**
     * Which children in a class are a mark or two short of a pass.
     *
     * The list a school actually works from when it sits down to give grace:
     * "these six are within three marks".
     */
    public function graceCandidates(Request $request, ExamGraceService $grace)
    {
        $validated = $request->validate([
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'within' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ]);

        $this->authorize('viewAny', ExamResultHeader::class);

        $within = (float) ($validated['within'] ?? 3);

        $headers = ExamResultHeader::where('exam_id', $validated['exam_id'])
            ->when($validated['class_id'] ?? null, fn ($q, $id) => $q->where('class_id', $id))
            ->when($validated['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))
            ->visibleTo($request->user())
            ->with(['student.user', 'examResultLines.examPaper.subject'])
            ->get();

        $candidates = [];

        foreach ($headers as $header) {
            foreach ($header->examResultLines as $line) {
                $short = $grace->shortfallOn($line);

                if ($short === null || $short <= 0 || $short > $within) {
                    continue;
                }

                $candidates[] = [
                    'result_line_id' => $line->id,
                    'student_id' => $header->student_id,
                    'student_name' => $header->student?->user?->name,
                    'subject' => $line->examPaper?->subject?->name,
                    'obtained' => (float) $line->obtained_marks,
                    'passing' => (float) $line->passing_marks_snapshot,
                    'short_by' => $short,
                    'headroom' => $grace->headroomOn($line),
                ];
            }
        }

        usort($candidates, fn ($a, $b) => $a['short_by'] <=> $b['short_by']);

        return response()->json(['data' => $candidates]);
    }

    /**
     * Whether this user may mark these children, on these papers.
     *
     * There was no authorisation anywhere in this module. Any signed-in user —
     * a student, a guardian, a driver — could enter and change marks for every
     * child in every class of every campus.
     *
     * Two things are checked, because they are two different questions. The
     * **paper** says which campus, class and section the marks belong to, and a
     * teacher may only mark the sections they have been given. The **result**,
     * where one already exists, says how far along the workflow it is: a
     * verified or published result is not edited in place, it is reopened
     * first, which is somebody else's decision.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  Collection<int, ExamPaper>|null  $papers
     */
    private function authorizeMarking(Exam $exam, array $rows, $papers = null): void
    {
        $paperIds = [];
        $studentIds = [];

        foreach ($rows as $row) {
            $studentIds[] = (int) $row['student_id'];

            foreach (array_keys((array) ($row['marks'] ?? [])) as $paperId) {
                $paperIds[(int) $paperId] = true;
            }
        }

        $marked = $papers
            ? $papers->only(array_keys($paperIds))
            : $this->markingService->papersOf($exam, array_keys($paperIds));

        foreach ($marked as $paper) {
            $this->authorize('create', [ExamResultHeader::class, $paper]);
        }

        $existing = ExamResultHeader::where('exam_id', $exam->id)
            ->whereIn('student_id', array_unique($studentIds))
            ->get();

        foreach ($existing as $header) {
            $this->authorize('enterMarks', $header);
        }
    }

    /**
     * Submit result for verification (API).
     */
    public function submitForVerification($resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $this->authorize('enterMarks', $header);
        $header->update(['status' => 'submitted']);

        return response()->json(['message' => 'Submitted for verification successfully', 'data' => $header]);
    }

    /**
     * Reopen result for editing (API).
     */
    public function reopen($resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $this->authorize('reopen', $header);
        $header->update(['status' => 'draft']);

        return response()->json(['message' => 'Result reopened successfully', 'data' => $header]);
    }

    /**
     * Lock student result (API).
     */
    public function lockStudentResult($resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $this->authorize('verify', $header);
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

        if (empty($query) || ! $examId || ! $classId) {
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
