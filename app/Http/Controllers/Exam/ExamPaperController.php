<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkCreateExamPaperRequest;
use App\Http\Requests\Exam\StoreExamPaperRequest;
use App\Http\Requests\Exam\UpdateExamPaperRequest;
use App\Models\Campus;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Services\Exam\ExamPaperService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamPaperController extends Controller
{
    protected $paperService;

    public function __construct(ExamPaperService $paperService)
    {
        $this->paperService = $paperService;
    }

    /**
     * Display a listing of exam papers (Page).
     */
    public function indexPage(Request $request): Response
    {
        $examId = $request->get('exam_id');
        $classId = $request->get('class_id');

        $papers = ExamPaper::with(['exam', 'subject', 'class', 'section', 'campus'])
            ->when($examId, fn ($q) => $q->where('exam_id', $examId))
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->orderBy('paper_date')
            ->get();

        return Inertia::render('Exam/Papers/Index', [
            'papers' => $papers,
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
            'subjects' => Subject::all(),
            'filters' => [
                'exam_id' => $examId,
                'class_id' => $classId,
            ],
        ]);
    }

    /**
     * Show the form for creating papers (Page).
     */
    public function createPage(Request $request): Response
    {
        return Inertia::render('Exam/Papers/Create', [
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
            'sections' => Section::all(),
            'subjects' => Subject::all(),
            'preSelectedExamId' => $request->get('exam_id'),
        ]);
    }

    /**
     * Show the form for editing the specified paper (Page).
     */
    public function editPage($id): Response
    {
        $paper = ExamPaper::with(['exam', 'subject', 'class', 'section', 'campus'])->findOrFail($id);

        return Inertia::render('Exam/Papers/Edit', [
            'paper' => $paper,
            'exams' => Exam::all(),
            'campuses' => Campus::all(),
            'classes' => SchoolClass::all(),
            'sections' => Section::all(),
            'subjects' => Subject::all(),
        ]);
    }

    /**
     * Display a listing of exam papers (API).
     */
    public function index(Request $request)
    {
        $examId = $request->get('exam_id');

        $papers = ExamPaper::with(['exam', 'subject', 'class', 'section', 'campus'])
            ->when($examId, fn ($q) => $q->where('exam_id', $examId))
            ->get();

        return response()->json(['data' => $papers]);
    }

    /**
     * Store a newly created paper (API).
     */
    public function store(StoreExamPaperRequest $request)
    {
        $validated = $request->validated();

        // Scope validation
        $scopeType = $validated['scope_type'];

        if ($scopeType === 'SCHOOL') {
            $validated['campus_id'] = null;
            $validated['class_id'] = null;
            $validated['section_id'] = null;
        } elseif ($scopeType === 'CLASS') {
            $validated['section_id'] = null;
        }

        $paper = ExamPaper::create($validated);

        return response()->json(['message' => 'Exam paper created successfully', 'data' => $paper], 201);
    }

    /**
     * Update the specified paper (API).
     */
    public function update(UpdateExamPaperRequest $request, $id)
    {
        $paper = ExamPaper::findOrFail($id);
        $validated = $request->validated();

        // Scope validation
        $scopeType = $validated['scope_type'] ?? $paper->scope_type;

        if ($scopeType === 'SCHOOL') {
            $validated['campus_id'] = null;
            $validated['class_id'] = null;
            $validated['section_id'] = null;
        } elseif ($scopeType === 'CLASS') {
            $validated['section_id'] = null;
        }

        $paper->update($validated);

        return response()->json(['message' => 'Exam paper updated successfully', 'data' => $paper]);
    }

    /**
     * Bulk create papers (API).
     */
    public function bulkCreate(BulkCreateExamPaperRequest $request)
    {
        $validated = $request->validated();
        $examId = $validated['exam_id'];
        $scopeType = $validated['scope_type'];

        $papers = [];
        $now = now();

        foreach ($validated['papers'] as $paper) {
            $paperData = [
                'exam_id' => $examId,
                'subject_id' => $paper['subject_id'],
                'scope_type' => $scopeType,
                'paper_date' => $paper['paper_date'],
                'start_time' => $paper['start_time'],
                'end_time' => $paper['end_time'],
                'total_marks' => $paper['total_marks'],
                'passing_marks' => $paper['passing_marks'],
                'status' => 'scheduled',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Add scope-specific fields
            if ($scopeType === 'SCHOOL') {
                $paperData['campus_id'] = null;
                $paperData['class_id'] = null;
                $paperData['section_id'] = null;
            } elseif ($scopeType === 'CLASS') {
                $paperData['class_id'] = $validated['class_id'] ?? null;
                $paperData['section_id'] = null;
                $paperData['campus_id'] = $validated['campus_id'] ?? null;
            } elseif ($scopeType === 'SECTION') {
                $paperData['class_id'] = $validated['class_id'] ?? null;
                $paperData['section_id'] = $validated['section_id'] ?? null;
                $paperData['campus_id'] = $validated['campus_id'] ?? null;
            }

            $papers[] = $paperData;
        }

        ExamPaper::insert($papers);

        return response()->json(['message' => 'Exam papers created successfully', 'count' => count($papers)], 201);
    }

    /**
     * Check for schedule clashes (API).
     */
    public function clashCheck(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'scope_type' => 'required|in:SCHOOL,CLASS,SECTION',
            'campus_id' => 'nullable|exists:campuses,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'paper_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ], [
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'The selected exam is invalid.',
            'scope_type.required' => 'Please select an exam scope.',
            'scope_type.in' => 'The exam scope must be either Section Level, Class Level, or School Level.',
            'paper_date.required' => 'Please select a paper date.',
            'paper_date.date' => 'Please enter a valid date.',
            'start_time.required' => 'Please select a start time.',
            'end_time.required' => 'Please select an end time.',
            'end_time.after' => 'End time must be after start time.',
        ]);

        $hasClash = $this->paperService->checkClash(
            $validated['exam_id'],
            $validated['scope_type'],
            $validated['paper_date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['class_id'] ?? null,
            $validated['section_id'] ?? null,
            $validated['campus_id'] ?? null
        );

        return response()->json(['has_clash' => $hasClash]);
    }

    /**
     * Cancel the specified paper (API).
     */
    public function cancel($id)
    {
        $paper = ExamPaper::findOrFail($id);
        $paper->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Exam paper cancelled successfully', 'data' => $paper]);
    }

    /**
     * Remove the specified paper (API).
     */
    public function destroy($id)
    {
        $paper = ExamPaper::findOrFail($id);
        $paper->delete();

        return response()->json(['message' => 'Exam paper deleted successfully']);
    }

    /**
     * Get sections by class (API).
     */
    public function getSectionsByClass(Request $request)
    {
        $classId = $request->get('class_id');

        if (! $classId) {
            return response()->json(['sections' => []]);
        }

        $sections = Section::where('class_id', $classId)->orderBy('name')->get();

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get existing papers or subjects by exam, campus, class, and section (API).
     * This is the main endpoint for the Create.vue page.
     * When user selects Exam, Campus, Class, and Section:
     * - If papers exist for that combination, return them
     * - If no papers exist, return subjects for that Campus, Class, Section
     */
    public function getPapersOrSubjects(Request $request)
    {
        $examId = $request->get('exam_id');
        $campusId = $request->get('campus_id');
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $excludeExisting = $request->get('exclude_existing', false);

        // Validate required parameters
        if (! $classId) {
            return response()->json([
                'type' => 'error',
                'message' => 'Class is required to load subjects or papers.',
                'data' => [],
            ], 422);
        }

        // If exam is selected, check for existing papers (unless exclude_existing is true)
        if ($examId && ! $excludeExisting) {
            $papersQuery = ExamPaper::with(['exam', 'subject', 'class', 'section', 'campus'])
                ->where('exam_id', $examId)
                ->where('class_id', $classId);

            // Filter by campus if provided
            if ($campusId) {
                $papersQuery->where('campus_id', $campusId);
            } else {
                $papersQuery->whereNull('campus_id');
            }

            // If a specific section is selected, also include CLASS-level papers for that class
            // This helps users see both scope levels when managing papers
            if ($sectionId) {
                $papersQuery->where(function ($q) use ($sectionId) {
                    $q->where('section_id', $sectionId)
                        ->orWhere(function ($subQ) {
                            $subQ->whereNull('section_id')
                                ->where('scope_type', 'CLASS');
                        });
                });
            } else {
                // No specific section - show CLASS level papers only
                $papersQuery->whereNull('section_id');
            }

            $papers = $papersQuery->orderBy('paper_date')->get();

            if ($papers->isNotEmpty()) {
                return response()->json([
                    'type' => 'papers',
                    'data' => $papers,
                    'message' => 'Existing papers found for this combination.',
                ]);
            }
        }

        // No existing papers (or exclude_existing=true), return subjects for the campus/class/section
        // Get subjects assigned to this class, optionally filtered by section
        $subjectQuery = Subject::whereHas('schoolClasses', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        });

        // If exclude_existing is true, exclude subjects that already have papers
        if ($examId && $excludeExisting) {
            $existingSubjectIds = ExamPaper::where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where(function ($q) use ($campusId) {
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    } else {
                        $q->whereNull('campus_id');
                    }
                })
                ->pluck('subject_id')
                ->toArray();

            $subjectQuery->whereNotIn('id', $existingSubjectIds);
        }

        // Get subjects for the class, ordered by name
        $subjects = $subjectQuery
            ->orderBy('name', 'asc')
            ->get();

        // Mark each subject as not existing (for UI purposes)
        $subjectsData = $subjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'short_name' => $subject->short_name,
                'exists' => false,
                'saved' => false,
            ];
        });

        return response()->json([
            'type' => 'subjects',
            'data' => $subjectsData,
            'message' => 'No existing papers found. Showing subjects for the selected class and section.',
        ]);
    }

    /**
     * Get subjects by class and section (API). [Deprecated - use getPapersOrSubjects]
     */
    public function getSubjectsByClassAndSection(Request $request)
    {
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $campusId = $request->get('campus_id');

        if (! $classId) {
            return response()->json(['subjects' => []]);
        }

        // Get subjects assigned to this class
        $subjectQuery = Subject::whereHas('schoolClasses', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        });

        // If section is specified, we can filter by section-specific subjects
        // For now, we get all subjects for the class
        $subjects = $subjectQuery->orderBy('name')->get();

        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Get exam date range for frontend validation (API).
     */
    public function getExamDateRange(Request $request)
    {
        $examId = $request->get('exam_id');

        if (! $examId) {
            return response()->json(['message' => 'Exam ID is required.'], 422);
        }

        $dateRange = $this->paperService->getExamDateRange($examId);

        if (! $dateRange) {
            return response()->json(['message' => 'Exam not found.'], 404);
        }

        return response()->json($dateRange);
    }

    /**
     * Validate paper date against exam date range (API).
     */
    public function validatePaperDate(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'paper_date' => 'required|date',
        ]);

        $result = $this->paperService->validatePaperDate(
            $validated['exam_id'],
            $validated['paper_date']
        );

        return response()->json($result);
    }

    /**
     * Check for date overlaps (API).
     */
    public function checkDateOverlap(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'paper_date' => 'required|date',
            'scope_type' => 'required|in:SCHOOL,CLASS,SECTION',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        $result = $this->paperService->checkDateOverlap(
            $validated['exam_id'],
            $validated['paper_date'],
            $validated['scope_type'],
            $validated['class_id'] ?? null,
            $validated['section_id'] ?? null,
            $validated['campus_id'] ?? null
        );

        return response()->json($result);
    }

    /**
     * Store a single paper with subject details (API).
     * Includes validation, duplicate check, schedule clash detection, and date range validation.
     */
    public function storeSinglePaper(Request $request)
    {
        // Enhanced validation with clear error messages
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'scope_type' => 'required|in:SCHOOL,CLASS,SECTION',
            'paper_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'total_marks' => 'required|numeric|min:0',
            'passing_marks' => 'required|numeric|min:0',
        ], [
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'The selected exam is invalid.',
            'subject_id.required' => 'Please select a subject.',
            'subject_id.exists' => 'The selected subject is invalid.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'The selected class is invalid.',
            'section_id.exists' => 'The selected section is invalid.',
            'scope_type.required' => 'Please select an exam scope.',
            'scope_type.in' => 'The exam scope must be either Section Level, Class Level, or School Level.',
            'paper_date.required' => 'Please select a paper date.',
            'paper_date.date' => 'Please enter a valid date.',
            'start_time.required' => 'Please select a start time.',
            'end_time.required' => 'Please select an end time.',
            'end_time.after' => 'End time must be after start time.',
            'total_marks.required' => 'Please enter total marks.',
            'total_marks.numeric' => 'Total marks must be a number.',
            'total_marks.min' => 'Total marks cannot be negative.',
            'passing_marks.required' => 'Please enter passing marks.',
            'passing_marks.numeric' => 'Passing marks must be a number.',
            'passing_marks.min' => 'Passing marks cannot be negative.',
        ]);

        // Validate paper date is within exam date range
        $dateValidation = $this->paperService->validatePaperDate($validated['exam_id'], $validated['paper_date']);
        if (! $dateValidation['valid']) {
            return response()->json([
                'message' => $dateValidation['message'],
                'error' => 'invalid_date',
            ], 422);
        }

        // Check for date overlap
        $overlapResult = $this->paperService->checkDateOverlap(
            $validated['exam_id'],
            $validated['paper_date'],
            $validated['scope_type'],
            $validated['class_id'],
            $validated['section_id'] ?? null,
            $validated['campus_id'] ?? null
        );

        if ($overlapResult['has_overlap']) {
            return response()->json([
                'message' => $overlapResult['message'],
                'error' => 'date_overlap',
                'overlapping_papers' => $overlapResult['overlapping_papers'],
            ], 422);
        }

        // Scope validation
        if ($validated['scope_type'] === 'SCHOOL') {
            $validated['campus_id'] = null;
            $validated['class_id'] = null;
            $validated['section_id'] = null;
        } elseif ($validated['scope_type'] === 'CLASS') {
            $validated['section_id'] = null;
        }

        // Check for hierarchical conflicts: SECTION scope vs CLASS scope
        // If trying to create a SECTION paper, check if CLASS paper already exists
        if ($validated['scope_type'] === 'SECTION') {
            $classLevelPaper = ExamPaper::where('exam_id', $validated['exam_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('class_id', $validated['class_id'])
                ->where('scope_type', 'CLASS')
                ->where(function ($q) use ($validated) {
                    $q->whereNull('campus_id')
                        ->orWhere('campus_id', $validated['campus_id']);
                })
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($classLevelPaper) {
                return response()->json([
                    'message' => 'A paper for this subject already exists at CLASS level (All Sections). Please edit or remove the class-level paper first, or select a different subject.',
                    'error' => 'hierarchy_conflict',
                    'existing_paper' => $classLevelPaper,
                    'conflict_type' => 'class_level_exists',
                ], 422);
            }
        }

        // If trying to create a CLASS paper, check if any SECTION papers already exist
        if ($validated['scope_type'] === 'CLASS') {
            $sectionPapers = ExamPaper::where('exam_id', $validated['exam_id'])
                ->where('subject_id', $validated['subject_id'])
                ->where('class_id', $validated['class_id'])
                ->where('scope_type', 'SECTION')
                ->where(function ($q) use ($validated) {
                    $q->whereNull('campus_id')
                        ->orWhere('campus_id', $validated['campus_id']);
                })
                ->where('status', '!=', 'cancelled')
                ->get();

            if ($sectionPapers->isNotEmpty()) {
                return response()->json([
                    'message' => 'Papers for this subject already exist at SECTION level. Please remove them before creating a class-level paper, or select specific sections instead.',
                    'error' => 'hierarchy_conflict',
                    'existing_papers' => $sectionPapers,
                    'conflict_type' => 'section_level_exists',
                ], 422);
            }
        }

        // Check if paper already exists for this exact combination (Duplicate check)
        $existingPaper = ExamPaper::where('exam_id', $validated['exam_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->where('campus_id', $validated['campus_id'])
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingPaper) {
            return response()->json([
                'message' => 'A paper for this subject already exists in the selected exam, campus, class, and section combination.',
                'error' => 'duplicate',
                'exists' => true,
                'paper' => $existingPaper,
            ], 422);
        }

        // Check for schedule clash (time-based overlap)
        $hasClash = $this->paperService->checkClash(
            $validated['exam_id'],
            $validated['scope_type'],
            $validated['paper_date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['class_id'],
            $validated['section_id'],
            $validated['campus_id']
        );

        if ($hasClash) {
            return response()->json([
                'message' => 'Schedule conflict: There is already an exam scheduled at this time for the same class/section.',
                'error' => 'clash',
                'has_clash' => true,
            ], 422);
        }

        $paper = ExamPaper::create($validated);

        return response()->json([
            'message' => 'Exam paper created successfully',
            'data' => $paper,
        ], 201);
    }

    /**
     * Store multiple papers in bulk (API).
     * Includes validation, duplicate check, schedule clash detection, and date range validation.
     */
    public function storeBulkPapers(Request $request)
    {
        // Enhanced validation with clear error messages
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'scope_type' => 'required|in:SCHOOL,CLASS,SECTION',
            'papers' => 'required|array|min:1',
            'papers.*.subject_id' => 'required|exists:subjects,id',
            'papers.*.paper_date' => 'required|date',
            'papers.*.start_time' => 'required',
            'papers.*.end_time' => 'required',
            'papers.*.total_marks' => 'required|numeric|min:0',
            'papers.*.passing_marks' => 'required|numeric|min:0',
        ], [
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'The selected exam is invalid.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'The selected class is invalid.',
            'papers.required' => 'Please add at least one paper to save.',
            'papers.array' => 'Invalid paper data format.',
            'papers.min' => 'Please add at least one paper to save.',
            'papers.*.subject_id.required' => 'Each paper must have a subject.',
            'papers.*.subject_id.exists' => 'One or more selected subjects are invalid.',
            'papers.*.paper_date.required' => 'Each paper must have a date.',
            'papers.*.paper_date.date' => 'Please enter valid dates for all papers.',
            'papers.*.start_time.required' => 'Each paper must have a start time.',
            'papers.*.end_time.required' => 'Each paper must have an end time.',
            'papers.*.total_marks.required' => 'Each paper must have total marks.',
            'papers.*.total_marks.numeric' => 'Total marks must be a number.',
            'papers.*.total_marks.min' => 'Total marks cannot be negative.',
            'papers.*.passing_marks.required' => 'Each paper must have passing marks.',
            'papers.*.passing_marks.numeric' => 'Passing marks must be a number.',
            'papers.*.passing_marks.min' => 'Passing marks cannot be negative.',
        ]);

        $examId = $validated['exam_id'];
        $scopeType = $validated['scope_type'];
        $classId = $validated['class_id'];
        $sectionId = $validated['section_id'] ?? null;
        $campusId = $validated['campus_id'] ?? null;
        $papers = $validated['papers'];

        // Check for hierarchical conflicts before processing bulk papers
        // If trying to create CLASS papers, check if SECTION papers already exist
        if ($scopeType === 'CLASS') {
            $existingSectionPapers = ExamPaper::where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('scope_type', 'SECTION')
                ->where(function ($q) use ($campusId) {
                    $q->whereNull('campus_id')
                        ->orWhere('campus_id', $campusId);
                })
                ->where('status', '!=', 'cancelled')
                ->pluck('subject_id')
                ->toArray();

            if (! empty($existingSectionPapers)) {
                return response()->json([
                    'message' => 'Cannot create class-level papers: Section-level papers already exist for some subjects. Please remove them first.',
                    'error' => 'hierarchy_conflict',
                    'conflict_type' => 'section_level_exists',
                    'conflicting_subject_ids' => $existingSectionPapers,
                ], 422);
            }
        }

        // If trying to create SECTION papers, check if CLASS papers exist
        if ($scopeType === 'SECTION') {
            $existingClassPapers = ExamPaper::where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('scope_type', 'CLASS')
                ->where(function ($q) use ($campusId) {
                    $q->whereNull('campus_id')
                        ->orWhere('campus_id', $campusId);
                })
                ->where('status', '!=', 'cancelled')
                ->pluck('subject_id')
                ->toArray();

            if (! empty($existingClassPapers)) {
                return response()->json([
                    'message' => 'Cannot create section-level papers: Class-level papers already exist for some subjects. Please remove them first.',
                    'error' => 'hierarchy_conflict',
                    'conflict_type' => 'class_level_exists',
                    'conflicting_subject_ids' => $existingClassPapers,
                ], 422);
            }
        }

        // Get exam date range for validation
        $exam = Exam::find($examId);
        $examStartDate = $exam->start_date;
        $examEndDate = $exam->end_date;

        $createdPapers = [];
        $skippedPapers = [];
        $clashedPapers = [];
        $invalidDatePapers = [];
        $dateOverlapPapers = [];
        $now = now();

        foreach ($papers as $paper) {
            // Validate paper date is within exam date range
            $paperDateObj = Carbon::parse($paper['paper_date']);

            if ($paperDateObj->lt($examStartDate) || $paperDateObj->gt($examEndDate)) {
                $invalidDatePapers[] = [
                    'subject_id' => $paper['subject_id'],
                    'reason' => "Paper date must be between {$examStartDate->format('Y-m-d')} and {$examEndDate->format('Y-m-d')}.",
                    'error' => 'invalid_date',
                    'paper_date' => $paper['paper_date'],
                ];

                continue;
            }

            // Check for date overlap
            $overlapResult = $this->paperService->checkDateOverlap(
                $examId,
                $paper['paper_date'],
                $scopeType,
                $classId,
                $sectionId,
                $campusId
            );

            if ($overlapResult['has_overlap']) {
                $dateOverlapPapers[] = [
                    'subject_id' => $paper['subject_id'],
                    'reason' => $overlapResult['message'],
                    'error' => 'date_overlap',
                    'paper_date' => $paper['paper_date'],
                ];

                continue;
            }

            // Check if paper already exists (Duplicate check)
            $existingPaper = ExamPaper::where('exam_id', $examId)
                ->where('subject_id', $paper['subject_id'])
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('campus_id', $campusId)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existingPaper) {
                $skippedPapers[] = [
                    'subject_id' => $paper['subject_id'],
                    'reason' => 'A paper for this subject already exists in the selected exam, campus, class, and section combination.',
                    'error' => 'duplicate',
                    'existing_paper' => $existingPaper,
                ];

                continue;
            }

            // Check for schedule clash (time-based overlap)
            $hasClash = $this->paperService->checkClash(
                $examId,
                $scopeType,
                $paper['paper_date'],
                $paper['start_time'],
                $paper['end_time'],
                $classId,
                $sectionId,
                $campusId
            );

            if ($hasClash) {
                $clashedPapers[] = [
                    'subject_id' => $paper['subject_id'],
                    'reason' => 'Schedule conflict: There is already an exam scheduled at this time.',
                    'error' => 'clash',
                    'paper_date' => $paper['paper_date'],
                    'start_time' => $paper['start_time'],
                    'end_time' => $paper['end_time'],
                ];

                continue;
            }

            $paperData = [
                'exam_id' => $examId,
                'subject_id' => $paper['subject_id'],
                'scope_type' => $scopeType,
                'paper_date' => $paper['paper_date'],
                'start_time' => $paper['start_time'],
                'end_time' => $paper['end_time'],
                'total_marks' => $paper['total_marks'],
                'passing_marks' => $paper['passing_marks'],
                'status' => 'scheduled',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Add scope-specific fields
            if ($scopeType === 'SCHOOL') {
                $paperData['campus_id'] = null;
                $paperData['class_id'] = null;
                $paperData['section_id'] = null;
            } elseif ($scopeType === 'CLASS') {
                $paperData['class_id'] = $classId;
                $paperData['section_id'] = null;
                $paperData['campus_id'] = $campusId;
            } elseif ($scopeType === 'SECTION') {
                $paperData['class_id'] = $classId;
                $paperData['section_id'] = $sectionId;
                $paperData['campus_id'] = $campusId;
            }

            $createdPapers[] = $paperData;
        }

        // Insert all papers at once
        if (! empty($createdPapers)) {
            ExamPaper::insert($createdPapers);
        }

        return response()->json([
            'message' => 'Exam papers processed successfully',
            'created_count' => count($createdPapers),
            'skipped_count' => count($skippedPapers),
            'clashed_count' => count($clashedPapers),
            'invalid_date_count' => count($invalidDatePapers),
            'date_overlap_count' => count($dateOverlapPapers),
            'created' => $createdPapers,
            'skipped' => $skippedPapers,
            'clashed' => $clashedPapers,
            'invalid_dates' => $invalidDatePapers,
            'date_overlaps' => $dateOverlapPapers,
        ], 201);
    }
}
