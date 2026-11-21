<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ResolvesOwnStaffProfile;
use App\Http\Controllers\Controller;
use App\Models\PayrollRunItem;
use App\Models\School;
use App\Models\Session;
use App\Models\Staff\StaffLeave;
use App\Models\TeacherClassAssignment;
use App\Services\Staff\StaffAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member of staff's own overview, and the printable salary slip.
 */
class StaffSelfServiceController extends Controller
{
    use ResolvesOwnStaffProfile;

    public function __construct(private StaffAttendanceService $attendance) {}

    /**
     * Own attendance this month, pending leave, latest payroll item and
     * teaching assignments, all resolved from the signed-in user.
     */
    public function dashboard(Request $request): Response
    {
        $staff = $this->resolveOwnStaffProfile($request->user());
        Gate::authorize('view', $staff);

        $today = now();
        $latestItem = $staff->payrollItems()
            ->with('payrollRun.month:id,name')
            ->latest('id')
            ->first();

        $sessionId = Session::where('is_active', true)->value('id');

        return Inertia::render('Staff/Me/Dashboard', [
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->user?->name,
                'employee_no' => $staff->employee_no,
            ],
            'attendance' => [
                'month' => $today->format('F Y'),
                'summary' => $this->attendance->monthlySummary($staff, $today->month, $today->month, $today->year),
            ],
            'pendingLeaves' => $staff->leaves()
                ->with('leaveType:id,name')
                ->where('status', StaffLeave::STATUS_PENDING)
                ->orderBy('from_date')
                ->get()
                ->map(fn (StaffLeave $leave) => [
                    'id' => $leave->id,
                    'type' => $leave->leaveType?->name,
                    'from_date' => $leave->from_date?->toDateString(),
                    'to_date' => $leave->to_date?->toDateString(),
                    'days' => (float) $leave->days,
                ])->values(),
            'latestPayroll' => $latestItem === null ? null : [
                'id' => $latestItem->id,
                'period' => trim(($latestItem->payrollRun?->month?->name ?? '').' '.($latestItem->payrollRun?->payroll_year ?? '')),
                'net_salary' => (float) $latestItem->net_salary,
                'amount_paid' => (float) $latestItem->amount_paid,
                'status' => $latestItem->status,
            ],
            'teaching' => $staff->teachingAssignments()
                ->with(['schoolClass:id,name', 'section:id,name', 'subject:id,name'])
                ->where('is_active', true)
                ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
                ->get()
                ->map(fn (TeacherClassAssignment $a) => [
                    'id' => $a->id,
                    'class' => $a->schoolClass?->name,
                    'section' => $a->section?->name,
                    'subject' => $a->subject?->name,
                    'is_class_teacher' => $a->is_class_teacher,
                ])->values(),
        ]);
    }

    /**
     * Print-styled slip for one payroll item. Owner or salary managers only.
     */
    public function salarySlip(PayrollRunItem $payrollRunItem): Response
    {
        $payrollRunItem->load(['payrollRun.month', 'payrollRun.campus', 'staffProfile.user', 'staffProfile.designation']);
        Gate::authorize('viewPayslip', $payrollRunItem->staffProfile);

        $item = $payrollRunItem;
        $school = School::where('is_active', true)->first();

        return Inertia::render('Staff/Payroll/SalarySlip', [
            'school' => ['name' => $school?->name, 'address' => $school?->address, 'phone' => $school?->phone],
            'slip' => [
                'id' => $item->id,
                'period' => trim(($item->payrollRun?->month?->name ?? '').' '.($item->payrollRun?->payroll_year ?? '')),
                'staff_name' => $item->staffProfile->user?->name,
                'employee_no' => $item->staffProfile->employee_no,
                'designation' => $item->staffProfile->designation?->name,
                'campus' => $item->payrollRun?->campus?->name,
                'gross_salary' => (float) $item->gross_salary,
                'allowance_amount' => (float) $item->allowance_amount,
                'deduction_amount' => (float) $item->deduction_amount,
                'advance_deduction_amount' => (float) $item->advance_deduction_amount,
                'net_salary' => (float) $item->net_salary,
                'amount_paid' => (float) $item->amount_paid,
                'amount_due' => $item->amountDue(),
                'status' => $item->status,
                'payment_method' => $item->payment_method,
                'reference_no' => $item->reference_no,
                'paid_at' => $item->paid_at?->toDateString(),
            ],
        ]);
    }
}
