<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Policies\StudentPolicy;
use App\Repositories\StudentRepository;
use App\Services\StudentService;
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
     * @param Request $request
     * @return InertiaResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apiIndex(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        return $this->service->getDropdownData($request);
    }

    /**
     * Show the form for creating a new student.
     *
     * @return InertiaResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param StoreStudentRequest $request
     * @return RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        Log::info('StudentController: Storing student', [
            'user_id' => auth()->id(),
            'admission_no' => $request->admission_no,
        ]);

        try {
            $validated = $request->validated();
            $this->service->create($validated);

            return redirect()->route('students.index')
                ->with('success', 'Student admitted successfully with guardian details.');
        } catch (\Exception $e) {
            Log::error('StudentController: Store failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to admit student: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified student.
     *
     * @param Student $student
     * @return InertiaResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param Student $student
     * @return InertiaResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @param UpdateStudentRequest $request
     * @param Student $student
     * @return RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
                ->with('error', 'Failed to update student: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Change student status (leave or re-admission).
     *
     * @param UpdateStudentRequest $request
     * @param Student $student
     * @return RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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

            $message = !empty($validated['is_reactivation'])
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
                ->with('error', 'Failed to update student status: ' . $e->getMessage());
        }
    }

    /**
     * Re-admit a previously left student.
     *
     * @param UpdateStudentRequest $request
     * @param Student $student
     * @return RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
                ->with('error', 'Failed to re-admit student: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified student from storage (soft delete).
     *
     * @param Student $student
     * @return RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
                ->with('error', 'Failed to delete student: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted student.
     *
     * @param Student $student
     * @return RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
                ->with('error', 'Failed to restore student: ' . $e->getMessage());
        }
    }

    /**
     * Get sections by class for dropdown.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'required|integer|exists:school_classes,id',
        ]);

        return $this->service->getSectionsByClass($validated['class_id']);
    }

    /**
     * Get guardian by phone number for sibling admission.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGuardianByPhone(Request $request): JsonResponse
    {
        return $this->service->getGuardianByPhone($request);
    }

    /**
     * Export students data.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function export(Request $request): JsonResponse
    {
        Gate::authorize('export', Student::class);

        return $this->service->export($request);
    }

    /**
     * Import students from file.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('import', Student::class);

        return $this->service->import($request);
    }
}
