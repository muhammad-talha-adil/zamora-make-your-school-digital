<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Finance\StudentAccountCharge;
use App\Models\PayrollRunItem;
use App\Models\TransportVehicleExpense;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceController extends Controller
{
    protected $financeService;

    protected $studentBillingService;

    protected $accountingService;

    public function __construct(
        FinanceService $financeService,
        StudentBillingService $studentBillingService,
        UnifiedAccountingService $accountingService
    ) {
        $this->financeService = $financeService;
        $this->studentBillingService = $studentBillingService;
        $this->accountingService = $accountingService;
    }

    /**
     * Dashboard - Show financial overview
     */
    public function index(Request $request)
    {
        // The `finance.view` permission is checked by route middleware — this
        // dashboard has no per-record owner to check beyond the campus filter
        // already applied to each query below.

        // `campus_id` is not a real column on `User` — the accessor every
        // other module reads is `campusId()`, through the staff profile. The
        // bare attribute read here always returned null, so a campus-limited
        // viewer's dashboard silently defaulted to whichever campus sorted
        // first rather than their own.
        $campusId = $request->filled('campus_id')
            ? $request->campus_id
            : (auth()->user()?->campusId() ?? Campus::first()?->id);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $openCharges = StudentAccountCharge::query()
            ->when($campusId, fn ($query) => $query->where('campus_id', $campusId))
            ->whereIn('status', ['open', 'partial'])
            ->get();

        // Fee payments (and every other module's cash movement) stopped
        // writing a `Ledger` row once they had a proper double-entry journal
        // to write instead — so the dashboard's cash figures are `Ledger`'s
        // manual entries plus the journals', not `Ledger` alone, or a fee
        // payment recorded today would not show up in "today's income" at all.
        $todayCash = $this->accountingService->cashMovementTotals($today, $today, $campusId);
        $monthCash = $this->accountingService->cashMovementTotals($monthStart, $monthEnd, $campusId);
        $todayLedger = $this->financeService->getTodaySummary($campusId);
        $monthLedger = $this->financeService->getMonthSummary($campusId);

        $data = [
            'today_summary' => [
                'income' => $todayLedger['income'] + $todayCash['income'],
                'expense' => $todayLedger['expense'] + $todayCash['expense'],
                'balance' => ($todayLedger['income'] + $todayCash['income']) - ($todayLedger['expense'] + $todayCash['expense']),
            ],
            'month_summary' => [
                'income' => $monthLedger['income'] + $monthCash['income'],
                'expense' => $monthLedger['expense'] + $monthCash['expense'],
                'balance' => ($monthLedger['income'] + $monthCash['income']) - ($monthLedger['expense'] + $monthCash['expense']),
            ],
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
                    // Two `whereDate` calls, not `whereBetween`: a date-cast
                    // column compared with a plain Y-m-d string range has
                    // silently failed to match on SQLite before.
                    ->whereDate('expense_date', '>=', $monthStart)
                    ->whereDate('expense_date', '<=', $monthEnd)
                    ->sum('amount'),
            ],
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'selected_campus' => $campusId,
            'weekly_trend' => $this->getWeeklyTrend($campusId),
        ];

        return Inertia::render('Finance/Dashboard', $data);
    }

    /**
     * Daily income/expense totals for the last 7 days, for the dashboard's
     * trend chart. Combines the same two sources as the headline figures
     * above (the `Ledger` journal plus the cash-movement journal entries), one
     * day at a time, so the chart and the "today"/"this month" cards can never
     * disagree about what counts as income or expense.
     *
     * @return array<int, array{date: string, income: float, expense: float}>
     */
    private function getWeeklyTrend(?int $campusId): array
    {
        $trend = [];

        for ($day = now()->subDays(6)->startOfDay(); $day->lte(now()->endOfDay()); $day->addDay()) {
            $date = $day->toDateString();

            $cash = $this->accountingService->cashMovementTotals($date, $date, $campusId);
            $ledgerIncome = $this->financeService->getTotalIncome($date, $date, $campusId);
            $ledgerExpense = $this->financeService->getTotalExpense($date, $date, $campusId);

            $trend[] = [
                'date' => $date,
                'income' => $ledgerIncome + $cash['income'],
                'expense' => $ledgerExpense + $cash['expense'],
            ];
        }

        return $trend;
    }
}
