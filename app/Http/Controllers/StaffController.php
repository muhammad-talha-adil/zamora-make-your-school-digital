<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Staff\StaffAttendance;
use App\Models\Staff\StaffDocument;
use App\Models\Staff\StaffLeave;
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
            'deduction_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'bank_name' => 'nullable|string|max:150',
            'account_no' => 'nullable|string|max:150',
            'is_active' => 'boolean',
        ]);

        $profile = DB::transaction(function () use ($data) {
            $employeeNo = $data['employee_no'] ?: $this->generateEmployeeNo();
            $email = $data['email'] ?: strtolower(Str::slug($data['name'], '')).'.'.$employeeNo.'@staff.local';

            $user = User::create([
                'username' => strtolower($employeeNo),
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make('password123'),
                'is_active' => $data['is_active'] ?? true,
            ]);

            return StaffProfile::create([
                'user_id' => $user->id,
                'employee_no' => $employeeNo,
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

    public function payPayrollItem(Request $request, PayrollRunItem $payrollRunItem)
    {
        // Releasing money is not the same act as working the figures out.
        Gate::authorize('approvePayroll', StaffProfile::class);

        $data = $request->validate([
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:150',
        ]);

        $payrollItem = $this->payroll->pay($payrollRunItem, $data);

        return response()->json([
            'success' => true,
            'message' => 'Salary payment marked successfully.',
            'payrollItem' => $payrollItem,
        ]);
    }

    protected function generateEmployeeNo(): string
    {
        $nextId = (int) (StaffProfile::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
