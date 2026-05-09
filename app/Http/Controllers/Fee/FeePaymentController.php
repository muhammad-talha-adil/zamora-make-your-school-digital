<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\WalletDirection;
use App\Enums\Fee\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Fee\FeePayment;
use App\Models\Fee\StudentFeeWalletTransaction;
use App\Models\Fee\FeeVoucher;
use App\Models\Student;
use App\Services\FinanceService;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FeePaymentController extends Controller
{
    public function __construct(
        protected StudentBillingService $studentBillingService,
        protected UnifiedAccountingService $accountingService
    ) {}

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
        $selectedStudent = null;

        if ($studentId) {
            $selectedStudent = Student::with('user:id,name')->find($studentId);
            $unpaidVouchers = $this->getUnpaidVouchersForStudent((int) $studentId);
        }

        return Inertia::render('Fee/Payments/Create', [
            'unpaidVouchers' => $unpaidVouchers,
            'studentId' => $studentId,
            'selectedStudent' => $selectedStudent ? [
                'id' => $selectedStudent->id,
                'name' => $selectedStudent->name,
                'registration_number' => $selectedStudent->registration_number,
            ] : null,
        ]);
    }

    /**
     * Search students for payment by student fields or voucher number.
     */
    public function searchStudents(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $students = Student::query()
            ->with('user:id,name')
            ->where(function ($query) use ($search) {
                $query->where('registration_no', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('feeVouchers', function ($voucherQuery) use ($search) {
                        $voucherQuery->where('voucher_no', 'like', "%{$search}%");
                    });
            })
            ->limit(15)
            ->get(['id', 'registration_no', 'admission_no'])
            ->map(function (Student $student) use ($search) {
                $matchedVoucher = $student->feeVouchers()
                    ->where('voucher_no', 'like', "%{$search}%")
                    ->orderByDesc('id')
                    ->value('voucher_no');

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_number' => $student->registration_number,
                    'admission_no' => $student->admission_no,
                    'matched_voucher_no' => $matchedVoucher,
                ];
            })
            ->values();

        return response()->json($students);
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
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf($request->input('payment_method') !== 'cash'),
                Rule::prohibitedIf($request->input('payment_method') === 'cash'),
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(in_array($request->input('payment_method'), ['bank', 'cheque'], true)),
            ],
            'received_amount' => 'required|numeric|min:0',
            'charges' => 'required|array|min:1',
            'charges.*.voucher_id' => 'required|exists:fee_vouchers,id',
            'charges.*.fee_voucher_item_id' => 'required|exists:fee_voucher_items,id',
            'charges.*.student_account_charge_id' => 'nullable|exists:student_account_charges,id',
            'charges.*.source_module' => 'nullable|string|max:50',
            'charges.*.amount' => 'required|numeric|gt:0',
            'remarks' => 'nullable|string',
        ]);

        $voucherIds = collect($validated['charges'])
            ->pluck('voucher_id')
            ->unique()
            ->values();

        $vouchers = FeeVoucher::query()
            ->whereIn('id', $voucherIds)
            ->where('student_id', $validated['student_id'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get()
            ->keyBy('id');

        if ($vouchers->count() !== $voucherIds->count()) {
            throw ValidationException::withMessages([
                'vouchers' => 'One or more selected vouchers do not belong to this student or are no longer unpaid.',
            ]);
        }

        $chargeIds = collect($validated['charges'])
            ->pluck('student_account_charge_id')
            ->filter()
            ->unique()
            ->values();

        $charges = \App\Models\Finance\StudentAccountCharge::query()
            ->whereIn('id', $chargeIds)
            ->where('student_id', $validated['student_id'])
            ->get()
            ->keyBy('id');

        foreach ($validated['charges'] as $index => $chargeData) {
            $voucher = $vouchers->get($chargeData['voucher_id']);
            if (! $voucher) {
                continue;
            }

            $item = $voucher->items()->whereKey($chargeData['fee_voucher_item_id'])->first();
            if (! $item) {
                throw ValidationException::withMessages([
                    "charges.{$index}.fee_voucher_item_id" => 'Selected due item does not belong to the selected voucher.',
                ]);
            }

            $charge = null;
            if (! empty($chargeData['student_account_charge_id'])) {
                $charge = $charges->get($chargeData['student_account_charge_id']);

                if (! $charge || $item->student_account_charge_id !== $charge->id) {
                    throw ValidationException::withMessages([
                        "charges.{$index}.student_account_charge_id" => 'Selected due charge does not match the voucher item.',
                    ]);
                }
            }

            $maxAllocatable = $this->studentBillingService->getVoucherItemBalance($item->loadMissing('studentAccountCharge'));

            if ((float) $chargeData['amount'] > $maxAllocatable) {
                throw ValidationException::withMessages([
                    "charges.{$index}.amount" => 'Allocated amount cannot exceed the remaining balance of the selected due item.',
                ]);
            }
        }

        $totalAllocated = (float) collect($validated['charges'])->sum('amount');
        if ($totalAllocated > (float) $validated['received_amount']) {
            throw ValidationException::withMessages([
                'charges' => 'Allocated due amount cannot be greater than the received amount.',
            ]);
        }

        /* Legacy voucher-wide validation remains below only for voucher existence. */
        foreach ($validated['charges'] as $index => $voucherData) {
            $voucher = $vouchers->get($voucherData['voucher_id']);

            if (! $voucher) {
                continue;
            }
        }

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
                'reference_no' => $validated['payment_method'] === 'cash' ? null : ($validated['reference_no'] ?? null),
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
            $affectedVoucherIds = [];
            $affectedChargeIds = [];

            foreach ($validated['charges'] as $chargeData) {
                $voucher = FeeVoucher::findOrFail($chargeData['voucher_id']);
                $item = $voucher->items()->findOrFail($chargeData['fee_voucher_item_id']);
                $chargeId = $chargeData['student_account_charge_id'] ?? $item->student_account_charge_id;

                $payment->allocations()->create([
                    'fee_voucher_id' => $voucher->id,
                    'fee_voucher_item_id' => $item->id,
                    'student_account_charge_id' => $chargeId,
                    'source_module' => $chargeData['source_module'] ?? $item->source_module,
                    'allocated_amount' => $chargeData['amount'],
                    'allocation_date' => $validated['payment_date'],
                    'notes' => $item->description,
                ]);

                $affectedVoucherIds[$voucher->id] = $voucher->id;
                if ($chargeId) {
                    $affectedChargeIds[$chargeId] = $chargeId;
                }
            }

            foreach ($affectedChargeIds as $chargeId) {
                $charge = \App\Models\Finance\StudentAccountCharge::find($chargeId);
                if ($charge) {
                    $this->studentBillingService->syncChargeSettlement($charge);
                }
            }

            foreach ($affectedVoucherIds as $voucherId) {
                $voucher = FeeVoucher::find($voucherId);
                if ($voucher) {
                    $this->studentBillingService->syncVoucherSettlement($voucher);
                }
            }

            // Keep the legacy ledger view aligned with the new accounting rule:
            // only the allocated portion is recognized as current-period income.
            if ($totalAllocated > 0) {
                $this->createFinanceLedgerEntry($payment, $totalAllocated, $enrollment->campus_id);
            }
            $this->accountingService->postPaymentJournal($payment);

            if ($walletAmount > 0) {
                StudentFeeWalletTransaction::create([
                    'student_id' => $validated['student_id'],
                    'transaction_date' => $validated['payment_date'],
                    'transaction_type' => WalletTransactionType::ADVANCE_DEPOSIT,
                    'direction' => WalletDirection::CREDIT,
                    'amount' => $walletAmount,
                    'reference_type' => FeePayment::class,
                    'reference_id' => $payment->id,
                    'description' => 'Excess amount kept as advance from receipt '.$payment->receipt_no,
                    'created_by' => auth()->id(),
                ]);
            }

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

    protected function getUnpaidVouchersForStudent(int $studentId)
    {
        return FeeVoucher::query()
            ->where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->with(['voucherMonth', 'items.feeHead'])
            ->orderBy('voucher_year')
            ->orderBy('voucher_month_id')
            ->get();
    }
}
