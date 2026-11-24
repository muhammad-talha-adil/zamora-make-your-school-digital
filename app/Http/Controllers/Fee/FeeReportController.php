<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Month;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Fee\StudentBalanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeReportController extends Controller
{
    /**
     * Display reports dashboard.
     */
    public function index()
    {
        Gate::authorize('viewAny', FeePayment::class);

        return Inertia::render('Fee/Reports/Index');
    }

    /**
     * Display collection report.
     */
    public function collection(Request $request)
    {
        Gate::authorize('viewAny', FeePayment::class);

        $query = FeePayment::query()->visibleTo($request->user())->where('status', '!=', PaymentStatus::REVERSED->value);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        // Get collection summary
        $summary = [
            'total_received' => $query->sum('received_amount'),
            'total_allocated' => $query->sum('allocated_amount'),
            // Renamed from `wallet_amount` to `excess_amount` on the table
            // itself — this had summed a column that no longer exists and
            // thrown on every load of this report.
            'total_wallet' => $query->sum('excess_amount'),
            'payment_count' => $query->count(),
            'total_late_fee' => $query->sum('late_fee_amount'),
            'total_concession' => $query->sum('concession_amount'),
            'reversed_total' => FeePayment::query()
                ->visibleTo($request->user())
                ->where('status', PaymentStatus::REVERSED->value)
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date_to))
                ->sum('received_amount'),
        ];

        // Get payment method breakdown
        $byMethod = FeePayment::query()
            ->visibleTo($request->user())
            ->where('status', '!=', PaymentStatus::REVERSED->value)
            ->select('payment_method', DB::raw('SUM(received_amount) as total'))
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('payment_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('payment_date', '<=', $request->date_to);
            })
            ->groupBy('payment_method')
            ->get();

        // Get daily collection
        $dailyCollection = FeePayment::query()
            ->visibleTo($request->user())
            ->where('status', '!=', PaymentStatus::REVERSED->value)
            ->select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(received_amount) as total')
            )
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('payment_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('payment_date', '<=', $request->date_to);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($request->boolean('export')) {
            return $this->csv('collection-report.csv', ['Date', 'Receipt', 'Student', 'Method', 'Received'],
                (clone $query)->with('student')->orderBy('payment_date')->get()
                    ->map(fn (FeePayment $p) => [$p->payment_date->toDateString(), $p->receipt_no, $p->student?->name, $p->payment_method, $p->received_amount])->all());
        }

        return Inertia::render('Fee/Reports/Collection', [
            'summary' => $summary,
            'byMethod' => $byMethod,
            'dailyCollection' => $dailyCollection,
            'filters' => $request->only(['date_from', 'date_to', 'campus_id']),
        ]);
    }

    /**
     * Open vouchers the viewer may see, narrowed by the report filters.
     *
     * @return Builder<FeeVoucher>
     */
    protected function balanceScope(Request $request)
    {
        $query = FeeVoucher::query()->visibleTo($request->user());

        foreach (['campus_id', 'class_id', 'voucher_month_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->input($column));
            }
        }

        return $query;
    }

    /**
     * Per-student balances for the filtered scope, from the one balance service.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function studentBalanceRows(Request $request): Collection
    {
        $balances = app(StudentBalanceService::class)->forStudents($this->balanceScope($request));
        $students = Student::query()
            ->whereIn('id', array_keys($balances))
            ->with(['user:id,name', 'currentEnrollment.class:id,name', 'currentEnrollment.section:id,name'])
            ->get()
            ->keyBy('id');

        return collect($balances)->map(function (array $balance, int $studentId) use ($students) {
            $student = $students->get($studentId);

            return $balance + [
                'id' => $studentId,
                'student_id' => $studentId,
                'student' => $student ? [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_number' => $student->registration_number,
                ] : null,
                'class_name' => trim(($student?->currentEnrollment?->class?->name ?? '').' '.($student?->currentEnrollment?->section?->name ?? '')),
            ];
        })->values();
    }

    /**
     * Display outstanding report.
     */
    public function outstanding(Request $request)
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        $rows = $this->studentBalanceRows($request);

        $summary = [
            'total_outstanding' => round((float) $rows->sum('outstanding'), 2),
            'total_overdue' => round((float) $rows->sum('overdue'), 2),
            'voucher_count' => (int) $rows->sum('voucher_count'),
            'student_count' => $rows->count(),
        ];

        $byClass = $this->balanceScope($request)
            ->select('class_id', 'section_id', DB::raw('SUM(balance_amount) as total'), DB::raw('COUNT(*) as count'))
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->where('balance_amount', '>', 0)
            ->groupBy('class_id', 'section_id')
            ->with(['schoolClass:id,name', 'section:id,name'])
            ->get()
            ->map(fn (FeeVoucher $row) => [
                'class_id' => $row->class_id,
                'section_id' => $row->section_id,
                'total' => $row->total,
                'count' => $row->count,
                'class' => $row->schoolClass
                    ? ['id' => $row->schoolClass->id, 'name' => trim($row->schoolClass->name.($row->section ? ' - '.$row->section->name : ''))]
                    : null,
            ]);

        $topDefaulters = $rows->sortByDesc('outstanding')->take(20)->values()->map(fn (array $row) => [
            'student_id' => $row['student_id'],
            'total_outstanding' => $row['outstanding'],
            'overdue' => $row['overdue'],
            'voucher_count' => $row['voucher_count'],
            'student' => $row['student'],
        ]);

        if ($request->boolean('export')) {
            return $this->csv('outstanding-report.csv', ['Student', 'Reg No', 'Class', 'Outstanding', 'Overdue', 'Not yet due'],
                $rows->map(fn ($r) => [$r['student']['name'] ?? '', $r['student']['registration_number'] ?? '', $r['class_name'], $r['outstanding'], $r['overdue'], $r['not_yet_due']])->all());
        }

        return Inertia::render('Fee/Reports/Outstanding', [
            'summary' => $summary,
            'byClass' => $byClass,
            'topDefaulters' => $topDefaulters,
            'filters' => $request->only(['campus_id', 'class_id', 'voucher_month_id']),
            ...$this->filterOptions(),
        ]);
    }

    /**
     * Display defaulters list: one row per student with an overdue balance.
     * The amount is the overdue part; total outstanding is shown beside it so
     * the numbers reconcile with the ledger and the Outstanding report.
     */
    public function defaulters(Request $request)
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        $minDays = $request->filled('min_days_overdue') ? max(0, (int) $request->min_days_overdue) : 0;

        $rows = $this->studentBalanceRows($request)
            ->filter(fn (array $row) => $row['overdue'] > 0 && $row['days_overdue'] >= max(1, $minDays))
            ->sortByDesc('days_overdue')
            ->values();

        if ($request->boolean('export')) {
            return $this->csv('defaulters-report.csv', ['Student', 'Reg No', 'Class', 'Overdue', 'Total outstanding', 'Overdue since', 'Days overdue'],
                $rows->map(fn ($r) => [$r['student']['name'] ?? '', $r['student']['registration_number'] ?? '', $r['class_name'], $r['overdue'], $r['outstanding'], $r['overdue_since'], $r['days_overdue']])->all());
        }

        $page = max(1, (int) $request->input('page', 1));
        $defaulters = new LengthAwarePaginator($rows->forPage($page, 50)->values(), $rows->count(), 50, $page, ['path' => $request->url()]);

        return Inertia::render('Fee/Reports/Defaulters', [
            'defaulters' => $defaulters,
            'totals' => [
                'overdue' => round((float) $rows->sum('overdue'), 2),
                'outstanding' => round((float) $rows->sum('outstanding'), 2),
            ],
            'filters' => $request->only(['campus_id', 'class_id', 'voucher_month_id', 'min_days_overdue']),
            ...$this->filterOptions(),
        ]);
    }

    /**
     * @return array{classOptions: Collection, monthOptions: Collection}
     */
    protected function filterOptions(): array
    {
        return [
            'classOptions' => SchoolClass::query()->orderBy('name')->get(['id', 'name']),
            'monthOptions' => Month::query()->orderBy('month_number')->get(['id', 'name']),
        ];
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, array<int, mixed>>  $lines
     */
    protected function csv(string $filename, array $header, array $lines): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $lines): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($lines as $line) {
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Display payment method report.
     */
    public function paymentMethods(Request $request)
    {
        Gate::authorize('viewAny', FeePayment::class);

        $query = FeePayment::query()->visibleTo($request->user())->where('status', '!=', PaymentStatus::REVERSED->value);

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $byMethod = $query->select(
            'payment_method',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(received_amount) as total')
        )
            ->groupBy('payment_method')
            ->get();

        return Inertia::render('Fee/Reports/PaymentMethods', [
            'byMethod' => $byMethod,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }
}
