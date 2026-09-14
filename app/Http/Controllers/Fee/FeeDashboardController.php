<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeDashboardController extends Controller
{
    /**
     * Display fee dashboard.
     */
    public function index(Request $request)
    {
        $stats = $this->getStats($request->user());
        $recentPayments = $this->getRecentPayments($request->user());
        $overdueVouchers = $this->getOverdueVouchers($request->user());
        $collectionTrend = $this->getCollectionTrend($request->user());

        return Inertia::render('Fee/Dashboard/Index', [
            'stats' => $stats,
            'recentPayments' => $recentPayments,
            'overdueVouchers' => $overdueVouchers,
            'collectionTrend' => $collectionTrend,
        ]);
    }

    /**
     * Get dashboard stats via API.
     */
    public function stats(Request $request)
    {
        return response()->json([
            'stats' => $this->getStats($request->user()),
            'recentPayments' => $this->getRecentPayments($request->user()),
            'overdueVouchers' => $this->getOverdueVouchers($request->user()),
            'collectionTrend' => $this->getCollectionTrend($request->user()),
        ]);
    }

    /**
     * Vouchers the viewer may see, campus-scoped like every other Fee query.
     *
     * `FeeVoucher::count()` and friends previously ran with no scope at all, so
     * a campus-restricted user's dashboard silently showed every campus's
     * totals rather than their own.
     */
    private function voucherQuery(?User $user): Builder
    {
        return FeeVoucher::query()->visibleTo($user);
    }

    /**
     * Payments the viewer may see, campus-scoped the same way.
     */
    private function paymentQuery(?User $user): Builder
    {
        return FeePayment::query()->visibleTo($user);
    }

    /**
     * Get dashboard statistics.
     *
     * @return array<string, int|float>
     */
    private function getStats(?User $user): array
    {
        return [
            'totalVouchers' => $this->voucherQuery($user)->count(),
            'unpaidVouchers' => $this->voucherQuery($user)->where('status', 'unpaid')->count(),
            'overdueVouchers' => $this->voucherQuery($user)->where('status', 'overdue')->count(),
            'totalCollected' => $this->paymentQuery($user)->where('status', 'posted')->sum('received_amount'),
            'totalOutstanding' => $this->voucherQuery($user)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance_amount'),
            'monthlyCollection' => $this->paymentQuery($user)
                ->where('status', 'posted')
                ->whereDate('payment_date', '>=', now()->startOfMonth()->toDateString())
                ->whereDate('payment_date', '<=', now()->endOfMonth()->toDateString())
                ->sum('received_amount'),
            // Discount stats - managed via student admission, not a separate module
            'activeDiscounts' => 0,
            'pendingApprovals' => 0,
        ];
    }

    /**
     * Get recent payments.
     */
    private function getRecentPayments(?User $user)
    {
        return $this->paymentQuery($user)
            ->with('student:id,name')
            ->select('id', 'receipt_no', 'student_id', 'received_amount', 'payment_date', 'payment_method')
            ->latest('payment_date')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'receipt_no' => $payment->receipt_no,
                    'student_name' => $payment->student->name ?? 'N/A',
                    'amount' => $payment->received_amount,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                ];
            });
    }

    /**
     * Get overdue vouchers.
     */
    private function getOverdueVouchers(?User $user)
    {
        return $this->voucherQuery($user)
            ->with(['student:id,name', 'voucherMonth:id,name'])
            ->where('status', 'overdue')
            ->select('id', 'voucher_no', 'student_id', 'voucher_month_id', 'balance_amount', 'due_date')
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'voucher_no' => $voucher->voucher_no,
                    'student_name' => $voucher->student->name ?? 'N/A',
                    'amount' => $voucher->balance_amount,
                    'due_date' => $voucher->due_date,
                    'days_overdue' => now()->diffInDays($voucher->due_date),
                ];
            });
    }

    /**
     * Daily collection totals for the last 7 days, for the trend chart.
     *
     * @return array<int, array{date: string, amount: float}>
     */
    private function getCollectionTrend(?User $user): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        $totalsByDate = $this->paymentQuery($user)
            ->where('status', 'posted')
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->whereDate('payment_date', '<=', $end->toDateString())
            ->get(['payment_date', 'received_amount'])
            ->groupBy(fn ($payment) => Carbon::parse($payment->payment_date)->toDateString())
            ->map(fn ($payments) => (float) $payments->sum('received_amount'));

        $trend = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $trend[] = [
                'date' => $date,
                'amount' => (float) ($totalsByDate[$date] ?? 0),
            ];
        }

        return $trend;
    }
}
