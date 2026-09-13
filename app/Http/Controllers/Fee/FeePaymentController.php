<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\PaymentStatus;
use App\Enums\Fee\WalletDirection;
use App\Enums\Fee\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\ReverseFeePaymentRequest;
use App\Http\Requests\Fee\StoreFeePaymentRequest;
use App\Models\Campus;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeePaymentAllocation;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\StudentFeeWalletTransaction;
use App\Models\Finance\StudentAccountCharge;
use App\Models\Ledger\Ledger;
use App\Models\Student;
use App\Services\Fee\FeePaymentService;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FeePaymentController extends Controller
{
    public function __construct(
        protected StudentBillingService $studentBillingService,
        protected UnifiedAccountingService $accountingService,
        protected FeePaymentService $feePaymentService
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', FeePayment::class);

        $query = FeePayment::query()->visibleTo($request->user())->with(['student', 'campus']);

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
        Gate::authorize('create', FeePayment::class);

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
        Gate::authorize('create', FeePayment::class);

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
    public function store(StoreFeePaymentRequest $request)
    {
        Gate::authorize('create', FeePayment::class);

        $payment = $this->feePaymentService->record($request->validated());

        return redirect()->route('fee.payments.show', $payment->id)
            ->with('success', 'Payment recorded successfully.');
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
        Gate::authorize('view', $payment);

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
        Gate::authorize('view', $payment);

        $payment->load([
            'student',
            'allocations.voucher.voucherMonth',
        ]);

        return Inertia::render('Fee/Payments/Receipt', [
            'payment' => $payment,
        ]);
    }

    /**
     * The same receipt screen — `Fee/Payments/Receipt.vue` already renders it
     * print-ready, the way the voucher screens use one Vue page for both
     * viewing and printing rather than a separate Blade template.
     */
    public function printReceipt(FeePayment $payment)
    {
        return $this->receipt($payment);
    }

    /**
     * Removes a payment that was recorded in error and never allocated.
     *
     * A posted receipt with money actually settled against a voucher is
     * `reverse()`'s job, not this one's — undoing that needs the allocations
     * and the accounting entries unwound in step, which deleting a row does
     * not do.
     */
    public function destroy(FeePayment $payment)
    {
        Gate::authorize('reverse', $payment);

        if ((float) $payment->allocated_amount > 0 || $payment->status === PaymentStatus::POSTED) {
            return back()->withErrors([
                'error' => 'This payment has been posted and allocated. Reverse it instead of deleting it.',
            ]);
        }

        $payment->delete();

        return redirect()->route('fee.payments.index')->with('success', 'Payment deleted successfully.');
    }

    /**
     * Undoes a posted payment: the allocations it made, the wallet advance it
     * left behind, and both accounting entries it posted.
     */
    public function reverse(ReverseFeePaymentRequest $request, FeePayment $payment)
    {
        Gate::authorize('reverse', $payment);

        if ($payment->status !== PaymentStatus::POSTED) {
            return back()->withErrors(['error' => 'Only a posted payment can be reversed.']);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($payment, $validated) {
            $affectedVoucherIds = [];
            $affectedChargeIds = [];

            foreach ($payment->allocations as $allocation) {
                $affectedVoucherIds[$allocation->fee_voucher_id] = $allocation->fee_voucher_id;

                if ($allocation->student_account_charge_id) {
                    $affectedChargeIds[$allocation->student_account_charge_id] = $allocation->student_account_charge_id;
                }
            }

            FeePaymentAllocation::where('fee_payment_id', $payment->id)->delete();

            foreach ($affectedChargeIds as $chargeId) {
                $charge = StudentAccountCharge::find($chargeId);
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

            // The advance this payment left in the wallet is reversed with an
            // offsetting debit, not deleted — the wallet is a ledger, and a
            // deposit that never happened should still say so.
            if ((float) $payment->excess_amount > 0) {
                StudentFeeWalletTransaction::create([
                    'student_id' => $payment->student_id,
                    'transaction_date' => now()->toDateString(),
                    'transaction_type' => WalletTransactionType::MANUAL_DEBIT,
                    'direction' => WalletDirection::DEBIT,
                    'amount' => $payment->excess_amount,
                    'reference_type' => FeePayment::class,
                    'reference_id' => $payment->id,
                    'description' => 'Reversal of advance from receipt '.$payment->receipt_no,
                    'created_by' => auth()->id(),
                ]);
            }

            $this->accountingService->voidPaymentJournal($payment);

            // A payment recorded before this fix may still carry a legacy
            // `Ledger` row (fee payments no longer write one at all — see
            // `UnifiedAccountingService::cashMovementTotals()`); clean it up
            // if so. Best-effort, the same way that write itself used to be.
            try {
                Ledger::where('reference_type', FeePayment::class)
                    ->where('reference_id', $payment->id)
                    ->delete();
            } catch (\Exception $e) {
                Log::error('Failed to void legacy ledger entry on reversal: '.$e->getMessage());
            }

            $payment->update([
                'status' => PaymentStatus::REVERSED,
                'allocated_amount' => 0,
                'excess_amount' => 0,
                'remaining_unallocated_amount' => 0,
                'notes' => trim(($payment->notes ? $payment->notes.' ' : '').
                    '[Reversed: '.($validated['reason'] ?? 'no reason given').']'),
            ]);
        });

        return back()->with('success', 'Payment reversed successfully.');
    }

    /**
     * Get payments recorded for a student.
     */
    /**
     * `{student}` is bound straight off the URL — this used to read a
     * `student_id` from the query string instead and ignore it, so the
     * endpoint 422'd on every call unless the caller redundantly repeated the
     * id as a query param too.
     */
    public function getByStudent(Request $request, Student $student)
    {
        Gate::authorize('viewByStudent', [FeePayment::class, $student->id]);

        $payments = FeePayment::query()
            ->visibleTo($request->user())
            ->where('student_id', $student->id)
            ->with('allocations')
            ->orderByDesc('payment_date')
            ->get();

        return response()->json($payments);
    }

    /**
     * Get payments allocated against a voucher.
     */
    public function getByVoucher(Request $request, FeeVoucher $voucher)
    {
        Gate::authorize('viewAny', FeePayment::class);

        $paymentIds = FeePaymentAllocation::where('fee_voucher_id', $voucher->id)
            ->pluck('fee_payment_id')
            ->unique();

        $payments = FeePayment::query()
            ->visibleTo($request->user())
            ->whereIn('id', $paymentIds)
            ->with(['allocations' => fn ($q) => $q->where('fee_voucher_id', $voucher->id)])
            ->orderByDesc('payment_date')
            ->get();

        return response()->json($payments);
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
