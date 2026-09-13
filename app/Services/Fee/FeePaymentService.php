<?php

namespace App\Services\Fee;

use App\Enums\Fee\WalletDirection;
use App\Enums\Fee\WalletTransactionType;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\StudentFeeWalletTransaction;
use App\Models\Finance\StudentAccountCharge;
use App\Models\Student;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recording a fee payment — the one place it happens.
 *
 * This used to live only in `FeePaymentController::store()`. Finance's
 * "Receive Payment" screen (`ReceivePaymentController`) needed the same
 * thing for its own "student" payment type and had grown its own, much
 * smaller version instead: no `FeePayment`, no `FeePaymentAllocation`, no
 * `StudentAccountCharge` sync, no accounting journal — it just added the
 * amount straight onto `fee_vouchers.paid_amount`. A payment recorded that
 * way was invisible to the Fee module's own payment list, produced no
 * receipt, and could not be reversed the way every other payment can.
 *
 * Both controllers now call this instead.
 */
class FeePaymentService
{
    public function __construct(
        private StudentBillingService $studentBillingService,
        private UnifiedAccountingService $accountingService
    ) {}

    /**
     * @param  array{
     *     student_id: int,
     *     payment_date: string,
     *     payment_method: string,
     *     received_amount: float,
     *     reference_no?: string|null,
     *     bank_name?: string|null,
     *     remarks?: string|null,
     *     charges: array<int, array{voucher_id: int, fee_voucher_item_id: int, student_account_charge_id?: int|null, source_module?: string|null, amount: float}>,
     * }  $data
     *
     * @throws ValidationException
     */
    public function record(array $data): FeePayment
    {
        $voucherIds = collect($data['charges'])->pluck('voucher_id')->unique()->values();

        $vouchers = FeeVoucher::query()
            ->whereIn('id', $voucherIds)
            ->where('student_id', $data['student_id'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get()
            ->keyBy('id');

        if ($vouchers->count() !== $voucherIds->count()) {
            throw ValidationException::withMessages([
                'vouchers' => 'One or more selected vouchers do not belong to this student or are no longer unpaid.',
            ]);
        }

        $chargeIds = collect($data['charges'])->pluck('student_account_charge_id')->filter()->unique()->values();

        $charges = StudentAccountCharge::query()
            ->whereIn('id', $chargeIds)
            ->where('student_id', $data['student_id'])
            ->get()
            ->keyBy('id');

        foreach ($data['charges'] as $index => $chargeData) {
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

        $totalAllocated = (float) collect($data['charges'])->sum('amount');
        if ($totalAllocated > (float) $data['received_amount']) {
            throw ValidationException::withMessages([
                'charges' => 'Allocated due amount cannot be greater than the received amount.',
            ]);
        }

        return DB::transaction(function () use ($data, $totalAllocated) {
            $student = Student::with('currentEnrollment')->findOrFail($data['student_id']);
            $enrollment = $student->currentEnrollment;

            if (! $enrollment) {
                throw new \Exception('Student has no active enrollment');
            }

            $receiptNo = $this->generateReceiptNumber($enrollment->campus_id);
            $walletAmount = max(0, $data['received_amount'] - $totalAllocated);

            $payment = FeePayment::create([
                'receipt_no' => $receiptNo,
                'student_id' => $data['student_id'],
                'student_enrollment_record_id' => $enrollment->id,
                'campus_id' => $enrollment->campus_id,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['payment_method'] === 'cash' ? null : ($data['reference_no'] ?? null),
                'bank_name' => $data['bank_name'] ?? null,
                'received_amount' => $data['received_amount'],
                'allocated_amount' => $totalAllocated,
                'excess_amount' => $walletAmount,
                'remaining_unallocated_amount' => $walletAmount,
                'status' => 'posted',
                'notes' => $data['remarks'] ?? null,
                'received_by' => auth()->id(),
            ]);

            $affectedVoucherIds = [];
            $affectedChargeIds = [];

            foreach ($data['charges'] as $chargeData) {
                $voucher = FeeVoucher::findOrFail($chargeData['voucher_id']);
                $item = $voucher->items()->findOrFail($chargeData['fee_voucher_item_id']);
                $chargeId = $chargeData['student_account_charge_id'] ?? $item->student_account_charge_id;

                $payment->allocations()->create([
                    'fee_voucher_id' => $voucher->id,
                    'fee_voucher_item_id' => $item->id,
                    'student_account_charge_id' => $chargeId,
                    'source_module' => $chargeData['source_module'] ?? $item->source_module,
                    'allocated_amount' => $chargeData['amount'],
                    'allocation_date' => $data['payment_date'],
                    'notes' => $item->description,
                ]);

                $affectedVoucherIds[$voucher->id] = $voucher->id;
                if ($chargeId) {
                    $affectedChargeIds[$chargeId] = $chargeId;
                }
            }

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

            $this->accountingService->postPaymentJournal($payment);

            if ($walletAmount > 0) {
                StudentFeeWalletTransaction::create([
                    'student_id' => $data['student_id'],
                    'transaction_date' => $data['payment_date'],
                    'transaction_type' => WalletTransactionType::ADVANCE_DEPOSIT,
                    'direction' => WalletDirection::CREDIT,
                    'amount' => $walletAmount,
                    'reference_type' => FeePayment::class,
                    'reference_id' => $payment->id,
                    'description' => 'Excess amount kept as advance from receipt '.$payment->receipt_no,
                    'created_by' => auth()->id(),
                ]);
            }

            return $payment;
        });
    }

    /**
     * Spreads a lump sum across a voucher's unpaid items, oldest item first —
     * what "Receive Payment" needs when a clerk enters one amount against a
     * voucher rather than picking items one at a time the way the Fee
     * module's own payment screen does.
     *
     * @return array<int, array{voucher_id: int, fee_voucher_item_id: int, student_account_charge_id: int|null, amount: float}>
     */
    public function allocateAcrossVoucherItems(FeeVoucher $voucher, float $amount): array
    {
        $charges = [];
        $remaining = $amount;

        foreach ($voucher->items()->with('studentAccountCharge')->orderBy('id')->get() as $item) {
            if ($remaining <= 0) {
                break;
            }

            $balance = $this->studentBillingService->getVoucherItemBalance($item);

            if ($balance <= 0) {
                continue;
            }

            $take = round(min($balance, $remaining), 2);

            $charges[] = [
                'voucher_id' => $voucher->id,
                'fee_voucher_item_id' => $item->id,
                'student_account_charge_id' => $item->student_account_charge_id,
                'amount' => $take,
            ];

            $remaining -= $take;
        }

        return $charges;
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

            return 'RCP-'.$campusId.'-'.$today.'-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}
