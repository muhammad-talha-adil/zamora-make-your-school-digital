<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatusCode;
use App\Models\Attendance;
use App\Models\Exam\ExamPaper;
use App\Models\Fee\FeePayment;
use App\Models\InventoryStock;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\UnifiedAccountingService;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        protected FinanceService $financeService,
        protected UnifiedAccountingService $accountingService,
    ) {}

    public function home(): Response
    {
        return Inertia::render('Home');
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact');
    }

    /**
     * The landing dashboard: a cross-module summary scoped to whatever the
     * viewer is allowed to see (their own campus, unless they are school-wide).
     */
    public function dashboard(Request $request): Response
    {
        $viewer = $request->user();
        $campusId = $viewer?->campusId();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $examHorizon = now()->addDays(14)->toDateString();

        return Inertia::render('Dashboard', [
            'stats' => [
                'active_students' => Student::query()->visibleTo($viewer)->whereHas('currentEnrollment')->count(),
                'active_staff' => StaffProfile::query()->visibleTo($viewer)->where('is_active', true)->count(),
            ],
            'fee_summary' => $this->feeSummary($campusId, $today, $monthStart, $monthEnd),
            'fee_trend' => $this->feeTrend($campusId),
            'attendance_today' => $this->attendanceToday($viewer, $today),
            'upcoming_exam_papers' => ExamPaper::query()
                ->with(['exam:id,name', 'subject:id,name', 'class:id,name'])
                ->visibleTo($viewer)
                ->whereBetween('paper_date', [$today, $examHorizon])
                ->orderBy('paper_date')
                ->limit(5)
                ->get()
                ->map(fn (ExamPaper $paper) => [
                    'id' => $paper->id,
                    'exam_name' => $paper->exam?->name,
                    'subject_name' => $paper->subject?->name,
                    'class_name' => $paper->class?->name,
                    'paper_date' => $paper->paper_date?->toDateString(),
                ]),
            // Not the `lowStock()` scope: it filters with a bare `HAVING`
            // clause (no `GROUP BY`), which SQLite refuses once `count()`
            // wraps the query in its own aggregate subquery.
            'low_stock_count' => InventoryStock::query()
                ->when($campusId, fn ($query) => $query->where('campus_id', $campusId))
                ->whereHas('inventoryItem', fn ($query) => $query->where('is_active', true))
                ->whereRaw('available_quantity < COALESCE(low_stock_threshold, ?)', [InventoryStock::DEFAULT_LOW_STOCK_THRESHOLD])
                ->count(),
            'recent_activity' => $this->recentActivity($viewer),
        ]);
    }

    public function inventoryIndex(): Response
    {
        return Inertia::render('inventory/Index');
    }

    /**
     * Today's and this month's fee collection, combining `Ledger`'s manual
     * entries with the journal-backed cash movements the same way
     * `FinanceController::index()` does — the dashboard has no business
     * re-deriving figures that already have one source of truth.
     *
     * @return array{today: float, month: float}
     */
    private function feeSummary(?int $campusId, string $today, string $monthStart, string $monthEnd): array
    {
        $todayLedger = $this->financeService->getTodaySummary($campusId);
        $monthLedger = $this->financeService->getMonthSummary($campusId);
        $todayCash = $this->accountingService->cashMovementTotals($today, $today, $campusId);
        $monthCash = $this->accountingService->cashMovementTotals($monthStart, $monthEnd, $campusId);

        return [
            'today' => (float) ($todayLedger['income'] + $todayCash['income']),
            'month' => (float) ($monthLedger['income'] + $monthCash['income']),
        ];
    }

    /**
     * The last 7 days' fee collection, for the small trend chart — cheap
     * enough at one query pair per day that it does not need its own cache.
     *
     * @return array<int, array{date: string, total: float}>
     */
    private function feeTrend(?int $campusId): array
    {
        $days = collect(range(6, 0))->map(fn (int $offset) => now()->subDays($offset)->toDateString());

        return $days->map(function (string $day) use ($campusId) {
            $ledger = $this->financeService->getTotalIncome($day, $day, $campusId);
            $cash = $this->accountingService->cashMovementTotals($day, $day, $campusId);

            return [
                'date' => $day,
                'total' => (float) ($ledger + $cash['income']),
            ];
        })->all();
    }

    /**
     * @return array{present: int, absent: int, has_register: bool}
     */
    private function attendanceToday(?User $viewer, string $today): array
    {
        $registers = Attendance::query()
            ->with(['attendanceStudents.attendanceStatus'])
            ->visibleTo($viewer)
            ->whereDate('attendance_date', $today)
            ->get();

        $present = 0;
        $absent = 0;

        foreach ($registers as $register) {
            $students = $register->attendanceStudents;
            $present += $students->where('attendanceStatus.code', AttendanceStatusCode::PRESENT->value)->count();
            $absent += $students->where('attendanceStatus.code', AttendanceStatusCode::ABSENT->value)->count();
        }

        return [
            'present' => $present,
            'absent' => $absent,
            'has_register' => $registers->isNotEmpty(),
        ];
    }

    /**
     * A lean cross-module feed: the latest fee payments and the latest new
     * admissions, merged and sorted by when they happened. Nothing more than
     * that is worth building until this application has a real activity log.
     *
     * @return array<int, array{description: string, at: string}>
     */
    private function recentActivity(?User $viewer): Collection
    {
        $payments = FeePayment::query()
            ->with('student.user:id,name')
            ->visibleTo($viewer)
            ->latest('payment_date')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (FeePayment $payment) => [
                'description' => 'Fee payment received: '.($payment->student?->user?->name ?? 'Student').' — Rs '.number_format((float) $payment->received_amount),
                'at' => $payment->payment_date?->toDateString() ?? $payment->created_at?->toDateTimeString(),
            ]);

        $admissions = Student::query()
            ->with('user:id,name')
            ->visibleTo($viewer)
            ->whereHas('currentEnrollment')
            ->latest('admission_date')
            ->limit(3)
            ->get()
            ->map(fn (Student $student) => [
                'description' => 'New student enrolled: '.($student->user?->name ?? $student->registration_no),
                'at' => $student->admission_date?->toDateString(),
            ]);

        return $payments->concat($admissions)
            ->sortByDesc('at')
            ->values()
            ->take(6);
    }
}
