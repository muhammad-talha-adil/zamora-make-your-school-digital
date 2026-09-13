<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Gender;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Staff\StaffAssignment;
use App\Models\Staff\StaffDocument;
use App\Models\Staff\StaffQualification;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\Subject;
use App\Services\Staff\StaffAssignmentService;
use App\Services\Staff\StaffEmploymentService;
use App\Services\Staff\TeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The staff list, and one person's record.
 *
 * There was one screen for the whole module — the list, the departments, the
 * designations and the payroll runs all on one page — so a person had no record
 * of their own to open. This is that record.
 *
 * `StaffController` is deliberately left alone. It still serves the old screen
 * and the payroll, and both keep working until Phase 6 and Phase 7 replace them.
 */
class StaffProfileController extends Controller
{
    public function __construct(
        private StaffAssignmentService $jobs,
        private StaffEmploymentService $employment,
        private TeacherAssignmentService $teaching
    ) {}

    /**
     * Everybody who works here.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StaffProfile::class);

        return Inertia::render('Staff/People/Index', [
            'departments' => StaffDepartment::orderBy('name')->get(['id', 'name']),
            'designations' => StaffDesignation::orderBy('name')->get(['id', 'name']),
            'campuses' => Campus::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'campus_id', 'department_id', 'designation_id', 'status']),
        ]);
    }

    /**
     * The list itself (API), scoped to what this person may see.
     */
    public function list(Request $request)
    {
        Gate::authorize('viewAny', StaffProfile::class);

        $staff = StaffProfile::query()
            ->visibleTo($request->user())
            ->with(['user:id,name,email', 'campus:id,name', 'department:id,name', 'designation:id,name'])
            ->withCount(['assignments as jobs_count' => fn ($q) => $q->whereNull('ended_on')])
            ->when($request->query('campus_id'), fn ($q, $id) => $q->where('campus_id', $id))
            ->when($request->query('department_id'), fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->query('designation_id'), fn ($q, $id) => $q->where('designation_id', $id))
            ->when(
                $request->query('status') === 'inactive',
                fn ($q) => $q->where('is_active', false),
                fn ($q) => $request->query('status') === 'all' ? $q : $q->where('is_active', true)
            )
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('cnic', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('employee_no')
            ->paginate((int) $request->query('per_page', 25));

        // The same rule the record screen enforces: what somebody is paid does
        // not leave the server for anybody who may not see it, list or record.
        $viewer = $request->user();
        $maySeeSalary = $viewer !== null && ($viewer->isSuperAdmin() || $viewer->hasPermission('staff.salary.manage'));

        if (! $maySeeSalary) {
            $staff->getCollection()->each(
                fn (StaffProfile $profile) => $profile->makeHidden(['basic_salary', 'allowance_amount', 'deduction_amount'])
            );
        }

        return response()->json(['data' => $staff]);
    }

    /**
     * One person's record.
     *
     * The tabs a school actually needs: who they are, what they do, what they
     * teach, and the papers on file. What they are **paid** is not here — it is
     * its own permission, and it arrives with Phase 6.
     */
    public function show(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('view', $staffProfile);

        if (! $request->user()?->can('viewSalary', $staffProfile)) {
            $staffProfile->makeHidden(['basic_salary', 'allowance_amount', 'deduction_amount']);
        }

        $staffProfile->load([
            'user:id,name,email,username,is_active',
            'campus:id,name',
            'department:id,name',
            'designation:id,name',
            'gender:id,name',
            'qualifications',
            'subjects.subject:id,name',
            'documents.uploadedBy:id,name',
            'employmentPeriods',
        ]);

        return Inertia::render('Staff/People/Show', [
            'staff' => $staffProfile,
            'jobs' => $this->jobs->currentJobsOf($staffProfile),
            'jobHistory' => StaffAssignment::with(['designation:id,name', 'department:id,name'])
                ->where('staff_profile_id', $staffProfile->id)
                ->whereNotNull('ended_on')
                ->orderByDesc('ended_on')
                ->get(),
            'classes' => $this->teaching->classesOf($staffProfile),
            'serviceMonths' => $this->employment->serviceMonths($staffProfile),

            // For the forms on the tabs.
            'departments' => StaffDepartment::orderBy('name')->get(['id', 'name']),
            'designations' => StaffDesignation::orderBy('name')->get(['id', 'name']),
            'campuses' => Campus::orderBy('name')->get(['id', 'name']),
            'genders' => Gender::orderBy('id')->get(['id', 'name']),
            'subjectOptions' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'classOptions' => SchoolClass::where('is_active', true)
                ->orderBy('level')->orderBy('name')->get(['id', 'name']),
            'sessions' => Session::where('is_active', true)->get(['id', 'name']),

            'can' => [
                'edit' => $request->user()?->can('update', $staffProfile) ?? false,
                'viewSalary' => $request->user()?->can('viewSalary', $staffProfile) ?? false,
                'manageSalary' => $request->user()?->can('manageSalary', $staffProfile) ?? false,
                'viewAttendance' => $request->user()?->can('viewAttendance', $staffProfile) ?? false,
                'markAttendance' => $request->user()?->can('markAttendance', StaffProfile::class) ?? false,
                'applyForLeave' => $request->user()?->can('applyForLeave', $staffProfile) ?? false,
                'decideLeave' => $request->user()?->can('decideLeave', $staffProfile) ?? false,
            ],
        ]);
    }

    /**
     * The personal file — who this person is.
     *
     * None of this had anywhere to live: neither `staff_profiles` nor `users`
     * carried a CNIC, a phone number or an emergency contact.
     */
    public function updatePersonal(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'cnic' => ['nullable', 'string', 'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
        ]);

        $staffProfile->update($validated);

        return response()->json(['message' => 'Details saved', 'data' => $staffProfile->fresh()]);
    }

