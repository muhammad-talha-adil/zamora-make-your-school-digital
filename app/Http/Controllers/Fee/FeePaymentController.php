<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Student;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FeePaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        $query = FeePayment::with(['student', 'campus']);

        // Apply campus filter (required for multi-campus security)
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('receipt_no', 'like', '%'.$request->search.'%')
                    ->orWhereHas('student', function ($sq) use ($request) {
                        $sq->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('registration_number', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $payments = $query->latest('payment_date')->paginate(50);

        return Inertia::render('Fee/Payments/Index', [
            'payments' => $payments,
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['campus_id', 'date_from', 'date_to', 'payment_method', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request)
    {
        $studentId = $request->get('student_id');
        $unpaidVouchers = [];

        if ($studentId) {
            $unpaidVouchers = FeeVoucher::where('student_id', $studentId)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->with(['voucherMonth', 'items.feeHead'])
                ->get();
        }

        return Inertia::render('Fee/Payments/Create', [
            'unpaidVouchers' => $unpaidVouchers,
            'studentId' => $studentId,
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,online,jazzcash,easypaisa,cheque',
            'reference_no' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'received_amount' => 'required|numeric|min:0',
            'vouchers' => 'required|array|min:1',
            'vouchers.*.voucher_id' => 'required|exists:fee_vouchers,id',
            'vouchers.*.amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // Get student enrollment to determine campus
            $student = Student::with('currentEnrollment')->findOrFail($validated['student_id']);
            $enrollment = $student->currentEnrollment;

            if (! $enrollment) {
                throw new \Exception('Student has no active enrollment');
            }

            // Generate receipt number with race condition protection
            $receiptNo = $this->generateReceiptNumber($enrollment->campus_id);

            // Calculate totals
            $totalAllocated = collect($validated['vouchers'])->sum('amount');
            $walletAmount = max(0, $validated['received_amount'] - $totalAllocated);

            // Create payment
            $payment = FeePayment::create([
                'receipt_no' => $receiptNo,
                'student_id' => $validated['student_id'],
                'student_enrollment_record_id' => $enrollment->id,
                'campus_id' => $enrollment->campus_id,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'received_amount' => $validated['received_amount'],
                'allocated_amount' => $totalAllocated,
                'excess_amount' => $walletAmount,
                'remaining_unallocated_amount' => $walletAmount,
                'status' => 'posted',
                'notes' => $validated['remarks'] ?? null,
                'received_by' => auth()->id(),
            ]);

            // Create allocations and update vouchers
            foreach ($validated['vouchers'] as $voucherData) {
                $voucher = FeeVoucher::findOrFail($voucherData['voucher_id']);
                $amountToAllocate = $voucherData['amount'];

                // Auto-allocate to previous vouchers if this voucher has previous_voucher_ids
                $previousVoucherIds = $voucher->previous_voucher_ids ?? [];

                if (! empty($previousVoucherIds) && is_array($previousVoucherIds)) {
                    // Get previous vouchers that still have balance
                    $previousVouchers = FeeVoucher::whereIn('id', $previousVoucherIds)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->get();

                    foreach ($previousVouchers as $previousVoucher) {
                        if ($amountToAllocate <= 0) {
                            break;
                        }

                        $previousBalance = $previousVoucher->balance_amount;
                        $allocationToPrevious = min($amountToAllocate, $previousBalance);

                        if ($allocationToPrevious > 0) {
                            // Create allocation for previous voucher
                            $payment->allocations()->create([
                                'fee_voucher_id' => $previousVoucher->id,
                                'allocated_amount' => $allocationToPrevious,
                                'allocation_date' => $validated['payment_date'],
                            ]);

                            // Update previous voucher
                            $previousVoucher->paid_amount += $allocationToPrevious;
                            $previousVoucher->balance_amount = $previousVoucher->net_amount - $previousVoucher->paid_amount;

                            if ($previousVoucher->balance_amount <= 0) {
                                $previousVoucher->status = 'paid';
                            } elseif ($previousVoucher->paid_amount > 0) {
                                $previousVoucher->status = 'partial';
                            }

                            $previousVoucher->save();

                            // Reduce amount available for current voucher
                            $amountToAllocate -= $allocationToPrevious;
                        }
                    }
                }

                // Create allocation for current voucher (remaining amount)
                if ($amountToAllocate > 0) {
                    $payment->allocations()->create([
                        'fee_voucher_id' => $voucher->id,
                        'allocated_amount' => $amountToAllocate,
                        'allocation_date' => $validated['payment_date'],
                    ]);

                    // Update current voucher
                    $voucher->paid_amount += $amountToAllocate;
                    $voucher->balance_amount = $voucher->net_amount - $voucher->paid_amount;

                    if ($voucher->balance_amount <= 0) {
                        $voucher->status = 'paid';
                    } elseif ($voucher->paid_amount > 0) {
                        $voucher->status = 'partial';
                    }

                    $voucher->save();
                }
            }

            // Create Finance Ledger Entry (Income from tuition fee)
            $this->createFinanceLedgerEntry($payment, $validated['received_amount'], $enrollment->campus_id);

            return redirect()->route('fee.payments.show', $payment->id)
                ->with('success', 'Payment recorded successfully.');
        });
    }

    /**
     * Create a finance ledger entry for the payment.
     */
    protected function createFinanceLedgerEntry(FeePayment $payment, float $amount, int $campusId): void
    {
        try {
            $financeService = new FinanceService;

            // Get student for description
            $student = $payment->student;

            // Map payment method to finance payment method
            $paymentMethodMap = [
                'cash' => 'Cash',
                'bank' => 'Bank Transfer',
                'online' => 'Online',
                'jazzcash' => 'JazzCash',
                'easypaisa' => 'EasyPaisa',
                'cheque' => 'Cheque',
            ];

            $financeService->createIncomeTransaction([
                'transaction_date' => $payment->payment_date,
                'amount' => $amount,
                'description' => 'Fee Payment - Receipt: '.$payment->receipt_no.' - Student: '.($student ? $student->name : 'N/A'),
                'reference_type' => FeePayment::class,
                'reference_id' => $payment->id,
                'category_id' => 1, // Tuition Fee (ID 1 from ledger_categories)
                'payment_method' => $paymentMethodMap[$payment->payment_method] ?? $payment->payment_method,
                'reference_number' => $payment->reference_no,
                'campus_id' => $campusId,
                'student_id' => $payment->student_id,
                'created_by' => $payment->received_by,
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the payment
            Log::error('Failed to create finance ledger entry: '.$e->getMessage());
        }
    }

    /**
     * Generate unique receipt number with race condition protection.
     */
    protected function generateReceiptNumber(int $campusId): string
    {
        return DB::transaction(function () use ($campusId) {
            $today = now()->format('Ymd');

            $lastPayment = FeePayment::where('receipt_no', 'like', "RCP-{$campusId}-{$today}%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $nextNumber = $lastPayment ? ((int) substr($lastPayment->receipt_no, -4)) + 1 : 1;

            return 'RCP-'.$campusId.'-'.$today.'-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Display the specified payment.
     */
    public function show(FeePayment $payment)
    {
        $payment->load([
            'student',
            'allocations.voucher.voucherMonth',
            'receivedBy',
        ]);

        return Inertia::render('Fee/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Display printable receipt.
     */
    public function receipt(FeePayment $payment)
    {
        $payment->load([
            'student',
            'allocations.voucher.voucherMonth',
        ]);

        return Inertia::render('Fee/Payments/Receipt', [
            'payment' => $payment,
        ]);
    }
}
