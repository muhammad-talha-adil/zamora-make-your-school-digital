<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\School;
use App\Models\Student;
use App\Repositories\StudentRepository;
use App\Services\Student\AdmissionCredentials;
use App\Services\Student\IdCardService;
use App\Services\Student\LeavingCertificateService;
use App\Services\Student\SiblingService;
use App\Services\StudentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * StudentController
 *
 * A RESTful resource controller for managing students.
 * Implements proper authorization, validation, caching, and error handling.
 *
 * @property StudentService $service
 * @property StudentRepository $repository
 */
class StudentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected StudentService $service,
        protected StudentRepository $repository
    ) {}

    /**
     * Display a listing of students.
     *
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): InertiaResponse
    {
        // Check authorization
        Gate::authorize('viewAny', Student::class);

        Log::info('StudentController: Listing students', [
            'user_id' => auth()->id(),
            'filters' => $request->all(),
        ]);

        $data = $this->service->getIndexData($request);

        return Inertia::render('students/Index', $data);
    }

    /**
     * Display a listing of students (API endpoint).
     *
     *
     * @throws AuthorizationException
     */
    public function apiIndex(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        return $this->service->getDropdownData($request);
    }

    /**
     * Show the form for creating a new student.
     *
     *
     * @throws AuthorizationException
     */
    public function create(): InertiaResponse
    {
        Gate::authorize('create', Student::class);

        Log::info('StudentController: Showing create form', [
            'user_id' => auth()->id(),
        ]);

        $data = $this->service->getCreateData();

        return Inertia::render('students/Create', $data);
    }

    /**
     * Store a newly created student in storage.
     *
     *
     * @throws AuthorizationException
     */
    public function store(StoreStudentRequest $request, AdmissionCredentials $credentials): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        Log::info('StudentController: Storing student', [
            'user_id' => auth()->id(),
            'admission_no' => $request->admission_no,
        ]);

        try {
            $validated = $request->validated();
            $this->service->create($validated);

            /*
             * The logins created for this admission, shown once and held
             * nowhere. The password used to be generated, hashed and dropped,
             * so every account this system made was one nobody could sign in
             * to — including the student portal's.
             */
            return redirect()->route('students.index')
                ->with('success', 'Student admitted successfully with guardian details.')
                ->with('new_logins', $credentials->take());
            // Throwable, not Exception: a bad enum value raises a ValueError,
            // which slipped past the narrower catch and returned a bare 500
            // instead of the message below.
        } catch (\Throwable $e) {
            Log::error('StudentController: Store failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to admit student: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified student.
     *
     *
     * @throws AuthorizationException
     */
    public function show(Student $student): InertiaResponse
    {
        Gate::authorize('view', $student);

        Log::info('StudentController: Showing student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $data = $this->service->getShowData($student);

        return Inertia::render('students/Show', $data);
    }

    /**
     * Show the form for editing the specified student.
     *
     *
     * @throws AuthorizationException
     */
    public function edit(Student $student): InertiaResponse
    {
        Gate::authorize('update', $student);

        Log::info('StudentController: Showing edit form', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        $data = $this->service->getEditData($student);

        return Inertia::render('students/Edit', $data);
    }

    /**
     * Update the specified student in storage.
     *
     *
     * @throws AuthorizationException
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        Log::info('StudentController: Updating student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        try {
            $validated = $request->validated();
            $this->service->update($student, $validated);

            return redirect()->route('students.index')
                ->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            Log::error('StudentController: Update failed', [
                'user_id' => auth()->id(),
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update student: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Change student status (leave or re-admission).
     *
     *
     * @throws AuthorizationException
     */
    public function changeStatus(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('changeStatus', $student);

        Log::info('StudentController: Changing status', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
            'is_reactivation' => $request->is_reactivation,
        ]);

        try {
            $validated = $request->validatedForStatusChange();
            $this->service->changeStatus($student, $validated);

            $message = ! empty($validated['is_reactivation'])
                ? 'Student has been re-admitted successfully.'
                : 'Student status has been updated and their account has been deactivated.';

            return redirect()->route('students.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('StudentController: Status change failed', [
                'user_id' => auth()->id(),
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update student status: '.$e->getMessage());
        }
    }

    /**
     * Re-admit a previously left student.
     *
     *
     * @throws AuthorizationException
     */
    public function readmit(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('readmit', $student);

        Log::info('StudentController: Re-admitting student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        try {
            $validated = $request->validatedForStatusChange();
            $this->service->readmit($student, $validated);

            return redirect()->route('students.index')
                ->with('success', 'Student has been re-admitted successfully.');
        } catch (\Exception $e) {
            Log::error('StudentController: Re-admission failed', [
                'user_id' => auth()->id(),
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to re-admit student: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified student from storage (soft delete).
     *
     *
     * @throws AuthorizationException
     */
    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        Log::info('StudentController: Deleting student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        try {
            $this->service->delete($student);

            return redirect()->route('students.index')
                ->with('success', 'Student deleted successfully.');
        } catch (\Exception $e) {
            Log::error('StudentController: Delete failed', [
                'user_id' => auth()->id(),
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete student: '.$e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted student.
     *
     *
     * @throws AuthorizationException
     */
    public function restore(Student $student): RedirectResponse
    {
        Gate::authorize('restore', $student);

        Log::info('StudentController: Restoring student', [
            'user_id' => auth()->id(),
            'student_id' => $student->id,
        ]);

        try {
            $this->service->restore($student);

            return redirect()->route('students.index')
                ->with('success', 'Student restored successfully.');
        } catch (\Exception $e) {
            Log::error('StudentController: Restore failed', [
                'user_id' => auth()->id(),
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to restore student: '.$e->getMessage());
        }
    }

    /**
     * Student ID cards (print).
     *
     * A section at a time, eight to a page, because that is how a school runs
     * them off and then cuts them. The photograph has been stored since
     * admission and nothing has ever printed it.
     */
    public function idCards(Request $request, IdCardService $cards)
    {
        Gate::authorize('viewAny', Student::class);

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        if (! empty($validated['student_ids'])) {
            $students = Student::whereIn('id', $validated['student_ids'])
                ->visibleTo($request->user())
                ->get();
        } elseif (! empty($validated['class_id'])) {
            $students = $cards->studentsIn(
                $request->user(),
                (int) $validated['class_id'],
                $validated['section_id'] ?? null
            );
        } else {
            $students = collect();
        }

        return response()->view('student.id-card', [
            'cards' => $cards->forStudents($students),
            'school' => School::where('is_active', true)->first(),
        ]);
    }

    /**
     * The other children of this child's family (API).
     *
     * Guardians are deduplicated by phone at admission, so the family has
     * always been in the data and merely unexposed. The fee module's family
     * concession is what wants it.
     */
    public function siblings(Student $student, SiblingService $siblings): JsonResponse
    {
        Gate::authorize('view', $student);

        return response()->json(['data' => $siblings->familyOf($student)]);
    }

    /**
     * The School Leaving Certificate (print).
     *
     * A child cannot be admitted to another school without one, so this is a
     * legal document rather than a convenience. It reads the leaving record
     * that `StudentEnrollmentService::leave()` writes, and refuses plainly
     * where a child has not been marked as having left.
     */
    public function leavingCertificate(Student $student, LeavingCertificateService $certificates)
    {
        Gate::authorize('view', $student);

        return response()->view('student.leaving-certificate', [
            'tc' => $certificates->forStudent($student),
        ]);
    }

    /**
     * Get sections by class for dropdown.
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        $validated = $request->validate([
            'class_id' => 'required|integer|exists:school_classes,id',
        ]);

        // `integer` validation accepts a numeric string from the query string.
        return $this->service->getSectionsByClass((int) $validated['class_id']);
    }

    /**
     * Get guardian by phone number for sibling admission.
     */
    public function getGuardianByPhone(Request $request): JsonResponse
    {
        // This looks a guardian up by phone number and returns their
        // details. It had no check at all, so any signed-in account could
        // walk the number space and read families out of the system.
        Gate::authorize('create', Student::class);

        return $this->service->getGuardianByPhone($request);
    }

    /**
     * Export the roll as a CSV.
     *
     * It used to answer "Export started. You will be notified when ready." and
     * export nothing. What comes out is scoped to what this person may see.
     *
     * @throws AuthorizationException
     */
    public function export(Request $request)
    {
        Gate::authorize('export', Student::class);

        return $this->service->export($request);
    }

    /**
     * Import students from file.
     *
     *
     * @throws AuthorizationException
     */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('import', Student::class);

        return $this->service->import($request);
    }

    /**
     * Print admission form for a student.
     *
     * @return Response
     *
     * @throws AuthorizationException
     */
    public function print(Student $student)
    {
        Gate::authorize('view', $student);

        $data = $this->service->getPrintData($student);

        return response()->view('students.print', $data)->header('Content-Type', 'text/html');
    }
}
