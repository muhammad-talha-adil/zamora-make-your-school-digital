<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FeeReportController extends Controller
{
    /**
     * Display reports dashboard.
     */
    public function index()
    {
        return Inertia::render('Fee/Reports/Index');
    }

    /**
     * Display collection report.
     */
    public function collection(Request $request)
    {
        $query = FeePayment::query();

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('student.enrollments', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        // Get collection summary
        $summary = [
            'total_received' => $query->sum('received_amount'),
            'total_allocated' => $query->sum('allocated_amount'),
            'total_wallet' => $query->sum('wallet_amount'),
            'payment_count' => $query->count(),
        ];

        // Get payment method breakdown
        $byMethod = FeePayment::select('payment_method', DB::raw('SUM(received_amount) as total'))
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('payment_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('payment_date', '<=', $request->date_to);
            })
            ->groupBy('payment_method')
            ->get();

        // Get daily collection
        $dailyCollection = FeePayment::select(
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
        $query = FeeVoucher::with(['student', 'voucherMonth'])
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
        $byClass = FeeVoucher::select(
            'class_id',
            DB::raw('SUM(balance_amount) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->groupBy('class_id')
            ->with('class:id,name')
            ->get();

        // Get top defaulters
        $topDefaulters = FeeVoucher::select(
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
        $query = FeeVoucher::with(['student', 'voucherMonth'])
            ->where('status', 'overdue');

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('min_days_overdue')) {
            $query->whereRaw('DATEDIFF(NOW(), due_date) >= ?', [$request->min_days_overdue]);
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
        $query = FeePayment::query();

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
