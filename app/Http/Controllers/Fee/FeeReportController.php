<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

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

        $query = FeePayment::query()->visibleTo($request->user());

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
        ];

        // Get payment method breakdown
        $byMethod = FeePayment::query()
            ->visibleTo($request->user())
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

        return Inertia::render('Fee/Reports/Collection', [
            'summary' => $summary,
            'byMethod' => $byMethod,
            'dailyCollection' => $dailyCollection,
            'filters' => $request->only(['date_from', 'date_to', 'campus_id']),
        ]);
    }

    /**
     * Display outstanding report.
     */
    public function outstanding(Request $request)
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        $query = FeeVoucher::with(['student', 'voucherMonth'])
            ->visibleTo($request->user())
            ->whereIn('status', ['unpaid', 'partial', 'overdue']);

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Get outstanding summary
        $summary = [
            'total_outstanding' => $query->sum('balance_amount'),
            'voucher_count' => $query->count(),
            'student_count' => $query->distinct('student_id')->count(),
        ];

        // Get outstanding by class
        $byClass = FeeVoucher::query()
            ->visibleTo($request->user())
            ->select(
                'class_id',
                DB::raw('SUM(balance_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->groupBy('class_id')
            ->with('class:id,name')
            ->get();

        // Get top defaulters
        $topDefaulters = FeeVoucher::query()
            ->visibleTo($request->user())
            ->select(
                'student_id',
                DB::raw('SUM(balance_amount) as total_outstanding'),
                DB::raw('COUNT(*) as voucher_count')
            )
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->groupBy('student_id')
            ->with('student:id,name,registration_number')
            ->orderByDesc('total_outstanding')
            ->limit(20)
            ->get();

        return Inertia::render('Fee/Reports/Outstanding', [
            'summary' => $summary,
            'byClass' => $byClass,
            'topDefaulters' => $topDefaulters,
            'filters' => $request->only(['campus_id', 'class_id']),
        ]);
    }

    /**
     * Display defaulters list.
     */
    public function defaulters(Request $request)
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        $query = FeeVoucher::with(['student', 'voucherMonth'])
            ->visibleTo($request->user())
            ->where('status', 'overdue');

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('min_days_overdue')) {
            // `DATEDIFF(NOW(), due_date)` is MySQL-only syntax — SQLite (this
            // suite's test database) has no such function at all. "At least N
            // days overdue" is the same question as "due on or before today
            // minus N days", asked in a way every driver understands.
            $cutoff = now()->subDays((int) $request->min_days_overdue)->toDateString();
            $query->whereDate('due_date', '<=', $cutoff);
        }

        $defaulters = $query->orderBy('due_date')->paginate(50);

        return Inertia::render('Fee/Reports/Defaulters', [
            'defaulters' => $defaulters,
            'filters' => $request->only(['campus_id', 'class_id', 'min_days_overdue']),
        ]);
    }

    /**
     * Display payment method report.
     */
    public function paymentMethods(Request $request)
    {
        Gate::authorize('viewAny', FeePayment::class);

        $query = FeePayment::query()->visibleTo($request->user());

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
