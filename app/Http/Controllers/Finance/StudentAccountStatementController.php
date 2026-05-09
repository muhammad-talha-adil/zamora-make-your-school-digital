<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeePayment;
use App\Models\Fee\StudentFeeWalletTransaction;
use App\Models\Finance\StudentAccountAdjustment;
use App\Models\Finance\StudentAccountCharge;
use App\Models\Student;
use App\Services\Finance\StudentBillingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentAccountStatementController extends Controller
{
    public function __construct(
        protected StudentBillingService $studentBillingService
    ) {}

    public function index(Request $request)
    {
        $student = null;
        $statement = null;

        if ($request->filled('student_id')) {
            $student = Student::with([
                'user',
                'currentEnrollment.class',
                'currentEnrollment.section',
                'currentEnrollment.campus',
                'currentEnrollment.session',
            ])->findOrFail($request->integer('student_id'));

            $statement = $this->buildStatement($student);
        }

        return Inertia::render('Finance/StudentAccountStatement', [
            'filters' => $request->only(['student_id']),
            'student' => $student ? [
                'id' => $student->id,
                'name' => $student->name,
                'registration_no' => $student->registration_no,
                'admission_no' => $student->admission_no,
                'campus' => $student->currentEnrollment?->campus?->name,
                'session' => $student->currentEnrollment?->session?->name,
                'class' => $student->currentEnrollment?->class?->name,
                'section' => $student->currentEnrollment?->section?->name,
                'wallet_balance' => (float) $student->wallet_balance,
            ] : null,
            'statement' => $statement,
        ]);
    }

    public function searchStudents(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $students = Student::query()
            ->with(['user', 'currentEnrollment.class', 'currentEnrollment.section'])
            ->where(function ($builder) use ($query) {
                $builder->where('registration_no', 'like', '%'.$query.'%')
                    ->orWhere('admission_no', 'like', '%'.$query.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$query.'%'));
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (Student $student) {
                $className = $student->currentEnrollment?->class?->name;
                $sectionName = $student->currentEnrollment?->section?->name;

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_no' => $student->registration_no,
                    'admission_no' => $student->admission_no,
                    'display_text' => trim(collect([
                        $student->name,
                        $student->registration_no,
                        $className ? $className.($sectionName ? ' / '.$sectionName : '') : null,
                    ])->filter()->implode(' | ')),
                ];
            });

        return response()->json($students);
    }

    protected function buildStatement(Student $student): array
    {
        $charges = StudentAccountCharge::with(['billingMonth', 'voucher', 'schoolClass', 'section'])
            ->where('student_id', $student->id)
            ->orderByDesc('charge_date')
            ->orderByDesc('id')
            ->get();

        $adjustments = StudentAccountAdjustment::with(['charge', 'voucher'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $payments = FeePayment::with([
            'allocations.voucher',
            'allocations.voucherItem.feeHead',
            'allocations.studentAccountCharge',
            'receivedBy',
        ])
            ->where('student_id', $student->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $walletTransactions = StudentFeeWalletTransaction::with('creator')
            ->where('student_id', $student->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $chargeRows = $charges->map(function (StudentAccountCharge $charge) {
            $paidAmount = $this->studentBillingService->getChargeAllocatedAmount($charge);
            $balanceAmount = $this->studentBillingService->getChargeBalance($charge);

            return [
                'id' => $charge->id,
                'module' => $charge->source_module,
                'title' => $charge->title,
                'description' => $charge->description,
                'charge_date' => optional($charge->charge_date)->format('Y-m-d'),
                'due_date' => optional($charge->due_date)->format('Y-m-d'),
                'period' => $charge->billingMonth?->name && $charge->billing_period_year
                    ? $charge->billingMonth->name.' '.$charge->billing_period_year
                    : null,
                'class_name' => $charge->schoolClass?->name,
                'section_name' => $charge->section?->name,
                'voucher_no' => $charge->voucher?->voucher_no,
                'amount' => (float) $charge->amount,
                'discount_amount' => (float) $charge->discount_amount,
                'fine_amount' => (float) $charge->fine_amount,
                'net_amount' => (float) $charge->net_amount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'status' => $charge->status,
            ];
        })->values();

        $adjustmentRows = $adjustments->map(function (StudentAccountAdjustment $adjustment) {
            return [
                'id' => $adjustment->id,
                'module' => $adjustment->source_module,
                'type' => $adjustment->adjustment_type,
                'amount' => (float) $adjustment->amount,
                'reason' => $adjustment->reason,
                'date' => optional($adjustment->created_at)->format('Y-m-d'),
                'voucher_no' => $adjustment->voucher?->voucher_no,
                'charge_title' => $adjustment->charge?->title,
            ];
        })->values();

        $paymentRows = $payments->map(function (FeePayment $payment) {
            $allocations = $payment->allocations->map(function ($allocation) {
                return [
                    'id' => $allocation->id,
                    'module' => $allocation->source_module ?: $allocation->studentAccountCharge?->source_module ?: 'fee',
                    'voucher_no' => $allocation->voucher?->voucher_no,
                    'charge_title' => $allocation->studentAccountCharge?->title
                        ?: $allocation->voucherItem?->feeHead?->name
                        ?: $allocation->voucherItem?->description,
                    'amount' => (float) $allocation->allocated_amount,
                ];
            })->values();

            return [
                'id' => $payment->id,
                'receipt_no' => $payment->receipt_no,
                'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                'payment_method' => $payment->payment_method?->value ?? (string) $payment->payment_method,
                'received_amount' => (float) $payment->received_amount,
                'allocated_amount' => (float) $payment->allocated_amount,
                'excess_amount' => (float) $payment->excess_amount,
                'status' => $payment->status?->value ?? (string) $payment->status,
                'received_by' => $payment->receivedBy?->name,
                'allocations' => $allocations,
            ];
        })->values();

        $walletRows = $walletTransactions->map(function (StudentFeeWalletTransaction $transaction) {
            return [
                'id' => $transaction->id,
                'date' => optional($transaction->transaction_date)->format('Y-m-d'),
                'type' => $transaction->transaction_type?->value ?? (string) $transaction->transaction_type,
                'direction' => $transaction->direction?->value ?? (string) $transaction->direction,
                'amount' => (float) $transaction->amount,
                'description' => $transaction->description,
            ];
        })->values();

        $timeline = collect()
            ->concat($chargeRows->map(fn (array $row) => [
                'date' => $row['charge_date'],
                'entry_type' => 'charge',
                'module' => $row['module'],
                'title' => $row['title'],
                'reference' => $row['voucher_no'],
                'debit' => $row['net_amount'],
                'credit' => 0,
                'status' => $row['status'],
            ]))
            ->concat($adjustmentRows->map(fn (array $row) => [
                'date' => $row['date'],
                'entry_type' => 'adjustment',
                'module' => $row['module'],
                'title' => $row['reason'],
                'reference' => $row['voucher_no'],
                'debit' => 0,
                'credit' => $row['amount'],
                'status' => $row['type'],
            ]))
            ->concat($paymentRows->map(fn (array $row) => [
                'date' => $row['payment_date'],
                'entry_type' => 'payment',
                'module' => 'fee',
                'title' => 'Payment received: '.$row['receipt_no'],
                'reference' => $row['receipt_no'],
                'debit' => 0,
                'credit' => $row['allocated_amount'],
                'status' => $row['status'],
            ]))
            ->concat($walletRows->map(fn (array $row) => [
                'date' => $row['date'],
                'entry_type' => 'wallet',
                'module' => 'wallet',
                'title' => $row['description'] ?: 'Wallet transaction',
                'reference' => strtoupper($row['direction']),
                'debit' => $row['direction'] === 'debit' ? $row['amount'] : 0,
                'credit' => $row['direction'] === 'credit' ? $row['amount'] : 0,
                'status' => $row['type'],
            ]))
            ->sortByDesc(fn (array $row) => $row['date'] ?? '0000-00-00')
            ->values();

        $openCharges = $charges->filter(fn (StudentAccountCharge $charge) => in_array($charge->status, ['open', 'partial'], true));

        return [
            'summary' => [
                'total_billed' => (float) $charges->sum('net_amount'),
                'total_paid' => (float) $payments->sum('allocated_amount'),
                'total_adjustments' => (float) $adjustments->sum('amount'),
                'total_outstanding' => (float) $openCharges->sum(fn (StudentAccountCharge $charge) => $this->studentBillingService->getChargeBalance($charge)),
                'fee_outstanding' => (float) $openCharges->where('source_module', 'fee')->sum(fn (StudentAccountCharge $charge) => $this->studentBillingService->getChargeBalance($charge)),
                'inventory_outstanding' => (float) $openCharges->where('source_module', 'inventory')->sum(fn (StudentAccountCharge $charge) => $this->studentBillingService->getChargeBalance($charge)),
                'transport_outstanding' => (float) $openCharges->where('source_module', 'transport')->sum(fn (StudentAccountCharge $charge) => $this->studentBillingService->getChargeBalance($charge)),
                'wallet_balance' => (float) $student->wallet_balance,
            ],
            'charges' => $chargeRows,
            'adjustments' => $adjustmentRows,
            'payments' => $paymentRows,
            'wallet_transactions' => $walletRows,
            'timeline' => $timeline,
        ];
    }
}
