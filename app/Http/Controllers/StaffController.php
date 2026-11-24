<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Staff\StaffProfileController;
use App\Http\Requests\Staff\GiveStaffAdvanceRequest;
use App\Http\Requests\Staff\PayPayrollItemRequest;
use App\Http\Requests\Staff\ReturnStaffAdvanceRequest;
use App\Http\Requests\Staff\SaveDesignationRequest;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Campus;
use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff\StaffAssignment;
use App\Models\Staff\StaffAttendance;
use App\Models\Staff\StaffDocument;
use App\Models\Staff\StaffDocumentType;
use App\Models\Staff\StaffLeave;
use App\Models\StaffAdvance;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Staff\StaffLoginService;
use App\Services\Staff\StaffPayrollService;
use App\Services\Staff\StaffSalaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(
        protected StaffPayrollService $payroll,
        protected StaffLoginService $logins,
        protected StaffSalaryService $salary
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
            ? round((float) PayrollRunItem::query()
                ->where('status', '!=', 'paid')
                ->whereHas('payrollRun', fn ($run) => $run->when(
                    $viewer && $viewer->campusId(),
                    fn ($query) => $query->where(fn ($q) => $q->whereNull('campus_id')->orWhere('campus_id', $viewer->campusId()))
                ))
                ->get()
                ->sum(fn (PayrollRunItem $item) => $item->amountDue()), 2)
            : null;

        $today = now()->toDateString();

        return Inertia::render('Staff/Dashboard', [
            // Selected columns only: the full model carries the salary fields,
            // which do not belong on a dashboard nobody's viewSalary was checked
            // for.
            'recentJoiners' => StaffProfile::query()
                ->select(['id', 'user_id', 'employee_no', 'hire_date', 'created_at', 'campus_id', 'designation_id'])
                ->with(['user:id,name', 'campus:id,name', 'designation:id,name'])
                ->visibleTo($viewer)
                ->orderByRaw('COALESCE(hire_date, DATE(created_at)) DESC')
                ->orderByDesc('id')
                ->take(5)
                ->get(),
            'summary' => [
                'active_staff' => (clone $activeStaff)->count(),
                'monthly_salary' => $maySeeSalary
                    ? (float) (clone $activeStaff)->get()->sum(fn (StaffProfile $profile) => $this->salary->grossOn($profile, $today))
                    : null,
                'pending_payroll' => $pendingPayroll,
                'present_today' => StaffAttendance::whereDate('attendance_date', $today)
                    ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                    ->whereHas('status', fn ($q) => $q->whereIn('code', ['P', 'LT', 'HD']))
                    ->count(),
                'on_leave_today' => StaffAttendance::whereDate('attendance_date', $today)
                    ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                    ->whereHas('status', fn ($q) => $q->where('code', 'L'))
                    ->pluck('staff_profile_id')
                    ->merge(
                        StaffLeave::approved()
                            ->overlapping($today, $today)
                            ->whereHas('staffProfile', fn ($q) => $q->visibleTo($viewer))
                            ->pluck('staff_profile_id')
                    )
                    ->unique()
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

        // Self-scoped roles (student, guardian) are never valid for a staff
        // designation. developer/owner never are either. campus_admin only
        // shows for whoever is themselves allowed to hand it out — the same
        // restriction User::assignRole() already enforces when the role is
        // actually applied, surfaced here so the dropdown doesn't offer an
        // option that would just abort on save.

        return Inertia::render('Staff/Settings/Index', [
            'designations' => StaffDesignation::orderBy('name')->get(),
            'documentTypes' => StaffDocumentType::orderBy('name')->get(),
            'permissionGroups' => StaffProfileController::groupedPermissions(),
        ]);
    }

    public function payrollPage(Request $request)
    {
        $viewer = $request->user();

        Gate::authorize('runPayroll', StaffProfile::class);

        $payrollRuns = PayrollRun::with(['campus', 'month', 'items.staffProfile.user'])
            ->when(
                $viewer?->campusId(),
                fn ($query, $campusId) => $query->where('campus_id', $campusId)
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

    public function storeDesignation(SaveDesignationRequest $request)
    {
        $designation = StaffDesignation::create($this->withSafeTemplate($request->safe()->except('is_active')) + ['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Designation created successfully.',
            'designation' => $designation,
        ]);
    }

    public function updateDesignation(SaveDesignationRequest $request, StaffDesignation $designation)
    {
        $designation->update($this->withSafeTemplate($request->validated()));

        return response()->json([
            'success' => true,
            'message' => 'Designation updated successfully.',
            'designation' => $designation->fresh(),
        ]);
    }

    /**
     * A position nobody holds can go; one that is in use is blocked with a
     * plain sentence rather than a database error.
     */
    public function destroyDesignation(StaffDesignation $designation)
    {
        $inUse = StaffProfile::withTrashed()->where('designation_id', $designation->id)->count()
            + StaffAssignment::where('designation_id', $designation->id)->count();

        if ($inUse > 0) {
            return response()->json([
                'success' => false,
                'message' => "\"{$designation->name}\" is in use by {$inUse} staff record".($inUse === 1 ? '' : 's').'. Move them to another position first.',
            ], 422);
        }

        $designation->delete();

        return response()->json(['success' => true, 'message' => 'Designation deleted.']);
    }

    public function storeStaff(StoreStaffRequest $request)
    {
        $data = $request->validated();

        $profile = DB::transaction(function () use ($data, $request) {
            $employeeNo = $data['employee_no'] ?? $this->generateEmployeeNo();
            $wantsLogin = $request->boolean('create_login', true);
            $email = $this->logins->emailFor($data['name'], $data['email'] ?? null);

            // Every staff record needs a user row. Without a login the
            // password is random and never shown, so nobody can sign in.
            $user = User::create([
                'username' => strtolower($employeeNo),
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Only an owner/developer may file staff under another campus.
            $viewer = $request->user();
            $campusId = $viewer->isSuperAdmin()
                ? ($data['campus_id'] ?? $viewer->campusId())
                : $viewer->campusId();

            $photoPath = $request->file('photo')?->store('staff/photos', 'public');

            // Filed under whichever campus the creator is currently scoped to
            // (their own campus, or the header switcher's pick for an
            // owner/developer) - not a client-supplied value.
            $profile = StaffProfile::create([
                'user_id' => $user->id,
                'employee_no' => $employeeNo,
                'campus_id' => $campusId,
                'cnic' => $data['cnic'] ?? null,
                'phone' => $data['phone'] ?? null,
                'photo' => $photoPath ? '/storage/'.$photoPath : null,
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

            if (! empty($data['role'])) {
                $user->assignRole($data['role']);
            }

            $this->copyDesignationTemplate($user, $data['designation_id'] ?? null);

            $login = $wantsLogin
                ? $this->logins->issue($user->refresh(), $email)
                : null;

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

            return [$profile, $login];
        });

        [$profile, $login] = $profile;

        return response()->json([
            'success' => true,
            'message' => 'Staff member added.',
            'staff' => $profile->load(['user', 'campus', 'department', 'designation']),
            // Shown once on the success screen, never kept.
            'login' => $login,
        ]);
    }

    public function updateStaff(UpdateStaffRequest $request, StaffProfile $staffProfile)
    {
        $data = $request->validated();

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
        if ($request->user()?->campusId() !== null) {
            $request->merge(['campus_id' => $request->user()->campusId()]);
        }

        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'payroll_month_id' => 'required|exists:months,id',
            'payroll_year' => 'required|integer|min:2000|max:2100',
            'title' => 'nullable|string|max:150',
        ]);

        Gate::authorize('runPayroll', [StaffProfile::class, $data['campus_id'] ?? null]);

        $campusId = isset($data['campus_id']) ? (int) $data['campus_id'] : null;
        $overlapping = $this->payroll->overlappingRuns($campusId, (int) $data['payroll_month_id'], (int) $data['payroll_year']);
        $monthName = Month::find($data['payroll_month_id'])?->name;

        if ($overlapping->isNotEmpty() && ! $request->boolean('confirm_replace')) {
            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'message' => "This will replace the existing run for {$monthName} {$data['payroll_year']}. Anything already paid is kept.",
            ], 409);
        }

        $otherScope = $overlapping->filter(fn (PayrollRun $run) => $run->campus_id !== $campusId);
        $blocking = $otherScope->first(fn (PayrollRun $run) => $this->payroll->hasPayments($run));

        if ($blocking) {
            $scope = $blocking->campus?->name ?? 'All Campuses';

            return response()->json([
                'success' => false,
                'message' => "The {$scope} run for {$monthName} {$data['payroll_year']} already has payments, so it cannot be replaced by a different campus run.",
            ], 422);
        }

        $otherScope->each(fn (PayrollRun $run) => $this->payroll->retireRun($run));

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
        Gate::authorize('approvePayroll', [StaffProfile::class, $payrollRunItem->staffProfile->campus_id]);

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
        Gate::authorize('approvePayroll', [StaffProfile::class, $staff->campus_id]);
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
        Gate::authorize('approvePayroll', [StaffProfile::class, $staffAdvance->staffProfile->campus_id]);

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
     *
     * `campus_admin` carries two extra rules — only an owner/developer may
     * hand it out, and a campus may only have one active holder — both
     * enforced centrally in {@see User::assignRole()} so every grant path
     * (not just this one) is covered.
     */
    /**
     * Copies the designation's permission template onto this user's own
     * permissions. A copy, not a link: editing the user later, or the
     * template later, never touches the other.
     */
    protected function copyDesignationTemplate(User $user, ?int $designationId): void
    {
        if (! $designationId) {
            return;
        }

        $names = StaffDesignation::find($designationId)?->permission_template ?? [];
        $names = array_values(array_diff($names, StaffProfileController::ownerOnlyPermissions()));

        if ($names !== []) {
            $user->givePermissionTo(Permission::whereIn('name', $names)->pluck('name')->all());
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withSafeTemplate(array $data): array
    {
        if (isset($data['permission_template'])) {
            $data['permission_template'] = array_values(array_diff($data['permission_template'], StaffProfileController::ownerOnlyPermissions()));
        }

        return $data;
    }

    protected function generateEmployeeNo(): string
    {
        $nextId = (int) (StaffProfile::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
