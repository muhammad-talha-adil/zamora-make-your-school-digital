<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\StaffProfile;
use App\Models\Subject;
use App\Models\TeacherClassAssignment;
use App\Services\Staff\TeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Which teacher has which class, and who may teach what.
 *
 * **This is the screen three finished modules have been waiting for.**
 *
 * `teacher_class_assignments` was built to close attendance A13, and the class
 * width in Attendance, Exam and Student all read it. Nothing has ever written
 * to it, so the live table holds **zero rows** — which is why every teacher in
 * the school currently sees nothing at all. The rule was right and had no data.
 */
class TeacherAssignmentController extends Controller
{
    public function __construct(private TeacherAssignmentService $teaching) {}

    /**
     * The assignment screen: a session, a class, and who takes it.
     */
    public function page(Request $request)
    {
        Gate::authorize('viewAny', StaffProfile::class);

        return Inertia::render('Staff/Teaching/Index', [
            'sessions' => Session::where('is_active', true)->get(['id', 'name']),
            'classes' => SchoolClass::where('is_active', true)
                ->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['session_id', 'class_id', 'section_id']),
        ]);
    }

    /**
     * Who takes what, for a class.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StaffProfile::class);

        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        $assignments = TeacherClassAssignment::query()
            ->with(['staffProfile.user:id,name', 'schoolClass:id,name', 'section:id,name', 'subject:id,name'])
            ->where('teacher_class_assignments.session_id', $validated['session_id'])
            ->when($validated['class_id'] ?? null, fn ($q, $id) => $q->where('class_id', $id))
            ->when($validated['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))
            // Only teachers this person may see.
            ->whereHas('staffProfile', fn ($q) => $q->visibleTo($request->user()))
            ->active()
            ->orderBy('class_id')
            ->orderByDesc('is_class_teacher')
            ->get();

        return response()->json(['data' => $assignments]);
    }

    /**
     * Gives a teacher a class.
     */
    public function store(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'is_class_teacher' => ['nullable', 'boolean'],
            'periods_per_week' => ['nullable', 'integer', 'min:1', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $assignment = $this->teaching->assignClass($staffProfile, $validated);

        return response()->json(['message' => 'Class assigned', 'data' => $assignment], 201);
    }

    /**
     * Takes a class away.
     */
    public function destroy(TeacherClassAssignment $assignment)
    {
        Gate::authorize('update', $assignment->staffProfile);

        // Marked inactive, not deleted: last month's register was taken by this
        // teacher and a report should still be able to say so.
        $this->teaching->unassignClass($assignment);

        return response()->json(['message' => 'Class taken away']);
    }

    /**
     * Says this person may be given a subject.
     */
    public function allowSubject(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'message' => 'Subject added',
            'data' => $this->teaching->allowSubject(
                $staffProfile,
                (int) $validated['subject_id'],
                (bool) ($validated['is_primary'] ?? false)
            ),
        ], 201);
    }

    public function disallowSubject(Request $request, StaffProfile $staffProfile, int|string $subjectId)
    {
        Gate::authorize('update', $staffProfile);

        $this->teaching->disallowSubject($staffProfile, (int) $subjectId);

        return response()->json(['message' => 'Subject removed']);
    }

    /**
     * Who could cover this subject.
     *
     * The question a school asks at eight in the morning when somebody rings in
     * sick, and has never been able to ask this system.
     */
    public function whoCanTeach(Request $request)
    {
        Gate::authorize('viewAny', StaffProfile::class);

        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ]);

        return response()->json([
            'data' => $this->teaching->whoCanTeach(
                (int) $validated['subject_id'],
                $request->user()?->campusId()
            ),
        ]);
    }

    /**
     * The sections of a class, for the assignment form.
     */
    public function sections(Request $request)
    {
        Gate::authorize('viewAny', StaffProfile::class);

        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
        ]);

        return response()->json([
            'data' => Section::where('class_id', $validated['class_id'])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
