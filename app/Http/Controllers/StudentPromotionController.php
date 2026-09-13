<?php

namespace App\Http\Controllers;

use App\Models\Exam\ExamResultHeader;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Services\Exam\AnnualResultService;
use App\Services\Student\StudentEnrollmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Moving a class up, and moving children sideways.
 *
 * A school promotes a **section at a time** at the end of the year, not a child
 * at a time — forty names on a screen with a decision beside each one. The
 * `students.promote` ability has been seeded since the beginning and nothing
 * has ever used it.
 *
 * Whether the child passed the year is the exam module's answer
 * (`AnnualResultService`); what this does is record the decision and open next
 * year's enrolment period.
 */
class StudentPromotionController extends Controller
{
    public function __construct(
        private StudentEnrollmentService $enrollments,
        private AnnualResultService $annual
    ) {}

    /**
     * The promotion screen.
     */
    public function page(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        return Inertia::render('Students/Promotion/Index', [
            'sessions' => Session::where('is_active', true)->get(),
            'classes' => SchoolClass::where('is_active', true)->orderBy('level')->orderBy('name')->get(),
            'filters' => $request->only(['session_id', 'class_id', 'section_id']),
        ]);
    }

    /**
     * The section, with each child's year beside their name.
     *
     * This is the sheet the office actually works from: who passed, who did
     * not, and what the system suggests for each.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        $this->authorize('viewAny', Student::class);

        $students = Student::query()
            ->visibleTo($request->user())
            ->with(['user:id,name', 'currentEnrollment'])
            ->whereHas('enrollmentRecords', function ($enrollment) use ($validated) {
                $enrollment->whereNull('leave_date')
                    ->where('session_id', $validated['session_id'])
                    ->where('class_id', $validated['class_id'])
                    ->when($validated['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id));
            })
            ->get();

        $suggestedClass = $this->enrollments->nextClassAfter((int) $validated['class_id']);

        return response()->json([
            'data' => [
                'suggested_class' => $suggestedClass,
                'students' => $students->map(function (Student $student) use ($validated) {
                    $year = $this->annual->forStudent(
                        (int) $student->id,
                        (int) $validated['session_id'],
                        $student->currentEnrollment?->campus_id
                    );

                    return [
                        'student_id' => $student->id,
                        'name' => $student->user?->name,
                        'admission_no' => $student->admission_no,
                        'percentage' => $year['percentage'],
                        'grade' => $year['grade'],
                        'result_status' => $year['result_status'],
                        'year_complete' => $year['is_complete'],
                        // A suggestion, never the decision. A school promotes a
                        // borderline child every year and is entitled to.
                        'suggested_outcome' => $this->suggest($year['result_status']),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Records the decisions and opens next year.
     */
    public function promote(Request $request)
    {
        $validated = $request->validate([
            'to_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'to_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'to_section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'starting_on' => ['nullable', 'date'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'students.*.outcome' => ['required', 'string', 'in:promoted,detained,promoted_on_condition'],
            'students.*.to_class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'students.*.to_section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        $students = Student::whereIn('id', collect($validated['students'])->pluck('student_id'))
            ->get()
            ->keyBy('id');

        // Checked one child at a time: a teacher cannot promote a section that
        // is not theirs by putting its ids in the payload.
        foreach ($students as $student) {
            $this->authorize('promote', $student);
        }

        $promoted = 0;

        foreach ($validated['students'] as $row) {
            $student = $students->get($row['student_id']);

            if (! $student) {
                continue;
            }

            $this->enrollments->promote(
                $student,
                (int) $validated['to_session_id'],
                $row['to_class_id'] ?? $validated['to_class_id'] ?? null,
                $row['to_section_id'] ?? $validated['to_section_id'] ?? null,
                $row['outcome'],
                $validated['starting_on'] ?? null,
                $request->user()
            );

            $promoted++;
        }

        return response()->json([
            'message' => "{$promoted} children moved into the new session",
            'data' => ['promoted' => $promoted],
        ]);
    }

    /**
     * Undoes a promotion run.
     *
     * A section promoted by mistake is otherwise fixed by hand in the database,
     * which is how enrolment histories get destroyed.
     */
    public function revert(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id'],
        ]);

        $students = Student::whereIn('id', $validated['student_ids'])->get();

        foreach ($students as $student) {
            $this->authorize('promote', $student);
        }

        $reverted = 0;

        foreach ($students as $student) {
            $this->enrollments->revertPromotion($student, (int) $validated['session_id']);
            $reverted++;
        }

        return response()->json([
            'message' => "{$reverted} promotions undone",
            'data' => ['reverted' => $reverted],
        ]);
    }

    /**
     * Moves children into another class or section, as of a date.
     *
     * Fifteen children out of 9-A and into 9-B in October. Today that is
     * fifteen individual edits, and each one should be closing a period and
     * opening another.
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'effective_from' => ['nullable', 'date'],
        ]);

        $students = Student::whereIn('id', $validated['student_ids'])->get();

        foreach ($students as $student) {
            $this->authorize('update', $student);
        }

        $moved = $this->enrollments->transfer(
            $students,
            (int) $validated['class_id'],
            $validated['section_id'] ?? null,
            $validated['effective_from'] ?? null,
            $validated['campus_id'] ?? null
        );

        return response()->json([
            'message' => "{$moved} children moved",
            'data' => ['moved' => $moved],
        ]);
    }

    /**
     * What the system would do, given the year's result.
     *
     * A suggestion the office overrules freely — which is why it is returned
     * beside the figures rather than applied.
     */
    private function suggest(?string $resultStatus): string
    {
        return match ($resultStatus) {
            ExamResultHeader::RESULT_FAIL => StudentEnrollmentService::DETAINED,
            // A pass, and also a year nobody finished marking: the office is
            // not told to hold a child back because a paper is unmarked.
            default => StudentEnrollmentService::PROMOTED,
        };
    }
}
