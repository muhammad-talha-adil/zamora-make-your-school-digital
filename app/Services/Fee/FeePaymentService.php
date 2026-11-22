<?php

namespace App\Services\Fee;

use App\Enums\Fee\VoucherItemSource;
use App\Enums\Fee\WalletDirection;
use App\Enums\Fee\WalletTransactionType;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\StudentFeeWalletTransaction;
use App\Models\Finance\StudentAccountCharge;
use App\Models\Student;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Support\Collection;
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
        return DB::transaction(fn () => $this->recordWithLock($data));
    }

    /**
     * Runs inside one transaction with the vouchers locked, so two submits
     * (a double click, or two tabs) are handled one after the other and the
     * second sees the balance the first left behind.
     *
     * @param  array<string, mixed>  $data
     */
    protected function recordWithLock(array $data): FeePayment
    {
        $voucherIds = collect($data['charges'])->pluck('voucher_id')->unique()->values();

        $vouchers = FeeVoucher::query()
            ->whereIn('id', $voucherIds)
            ->where('student_id', $data['student_id'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($vouchers->count() !== $voucherIds->count()) {
            throw ValidationException::withMessages([
                'vouchers' => 'One or more selected vouchers do not belong to this student or are no longer unpaid.',
            ]);
        }

        $this->rejectDuplicate($data);

        $concessionBreakdown = $this->applyConcession($data, $vouchers);
        $data['charges'] = collect($data['charges'])
            ->filter(fn (array $charge) => (float) $charge['amount'] > 0)
            ->values()
            ->all();

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

        $lateFee = round((float) ($data['late_fee'] ?? 0), 2);
        $lateFeeItem = $lateFee > 0 ? $this->addLateFee($vouchers->first(), $lateFee) : null;

        if ($lateFeeItem) {
            $data['charges'][] = [
                'voucher_id' => $lateFeeItem->fee_voucher_id,
                'fee_voucher_item_id' => $lateFeeItem->id,
                'student_account_charge_id' => null,
                'source_module' => 'fee',
                'amount' => $lateFee,
            ];
        }

        if ($data['charges'] === []) {
            throw ValidationException::withMessages([
                'charges' => 'Enter an amount to pay.',
            ]);
        }

        $totalAllocated = (float) collect($data['charges'])->sum('amount');
        if ($totalAllocated > (float) $data['received_amount']) {
            throw ValidationException::withMessages([
                'charges' => 'Allocated due amount cannot be greater than the received amount.',
            ]);
        }

        /*
         * Overpayment used to fall through to a wallet "advance" credit for
         * whatever was left over. Issue #121: for now this is disallowed
         * outright — the office should not be able to submit more than the
         * outstanding due being paid against. If an advance-deposit feature
         * is wanted later, it needs its own explicit flow rather than being
         * an accidental side effect of typing a bigger number here.
         */
        if ((float) $data['received_amount'] > $totalAllocated) {
            throw ValidationException::withMessages([
                'received_amount' => 'Received amount cannot exceed the total due amount being paid ('.number_format($totalAllocated, 2).').',
            ]);
        }

        return DB::transaction(function () use ($data, $totalAllocated, $lateFee, $lateFeeItem, $concessionBreakdown) {
            $student = Student::with(['currentEnrollment.class', 'currentEnrollment.section'])->findOrFail($data['student_id']);
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
                // Frozen at the moment of payment — a later name correction
                // or class change must never rewrite a receipt already
                // handed to a parent.
                'student_name_snapshot' => $student->name,
                'registration_no_snapshot' => $student->registration_no,
                'class_name_snapshot' => $enrollment->class?->name,
                'section_name_snapshot' => $enrollment->section?->name,
                'campus_id' => $enrollment->campus_id,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['payment_method'] === 'cash' ? null : ($data['reference_no'] ?? null),
                'bank_name' => $data['bank_name'] ?? null,
                'received_amount' => $data['received_amount'],
                'allocated_amount' => $totalAllocated,
                'excess_amount' => $walletAmount,
                'late_fee_amount' => $lateFee,
                'concession_amount' => round((float) collect($concessionBreakdown)->sum('amount'), 2),
                'concession_reason' => $concessionBreakdown === [] ? null : ($data['concession_reason'] ?? null),
                'concession_breakdown' => $concessionBreakdown === [] ? null : $concessionBreakdown,
                'late_fee_item_id' => $lateFeeItem?->id,
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
     * A second identical submit a moment after the first (double click) is
     * refused instead of recorded again.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function rejectDuplicate(array $data): void
    {
        $recent = FeePayment::query()
            ->where('student_id', $data['student_id'])
            ->where('payment_method', $data['payment_method'])
            ->where('received_amount', $data['received_amount'])
            ->where('status', 'posted')
            ->where('created_at', '>=', now()->subSeconds(5))
            ->exists();

        if ($recent) {
            throw ValidationException::withMessages([
                'charges' => 'This payment was just recorded. Please check the payments list.',
            ]);
        }
    }

    /**
     * Takes the concession off the selected items, oldest first, and returns
     * what was taken from each so a reversal can put it back.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<int, FeeVoucher>  $vouchers
     * @return array<int, array{item_id: int, amount: float}>
     *
     * @throws ValidationException
     */
    protected function applyConcession(array &$data, $vouchers): array
    {
        $concession = round((float) ($data['concession'] ?? 0), 2);

        if ($concession <= 0) {
            return [];
        }

        if (blank($data['concession_reason'] ?? null)) {
            throw ValidationException::withMessages(['concession_reason' => 'Give a reason for the concession.']);
        }

        $items = [];
        foreach ($data['charges'] as $chargeData) {
            $item = FeeVoucherItem::query()
                ->with('studentAccountCharge')
                ->where('fee_voucher_id', $chargeData['voucher_id'])
                ->find($chargeData['fee_voucher_item_id']);

            if ($item) {
                $items[$item->id] = $item;
            }
        }

        $totalBalance = collect($items)->sum(fn (FeeVoucherItem $item) => $this->studentBillingService->getVoucherItemBalance($item));

        if ($concession > round($totalBalance, 2)) {
            throw ValidationException::withMessages(['concession' => 'Concession cannot be more than the balance (Rs '.number_format($totalBalance, 2).').']);
        }

        $remaining = $concession;
        $breakdown = [];

        foreach ($items as $item) {
            if ($remaining <= 0) {
                break;
            }

            $take = round(min($remaining, $this->studentBillingService->getVoucherItemBalance($item)), 2);

            if ($take <= 0) {
                continue;
            }

            $this->shiftItemDiscount($item, $take);
            $breakdown[] = ['item_id' => $item->id, 'amount' => $take];
            $remaining = round($remaining - $take, 2);
        }

        return $breakdown;
    }

    /**
     * Moves an item's discount by `$amount` (negative puts it back), keeping
     * its charge and its voucher in step.
     */
    public function shiftItemDiscount(FeeVoucherItem $item, float $amount): void
    {
        $item->update([
            'discount_amount' => (float) $item->discount_amount + $amount,
            'net_amount' => (float) $item->net_amount - $amount,
        ]);

        $charge = $item->studentAccountCharge;
        if ($charge) {
            $charge->update([
                'discount_amount' => (float) $charge->discount_amount + $amount,
                'net_amount' => (float) $charge->net_amount - $amount,
            ]);
        }

        $voucher = FeeVoucher::query()->lockForUpdate()->find($item->fee_voucher_id);
        $voucher->update([
            'discount_amount' => (float) $voucher->discount_amount + $amount,
            'net_amount' => (float) $voucher->net_amount - $amount,
        ]);
    }

    /**
     * Adds a late fee line to the voucher and its fine total.
     */
    protected function addLateFee(FeeVoucher $voucher, float $amount): FeeVoucherItem
    {
        $item = $voucher->items()->create([
            'fee_head_id' => FeeHead::query()->where('code', 'LATE_FINE')->value('id'),
            'description' => 'Late fee',
            'amount' => $amount,
            'discount_amount' => 0,
            'fine_amount' => 0,
            'net_amount' => $amount,
            'source_type' => VoucherItemSource::MANUAL,
            'source_module' => 'fee',
        ]);

        $voucher->refresh()->update([
            'fine_amount' => (float) $voucher->fine_amount + $amount,
            'net_amount' => (float) $voucher->net_amount + $amount,
        ]);

        return $item;
    }

    /**
     * Takes the late fee and concession of a payment off the vouchers again.
     */
    public function undoLateFeeAndConcession(FeePayment $payment): void
    {
        foreach ($payment->concession_breakdown ?? [] as $row) {
            $item = FeeVoucherItem::query()->with('studentAccountCharge')->find($row['item_id']);

            if ($item) {
                $this->shiftItemDiscount($item, -1 * (float) $row['amount']);
            }
        }

        $item = $payment->late_fee_item_id ? FeeVoucherItem::query()->find($payment->late_fee_item_id) : null;

        if ($item) {
            $voucher = FeeVoucher::query()->find($item->fee_voucher_id);
            $amount = (float) $item->net_amount;
            $voucher->update([
                'fine_amount' => (float) $voucher->fine_amount - $amount,
                'net_amount' => (float) $voucher->net_amount - $amount,
            ]);
            $item->delete();
        }
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
