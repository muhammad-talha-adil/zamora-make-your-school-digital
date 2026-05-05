<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use Inertia\Inertia;

class FeeDashboardController extends Controller
{
    /**
     * Display fee dashboard.
     */
    public function index()
    {
        $stats = $this->getStats();
        $recentPayments = $this->getRecentPayments();
        $overdueVouchers = $this->getOverdueVouchers();

        return Inertia::render('Fee/Dashboard/Index', [
            'stats' => $stats,
            'recentPayments' => $recentPayments,
            'overdueVouchers' => $overdueVouchers,
        ]);
    }

    /**
     * Get dashboard stats via API.
     */
    public function stats()
    {
        return response()->json([
            'stats' => $this->getStats(),
            'recentPayments' => $this->getRecentPayments(),
            'overdueVouchers' => $this->getOverdueVouchers(),
        ]);
    }

    /**
     * Get dashboard statistics.
     */
    private function getStats()
    {
        return [
            'totalVouchers' => FeeVoucher::count(),
            'unpaidVouchers' => FeeVoucher::where('status', 'unpaid')->count(),
            'overdueVouchers' => FeeVoucher::where('status', 'overdue')->count(),
            'totalCollected' => FeePayment::where('status', 'completed')->sum('received_amount'),
            'totalOutstanding' => FeeVoucher::whereIn('status', ['unpaid', 'partial', 'overdue'])->sum('balance_amount'),
            'monthlyCollection' => FeePayment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('received_amount'),
            // Discount stats - managed via student admission, not a separate module
            'activeDiscounts' => 0,
            'pendingApprovals' => 0,
        ];
    }

    /**
     * Get recent payments.
     */
    private function getRecentPayments()
    {
        return FeePayment::with('student:id,name')
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
    private function getOverdueVouchers()
    {
        return FeeVoucher::with(['student:id,name', 'voucherMonth:id,name'])
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
}
