<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\GiveStaffAdvanceRequest;
use App\Http\Requests\Staff\PayPayrollItemRequest;
use App\Http\Requests\Staff\ReturnStaffAdvanceRequest;
use App\Models\Campus;
use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Role;
use App\Models\Staff\StaffAttendance;
use App\Models\Staff\StaffDocument;
use App\Models\Staff\StaffDocumentType;
use App\Models\Staff\StaffLeave;
use App\Models\StaffAdvance;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Staff\StaffPayrollService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(
        protected StaffPayrollService $payroll
    ) {}

    /**
     * The overview a school opens to first.
     *
     * `Staff/Index.vue` used to be the staff list, the payroll runs, the
     * departments and the designations all crammed onto one page — the last
     * thing built, once every other job had somewhere better to live: the
     * staff list moved to `Staff\StaffProfileController::index()`, payroll to
     * `payrollPage()` below, and the department/designation lookups now sit in
     * a panel on the staff list itself.
     */
    public function index()
    {
        $viewer = request()->user();

        Gate::authorize('viewAny', StaffProfile::class);

        $staff = StaffProfile::query()->visibleTo($viewer);
        $activeStaff = (clone $staff)->where('is_active', true);

        $maySeeSalary = $viewer !== null
            && ($viewer->isSuperAdmin() || $viewer->hasPermission('staff.salary.manage'));

        $pendingPayroll = $maySeeSalary
            ? (float) PayrollRun::where('status', '!=', 'paid')
                ->when(
                    $viewer && ! $viewer->isSuperAdmin() && $viewer->campusId(),
                    fn ($query) => $query->where(function ($q) use ($viewer) {
                        $q->whereNull('campus_id')->orWhere('campus_id', $viewer->campusId());
                    })
                )
                ->sum('total_net')
            : null;

        $today = now()->toDateString();

        return Inertia::render('Staff/Dashboard', [
            // Selected columns only: the full model carries the salary fields,
            // which do not belong on a dashboard nobody's viewSalary was checked
            // for.
            'recentJoiners' => StaffProfile::query()
                ->select(['id', 'user_id', 'employee_no', 'hire_date', 'campus_id', 'designation_id'])
                ->with(['user:id,name', 'campus:id,name', 'designation:id,name'])
                ->visibleTo($viewer)
                ->whereNotNull('hire_date')
                ->orderByDesc('hire_date')
                ->take(5)
                ->get(),
            'summary' => [
                'active_staff' => (clone $activeStaff)->count(),
                'monthly_salary' => $maySeeSalary
                    ? (float) (clone $activeStaff)->get()->sum(fn (StaffProfile $profile) => $profile->net_salary)
                    : null,
                'pending_payroll' => $pendingPayroll,
                'present_today' => StaffAttendance::whereDate('attendance_date', $today)
                    ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                    ->whereHas('status', fn ($q) => $q->where('code', '!=', 'A'))
                    ->count(),
                'on_leave_today' => StaffLeave::approved()
                    ->overlapping($today, $today)
                    ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                    ->count(),
                'pending_leave_requests' => StaffLeave::where('status', StaffLeave::STATUS_PENDING)
                    ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                    ->count(),
                'documents_expiring_soon' => StaffDocument::expiringBy(now()->addDays(30)->toDateString())
                    ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                    ->count(),
            ],
        ]);
    }

    /**
     * The payroll screen: generate a run, then release it.
     */
    /**
     * The full lookup panel — departments, designations and document types —
     * moved out of the staff list's dialog into a screen of its own.
     */
    public function settingsPage()
    {
        Gate::authorize('manageStructure', StaffProfile::class);

        return Inertia::render('Staff/Settings/Index', [
            'departments' => StaffDepartment::orderBy('name')->get(),
            'designations' => StaffDesignation::orderBy('name')->get(),
            'documentTypes' => StaffDocumentType::orderBy('name')->get(),
            // Self-scoped roles (student, guardian) are never valid for a
            // staff designation — only staff-facing roles belong here.
            'roles' => Role::where('scope_level', '!=', Role::SCOPE_SELF)->orderBy('name')->get(['id', 'name', 'label']),
        ]);
    }

    public function payrollPage(Request $request)
    {
        $viewer = $request->user();

        Gate::authorize('runPayroll', StaffProfile::class);

        $payrollRuns = PayrollRun::with(['campus', 'month', 'items.staffProfile.user'])
            ->when(
                $viewer && ! $viewer->isSuperAdmin() && $viewer->campusId(),
                fn ($query) => $query->where(function ($q) use ($viewer) {
                    $q->whereNull('campus_id')->orWhere('campus_id', $viewer->campusId());
                })
            )
            ->latest()
            ->take(12)
            ->get();

        return Inertia::render('Staff/Payroll/Index', [
            'campuses' => Campus::select('id', 'name')->orderBy('name')->get(),
            'months' => Month::select('id', 'name', 'month_number')->orderBy('month_number')->get(),
            'payrollRuns' => $payrollRuns,
            'can' => [
                'approve' => $viewer?->can('approvePayroll', StaffProfile::class) ?? false,
            ],
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:staff_departments,name',
            'description' => 'nullable|string|max:255',
        ]);

        $department = StaffDepartment::create($data + ['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'department' => $department,
        ]);
    }

    public function updateDepartment(Request $request, StaffDepartment $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:staff_departments,name,'.$department->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $department->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'department' => $department->fresh(),
        ]);
    }

    public function storeDesignation(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:staff_designations,name',
            'description' => 'nullable|string|max:255',
            // Self-scoped roles (student, guardian) are never valid for a
            // staff designation.
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where(fn ($q) => $q->where('scope_level', '!=', Role::SCOPE_SELF))],
        ]);

        $designation = StaffDesignation::create($data + ['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Designation created successfully.',
            'designation' => $designation,
        ]);
    }

    public function updateDesignation(Request $request, StaffDesignation $designation)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:staff_designations,name,'.$designation->id,
            'description' => 'nullable|string|max:255',
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where(fn ($q) => $q->where('scope_level', '!=', Role::SCOPE_SELF))],
            'is_active' => 'boolean',
        ]);

        $designation->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Designation updated successfully.',
            'designation' => $designation->fresh(),
        ]);
    }

    public function storeStaff(Request $request)
    {
        Gate::authorize('create', StaffProfile::class);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:users,email',
            'employee_no' => 'nullable|string|max:50|unique:staff_profiles,employee_no',
            'campus_id' => 'nullable|exists:campuses,id',
            'department_id' => 'nullable|exists:staff_departments,id',
            'designation_id' => 'nullable|exists:staff_designations,id',
            'employment_type' => 'required|string|max:50',
            'hire_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:150',
            'account_no' => 'nullable|string|max:150',
            'is_active' => 'boolean',
            'documents' => 'nullable|array',
            'documents.*.kind' => 'required_with:documents|string|max:50',
            'documents.*.title' => 'required_with:documents|string|max:150',
            'documents.*.issued_on' => 'nullable|date',
            'documents.*.expires_on' => 'nullable|date|after:documents.*.issued_on',
            'documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $profile = DB::transaction(function () use ($data, $request) {
            $employeeNo = $data['employee_no'] ?: $this->generateEmployeeNo();
            $email = $data['email'] ?: strtolower(Str::slug($data['name'], '')).'.'.$employeeNo.'@staff.local';

            $user = User::create([
                'username' => strtolower($employeeNo),
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make('password123'),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $profile = StaffProfile::create([
                'user_id' => $user->id,
                'employee_no' => $employeeNo,
                'campus_id' => $data['campus_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'employment_type' => $data['employment_type'],
                'hire_date' => $data['hire_date'] ?? null,
                'basic_salary' => $data['basic_salary'],
                'allowance_amount' => $data['allowance_amount'] ?? 0,
                'payment_method' => $data['payment_method'],
                'bank_name' => $data['bank_name'] ?? null,
                'account_no' => $data['account_no'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->assignDesignationRole($user, $data['designation_id'] ?? null);

            foreach ($data['documents'] ?? [] as $index => $document) {
                $file = $request->file("documents.{$index}.file");
                $path = $file?->store('staff/'.$profile->id, 'public');

                $profile->documents()->create([
                    'kind' => $document['kind'],
                    'title' => $document['title'],
                    'path' => $path,
                    'issued_on' => $document['issued_on'] ?? null,
                    'expires_on' => $document['expires_on'] ?? null,
                    'uploaded_by' => auth()->id(),
                ]);
            }

            return $profile;
        });

        return response()->json([
            'success' => true,
            'message' => 'Staff member created successfully.',
            'staff' => $profile->load(['user', 'campus', 'department', 'designation']),
        ]);
    }

    public function updateStaff(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:users,email,'.$staffProfile->user_id,
            'employee_no' => 'required|string|max:50|unique:staff_profiles,employee_no,'.$staffProfile->id,
            'campus_id' => 'nullable|exists:campuses,id',
            'department_id' => 'nullable|exists:staff_departments,id',
            'designation_id' => 'nullable|exists:staff_designations,id',
            'employment_type' => 'required|string|max:50',
            'hire_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'deduction_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:150',
            'account_no' => 'nullable|string|max:150',
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($staffProfile, $data) {
            $previousDesignationId = $staffProfile->designation_id;

            $staffProfile->user->update([
                'name' => $data['name'],
                'email' => $data['email'] ?: $staffProfile->user->email,
                'username' => strtolower($data['employee_no']),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $staffProfile->update([
                'employee_no' => $data['employee_no'],
                'campus_id' => $data['campus_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'designation_id' => $data['designation_id'] ?? null,
                'employment_type' => $data['employment_type'],
                'hire_date' => $data['hire_date'] ?? null,
                'basic_salary' => $data['basic_salary'],
                'allowance_amount' => $data['allowance_amount'] ?? 0,
                'deduction_amount' => $data['deduction_amount'] ?? 0,
                'payment_method' => $data['payment_method'],
                'bank_name' => $data['bank_name'] ?? null,
                'account_no' => $data['account_no'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Only touch roles when the designation actually changed in this
            // request — an admin may have granted extra roles by hand, and
            // resaving unrelated fields (salary, bank details, ...) must not
            // silently re-run the designation's role grant every time.
            if (($data['designation_id'] ?? null) !== $previousDesignationId) {
                $this->assignDesignationRole($staffProfile->user, $data['designation_id'] ?? null);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Staff member updated successfully.',
            'staff' => $staffProfile->fresh(['user', 'campus', 'department', 'designation']),
        ]);
    }

    public function toggleStaff(StaffProfile $staffProfile)
    {
        Gate::authorize('update', $staffProfile);

        $staffProfile->update(['is_active' => ! $staffProfile->is_active]);
        $staffProfile->user?->update(['is_active' => $staffProfile->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Staff status updated successfully.',
            'staff' => $staffProfile->fresh(['user', 'campus', 'department', 'designation']),
        ]);
    }

    public function generatePayroll(Request $request)
    {
        Gate::authorize('runPayroll', StaffProfile::class);

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'payroll_month_id' => 'required|exists:months,id',
            'payroll_year' => 'required|integer|min:2000|max:2100',
            'title' => 'nullable|string|max:150',
        ]);

        $run = $this->payroll->generateRun($data, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Payroll generated successfully.',
            'payrollRun' => $run,
        ]);
    }

    public function payPayrollItem(PayPayrollItemRequest $request, PayrollRunItem $payrollRunItem)
    {
        // Releasing money is not the same act as working the figures out.
        Gate::authorize('approvePayroll', StaffProfile::class);

        $payrollItem = $this->payroll->pay($payrollRunItem, $request->validated());

        return response()->json([
            'success' => true,
            'message' => $payrollItem->status === 'paid'
                ? 'Salary payment marked successfully.'
                : 'Partial payment recorded.',
            'payrollItem' => $payrollItem,
        ]);
    }

    /**
     * Disburses a salary advance for a staff member — a lump sum now,
     * outside any specific payroll item.
     */
    public function giveAdvance(GiveStaffAdvanceRequest $request)
    {
        $staff = StaffProfile::findOrFail($request->validated('staff_profile_id'));
        Gate::authorize('approvePayroll', StaffProfile::class);
        Gate::authorize('view', $staff);

        $advance = $this->payroll->giveAdvance($request->validated(), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Advance disbursed successfully.',
            'advance' => $advance,
        ]);
    }

    /**
     * Lists a staff member's advance history — used by both the payroll
     * screen and their own profile page.
     */
    public function advancesForStaff(StaffProfile $staffProfile)
    {
        Gate::authorize('view', $staffProfile);

        $advances = $staffProfile->advances()
            ->with('deductions')
            ->latest('disbursed_date')
            ->get();

        return response()->json([
            'advances' => $advances,
        ]);
    }

    /**
     * Early close: paid back directly, or waived. Stops future deductions.
     */
    public function returnAdvance(ReturnStaffAdvanceRequest $request, StaffAdvance $staffAdvance)
    {
        Gate::authorize('approvePayroll', StaffProfile::class);

        $advance = $this->payroll->returnAdvance($staffAdvance, $request->validated('status'));

        if ($request->filled('notes')) {
            $advance->update(['notes' => $request->validated('notes')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advance marked '.$advance->status.'.',
            'advance' => $advance,
        ]);
    }

    /**
     * Grants the Spatie role mapped to a designation, if any.
     *
     * Additive (`assignRole`), never destructive: a fresh hire has no roles
     * to preserve, and a designation change should not strip a role an admin
     * granted by hand for reasons the designation mapping does not know
     * about.
     */
    protected function assignDesignationRole(User $user, ?int $designationId): void
    {
        if (! $designationId) {
            return;
        }

        $role = StaffDesignation::find($designationId)?->role;

        if ($role) {
            $user->assignRole($role);
        }
    }

    protected function generateEmployeeNo(): string
    {
        $nextId = (int) (StaffProfile::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
