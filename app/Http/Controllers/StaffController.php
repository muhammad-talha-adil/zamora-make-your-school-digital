<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function __construct(
        protected UnifiedAccountingService $accountingService
    ) {}

    public function index()
    {
        $staff = StaffProfile::with(['user', 'campus', 'department', 'designation'])
            ->latest()
            ->get();

        $payrollRuns = PayrollRun::with(['campus', 'month', 'items.staffProfile.user'])
            ->latest()
            ->take(12)
            ->get();

        return Inertia::render('Staff/Index', [
            'staffMembers' => $staff,
            'departments' => StaffDepartment::orderBy('name')->get(),
            'designations' => StaffDesignation::orderBy('name')->get(),
            'campuses' => Campus::select('id', 'name')->orderBy('name')->get(),
            'months' => Month::select('id', 'name')->orderBy('id')->get(),
            'payrollRuns' => $payrollRuns,
            'summary' => [
                'active_staff' => $staff->where('is_active', true)->count(),
                'monthly_salary' => (float) $staff->where('is_active', true)->sum(fn (StaffProfile $profile) => $profile->net_salary),
                'pending_payroll' => (float) $payrollRuns->where('status', '!=', 'paid')->sum('total_net'),
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
        $data = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'payroll_month_id' => 'required|exists:months,id',
            'payroll_year' => 'required|integer|min:2000|max:2100',
            'title' => 'nullable|string|max:150',
        ]);

        $staffProfiles = StaffProfile::with('user')
            ->where('is_active', true)
            ->when($data['campus_id'] ?? null, fn ($query, $campusId) => $query->where('campus_id', $campusId))
            ->get();

        $run = DB::transaction(function () use ($data, $staffProfiles) {
            $title = $data['title'] ?: 'Payroll '.$data['payroll_month_id'].'/'.$data['payroll_year'];

            $run = PayrollRun::updateOrCreate(
                [
                    'campus_id' => $data['campus_id'] ?? null,
                    'payroll_month_id' => $data['payroll_month_id'],
                    'payroll_year' => $data['payroll_year'],
                ],
                [
                    'title' => $title,
                    'status' => 'processed',
                    'processed_at' => now(),
                    'created_by' => auth()->id(),
                ]
            );

            $run->items()->delete();

            $totals = [
                'gross' => 0,
                'deductions' => 0,
                'net' => 0,
            ];

            foreach ($staffProfiles as $profile) {
                $gross = $profile->gross_salary;
                $deductions = (float) $profile->deduction_amount;
                $net = $profile->net_salary;

                $run->items()->create([
                    'staff_profile_id' => $profile->id,
                    'gross_salary' => $profile->basic_salary,
                    'allowance_amount' => $profile->allowance_amount,
                    'deduction_amount' => $deductions,
                    'net_salary' => $net,
                    'status' => 'pending',
                    'payment_method' => $profile->payment_method,
                ]);

                $totals['gross'] += $gross;
                $totals['deductions'] += $deductions;
                $totals['net'] += $net;
            }

            $run->update([
                'total_gross' => $totals['gross'],
                'total_deductions' => $totals['deductions'],
                'total_net' => $totals['net'],
            ]);

            return $run;
        });

        $this->accountingService->postPayrollAccrualJournal($run->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Payroll generated successfully.',
            'payrollRun' => $run->fresh(['campus', 'month', 'items.staffProfile.user']),
        ]);
    }

    public function payPayrollItem(Request $request, PayrollRunItem $payrollRunItem)
    {
        $data = $request->validate([
            'payment_method' => 'required|string|max:50',
            'reference_no' => 'nullable|string|max:150',
        ]);

        $payrollRunItem->update([
            'status' => 'paid',
            'payment_method' => $data['payment_method'],
            'reference_no' => $data['reference_no'] ?? null,
            'paid_at' => now(),
        ]);

        $this->accountingService->postPayrollPaymentJournal($payrollRunItem->fresh(['payrollRun', 'staffProfile.user']));

        $run = $payrollRunItem->payrollRun()->with('items')->first();

        if ($run && $run->items->every(fn (PayrollRunItem $item) => $item->status === 'paid')) {
            $run->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Salary payment marked successfully.',
            'payrollItem' => $payrollRunItem->fresh(['staffProfile.user']),
        ]);
    }

    protected function generateEmployeeNo(): string
    {
        $nextId = (int) (StaffProfile::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
