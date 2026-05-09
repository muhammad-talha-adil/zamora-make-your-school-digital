<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Finance\StudentAccountCharge;
use App\Models\PayrollRunItem;
use App\Models\TransportVehicleExpense;
use App\Services\Finance\StudentBillingService;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceController extends Controller
{
    protected $financeService;
    protected $studentBillingService;

    public function __construct(FinanceService $financeService, StudentBillingService $studentBillingService)
    {
        $this->financeService = $financeService;
        $this->studentBillingService = $studentBillingService;
    }

    /**
     * Dashboard - Show financial overview
     */
    public function index(Request $request)
    {
        $campusId = $request->filled('campus_id')
            ? $request->campus_id
            : (auth()->user()->campus_id ?? Campus::first()?->id);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $openCharges = StudentAccountCharge::query()
            ->when($campusId, fn ($query) => $query->where('campus_id', $campusId))
            ->whereIn('status', ['open', 'partial'])
            ->get();

        $data = [
            'today_summary' => $this->financeService->getTodaySummary($campusId),
            'month_summary' => $this->financeService->getMonthSummary($campusId),
            'student_receivables' => [
                'total_open' => (float) $openCharges->sum(fn ($charge) => $this->studentBillingService->getChargeBalance($charge)),
                'fee_open' => (float) $openCharges->where('source_module', 'fee')->sum(fn ($charge) => $this->studentBillingService->getChargeBalance($charge)),
                'inventory_open' => (float) $openCharges->where('source_module', 'inventory')->sum(fn ($charge) => $this->studentBillingService->getChargeBalance($charge)),
                'transport_open' => (float) $openCharges->where('source_module', 'transport')->sum(fn ($charge) => $this->studentBillingService->getChargeBalance($charge)),
            ],
            'operations_summary' => [
                'pending_salary_payable' => (float) PayrollRunItem::query()
                    ->where('status', '!=', 'paid')
                    ->whereHas('payrollRun', fn ($query) => $query->when($campusId, fn ($inner, $id) => $inner->where('campus_id', $id)))
                    ->sum('net_salary'),
                'transport_expense_month' => (float) TransportVehicleExpense::query()
                    ->when($campusId, fn ($query) => $query->where('campus_id', $campusId))
                    ->whereBetween('expense_date', [$monthStart, $monthEnd])
                    ->sum('amount'),
            ],
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'selected_campus' => $campusId,
        ];

        return Inertia::render('Finance/Dashboard', $data);
    }
}