    /* ------------------------------------------------------------- the jobs */

    public function addJob(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'designation_id' => ['required', 'integer', 'exists:staff_designations,id'],
            'department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'is_primary' => ['nullable', 'boolean'],
            'started_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $job = $this->jobs->give($staffProfile, $validated);

        return response()->json(['message' => 'Job added', 'data' => $job], 201);
    }

    public function makeJobPrimary(StaffAssignment $assignment)
    {
        Gate::authorize('update', $assignment->staffProfile);

        return response()->json([
            'message' => 'Primary job changed',
            'data' => $this->jobs->makePrimary($assignment),
        ]);
    }

    public function endJob(Request $request, StaffAssignment $assignment)
    {
        Gate::authorize('update', $assignment->staffProfile);

        $validated = $request->validate(['ended_on' => ['nullable', 'date']]);

        return response()->json([
            'message' => 'Job ended',
            'data' => $this->jobs->end($assignment, $validated['ended_on'] ?? null),
        ]);
    }

    /* ------------------------------------------------------- joining/leaving */

    public function leave(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'left_on' => ['nullable', 'date'],
            'leaving_reason' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json([
            'message' => 'Recorded as having left',
            'data' => $this->employment->leave(
                $staffProfile,
                $validated['left_on'] ?? null,
                $validated['leaving_reason'] ?? null,
                $validated['notes'] ?? null,
                $request->user()
            ),
        ]);
    }

    public function rejoin(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'joined_on' => ['nullable', 'date'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ]);

        return response()->json([
            'message' => 'Taken back on',
            'data' => $this->employment->rejoin($staffProfile, $validated, $request->user()),
        ]);
    }

    /* ------------------------------------------------------- the file itself */

    public function addQualification(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'year_completed' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'grade' => ['nullable', 'string', 'max:50'],
        ]);

        $qualification = $staffProfile->qualifications()->create($validated);

        return response()->json(['message' => 'Qualification added', 'data' => $qualification], 201);
    }

    public function removeQualification(StaffQualification $qualification)
    {
        Gate::authorize('update', $qualification->staffProfile);

        $qualification->delete();

        return response()->json(['message' => 'Qualification removed']);
    }

    public function addDocument(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $validated = $request->validate([
            'kind' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:150'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'issued_on' => ['nullable', 'date'],
            // A lapsed contract or police verification is what a school is
            // fined for, and nobody notices it in a folder.
            'expires_on' => ['nullable', 'date', 'after:issued_on'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->hasFile('file')
            ? $request->file('file')->store('staff/'.$staffProfile->id, 'public')
            : null;

        $document = $staffProfile->documents()->create(
            collect($validated)->except('file')->all() + [
                'path' => $path,
                'uploaded_by' => $request->user()?->id,
            ]
        );

        return response()->json(['message' => 'Document filed', 'data' => $document], 201);
    }

    public function removeDocument(StaffDocument $document)
    {
        Gate::authorize('update', $document->staffProfile);

        $document->delete();

        return response()->json(['message' => 'Document removed']);
    }
}
