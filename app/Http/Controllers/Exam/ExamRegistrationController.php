<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreRegistrationRequest;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamStudentRegistration;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Services\Exam\ExamRegistrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamRegistrationController extends Controller
{
    protected $registrationService;

    public function __construct(ExamRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Display a listing of exam registrations (Page).
     */
    public function indexPage(Request $request): Response
    {
        $examId = $request->get('exam_id');
        $classId = $request->get('class_id');

        $registrations = ExamStudentRegistration::with(['student.user', 'exam', 'class', 'section'])
            ->when($examId, fn($q) => $q->where('exam_id', $examId))
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->get();

        return Inertia::render('Exam/Registrations/Index', [
            'registrations' => $registrations,
            'exams' => Exam::all(),
            'classes' => SchoolClass::all(),
            'sections' => Section::all(),
            'filters' => [
                'exam_id' => $examId,
                'class_id' => $classId,
            ],
        ]);
    }

    /**
     * Display a listing of registrations (API).
     */
    public function index(Request $request)
    {
        $examId = $request->get('exam_id');
        
        $registrations = ExamStudentRegistration::with(['student.user', 'exam', 'class', 'section'])
            ->when($examId, fn($q) => $q->where('exam_id', $examId))
            ->get();
            
        return response()->json(['data' => $registrations]);
    }

    /**
     * Generate registrations from enrollments (API).
     */
    public function generateFromEnrollments(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $count = $this->registrationService->generateFromEnrollments(
            $validated['exam_id'],
            $validated['class_id'] ?? null,
            $validated['section_id'] ?? null
        );
        
        return response()->json(['message' => "Generated {$count} registrations successfully"]);
    }

    /**
     * Store a newly created registration (API).
     */
    public function store(StoreRegistrationRequest $request)
    {
        $registration = $this->registrationService->registerStudent($request->validated());
        return response()->json(['message' => 'Registration created successfully', 'data' => $registration], 201);
    }

    /**
     * Bulk register students (API).
     */
    public function bulkRegister(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:students,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $examId = $validated['exam_id'];
        $studentIds = $validated['student_ids'];
        $classId = $validated['class_id'] ?? null;
        $sectionId = $validated['section_id'] ?? null;
        $now = now();
        
        $registrations = [];
        foreach ($studentIds as $studentId) {
            $registrations[] = [
                'exam_id' => $examId,
                'student_id' => $studentId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'status' => 'registered',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ExamStudentRegistration::insert($registrations);
        return response()->json(['message' => 'Students registered successfully', 'count' => count($registrations)], 201);
    }

    /**
     * Withdraw a registration (API).
     */
    public function withdraw($id)
    {
        $registration = ExamStudentRegistration::findOrFail($id);
        $registration = $this->registrationService->withdraw($registration);
        return response()->json(['message' => 'Registration withdrawn successfully', 'data' => $registration]);
    }
}
