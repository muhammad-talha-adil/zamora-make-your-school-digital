<?php

namespace App\Services\Finance;

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\StudentAccountCharge;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\StudentInventory;
use App\Models\StudentInventoryReturn;
use App\Models\TransportVehicleExpense;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnifiedAccountingService
{
    public function postVoucherJournal(FeeVoucher $voucher): ?JournalEntry
    {
        $voucher->loadMissing(['items.studentAccountCharge']);

        $charges = $voucher->items
            ->pluck('studentAccountCharge')
            ->filter()
            ->values();

        if ($charges->isEmpty()) {
            return null;
        }

        return $this->replaceSourceJournal('fee', 'fee_voucher', $voucher->id, function () use ($voucher, $charges) {
            $totalReceivable = (float) $charges->sum('net_amount');

            if ($totalReceivable <= 0) {
                return null;
            }

            $entry = $this->createEntry([
                'entry_date' => $voucher->issue_date ?? now(),
                'source_module' => 'fee',
                'source_type' => 'fee_voucher',
                'source_id' => $voucher->id,
                'campus_id' => $voucher->campus_id,
                'student_id' => $voucher->student_id,
                'description' => 'Fee voucher generated: '.$voucher->voucher_no,
                'meta' => [
                    'voucher_no' => $voucher->voucher_no,
                    'charge_ids' => $charges->pluck('id')->values()->all(),
                    'receivable_amount' => $totalReceivable,
                ],
                'created_by' => $voucher->generated_by,
            ]);

            $this->createLine($entry, '1100', $totalReceivable, 0, 'Student receivable raised for voucher '.$voucher->voucher_no, $voucher->campus_id, $voucher->student_id);

            $credits = $charges->groupBy(fn ($charge) => $this->resolveIncomeAccountCode(
                $charge->source_module,
                $charge->charge_category,
                $charge->source_type
            ));

            foreach ($credits as $accountCode => $groupedCharges) {
                $amount = (float) $groupedCharges->sum('net_amount');

                if ($amount <= 0) {
                    continue;
                }

                $this->createLine(
                    $entry,
                    $accountCode,
                    0,
                    $amount,
                    'Income recognized from voucher '.$voucher->voucher_no,
                    $voucher->campus_id,
                    $voucher->student_id
                );
            }

            return $entry;
        });
    }

    public function postPaymentJournal(FeePayment $payment): ?JournalEntry
    {
        $accountCode = $this->resolveCashAccountCode($payment->payment_method?->value ?? (string) $payment->payment_method);
        $receivedAmount = (float) $payment->received_amount;
        $allocatedAmount = (float) $payment->allocated_amount;
        $excessAmount = (float) $payment->excess_amount;

        if ($receivedAmount <= 0) {
            return null;
        }

        return $this->replaceSourceJournal('fee', 'fee_payment', $payment->id, function () use ($payment, $accountCode, $receivedAmount, $allocatedAmount, $excessAmount) {
            $entry = $this->createEntry([
                'entry_date' => $payment->payment_date ?? now(),
                'source_module' => 'fee',
                'source_type' => 'fee_payment',
                'source_id' => $payment->id,
                'campus_id' => $payment->campus_id,
                'student_id' => $payment->student_id,
                'description' => 'Fee payment received: '.$payment->receipt_no,
                'meta' => [
                    'receipt_no' => $payment->receipt_no,
                    'payment_method' => $payment->payment_method?->value ?? (string) $payment->payment_method,
                    'received_amount' => $receivedAmount,
                    'allocated_amount' => $allocatedAmount,
                    'excess_amount' => $excessAmount,
                ],
                'created_by' => $payment->received_by,
            ]);

            $this->createLine($entry, $accountCode, $receivedAmount, 0, 'Payment received via '.$this->formatPaymentMethod($payment), $payment->campus_id, $payment->student_id);

            if ($allocatedAmount > 0) {
                $this->createLine($entry, '1100', 0, $allocatedAmount, 'Settlement of student receivable for receipt '.$payment->receipt_no, $payment->campus_id, $payment->student_id);
            }

            if ($excessAmount > 0) {
                $this->createLine($entry, '2200', 0, $excessAmount, 'Advance kept in student wallet from receipt '.$payment->receipt_no, $payment->campus_id, $payment->student_id);
            }

            return $entry;
        });
    }

    public function postInventoryAssignmentJournal(StudentInventory $record): ?JournalEntry
    {
        $record->loadMissing(['student', 'items']);

        $receivableAmount = (float) $record->final_amount;
        $cogsAmount = (float) $record->items->sum(fn ($item) => $item->getCostOfGoodsSold());

        if ($receivableAmount <= 0 && $cogsAmount <= 0) {
            return null;
        }

        return $this->replaceSourceJournal('inventory', 'student_inventory_assignment', $record->id, function () use ($record, $receivableAmount, $cogsAmount) {
            $entry = $this->createEntry([
                'entry_date' => $record->assigned_date ?? now(),
                'source_module' => 'inventory',
                'source_type' => 'student_inventory_assignment',
                'source_id' => $record->id,
                'campus_id' => $record->campus_id,
                'student_id' => $record->student_id,
                'description' => 'Inventory assigned to student: '.$record->student_inventory_id,
                'meta' => [
                    'student_inventory_id' => $record->student_inventory_id,
                    'item_count' => $record->items->count(),
                    'receivable_amount' => $receivableAmount,
                    'cogs_amount' => $cogsAmount,
                ],
                'created_by' => auth()->id(),
            ]);

            if ($receivableAmount > 0) {
                $this->createLine($entry, '1100', $receivableAmount, 0, 'Student receivable raised for inventory assignment', $record->campus_id, $record->student_id);
                $this->createLine($entry, '4020', 0, $receivableAmount, 'Inventory sales income for student assignment', $record->campus_id, $record->student_id);
            }

            if ($cogsAmount > 0) {
                $this->createLine($entry, '5030', $cogsAmount, 0, 'Cost of goods sold for inventory assignment', $record->campus_id, $record->student_id);
                $this->createLine($entry, '1200', 0, $cogsAmount, 'Inventory asset reduction for assignment', $record->campus_id, $record->student_id);
            }

            return $entry;
        });
    }

    public function postInventoryReturnJournal(StudentInventoryReturn $returnRecord, StudentInventory $record, float $creditAmount, float $returnedCost): ?JournalEntry
    {
        if ($creditAmount <= 0 && $returnedCost <= 0) {
            return null;
        }

        return $this->replaceSourceJournal('inventory', 'student_inventory_return', $returnRecord->id, function () use ($returnRecord, $record, $creditAmount, $returnedCost) {
            $entry = $this->createEntry([
                'entry_date' => $returnRecord->return_date ?? now(),
                'source_module' => 'inventory',
                'source_type' => 'student_inventory_return',
                'source_id' => $returnRecord->id,
                'campus_id' => $returnRecord->campus_id,
                'student_id' => $returnRecord->student_id,
                'description' => 'Inventory return processed: '.$returnRecord->return_id,
                'meta' => [
                    'return_id' => $returnRecord->return_id,
                    'student_inventory_id' => $record->student_inventory_id,
                    'credit_amount' => $creditAmount,
                    'returned_cost' => $returnedCost,
                ],
                'created_by' => auth()->id(),
            ]);

            if ($creditAmount > 0) {
                $this->createLine($entry, '4020', $creditAmount, 0, 'Inventory sales reversal for returned items', $returnRecord->campus_id, $returnRecord->student_id);
                $this->createLine($entry, '1100', 0, $creditAmount, 'Reduction of student receivable from inventory return', $returnRecord->campus_id, $returnRecord->student_id);
            }

            if ($returnedCost > 0) {
                $this->createLine($entry, '1200', $returnedCost, 0, 'Inventory asset restored from return', $returnRecord->campus_id, $returnRecord->student_id);
                $this->createLine($entry, '5030', 0, $returnedCost, 'COGS reversal for returned inventory', $returnRecord->campus_id, $returnRecord->student_id);
            }

            return $entry;
        });
    }

    public function postTransportChargeJournal(StudentAccountCharge $charge): ?JournalEntry
    {
        if ($charge->source_module !== 'transport' || (float) $charge->net_amount <= 0) {
            return null;
        }

        return $this->replaceSourceJournal('transport', 'transport_charge', $charge->id, function () use ($charge) {
            $entry = $this->createEntry([
                'entry_date' => $charge->charge_date ?? now(),
                'source_module' => 'transport',
                'source_type' => 'transport_charge',
                'source_id' => $charge->id,
                'campus_id' => $charge->campus_id,
                'student_id' => $charge->student_id,
                'description' => $charge->title,
                'meta' => [
                    'charge_id' => $charge->id,
                    'net_amount' => (float) $charge->net_amount,
                ],
                'created_by' => $charge->created_by,
            ]);

            $this->createLine($entry, '1100', (float) $charge->net_amount, 0, 'Transport due billed to student', $charge->campus_id, $charge->student_id);
            $this->createLine($entry, '4030', 0, (float) $charge->net_amount, 'Transport income recognized', $charge->campus_id, $charge->student_id);

            return $entry;
        });
    }

    public function postPayrollAccrualJournal(PayrollRun $payrollRun): ?JournalEntry
    {
        if ((float) $payrollRun->total_net <= 0) {
            return null;
        }

        return $this->replaceSourceJournal('staff', 'payroll_run', $payrollRun->id, function () use ($payrollRun) {
            $entry = $this->createEntry([
                'entry_date' => now(),
                'source_module' => 'staff',
                'source_type' => 'payroll_run',
                'source_id' => $payrollRun->id,
                'campus_id' => $payrollRun->campus_id,
                'description' => 'Payroll accrued: '.$payrollRun->title,
                'meta' => [
                    'payroll_run_id' => $payrollRun->id,
                    'total_net' => (float) $payrollRun->total_net,
                ],
                'created_by' => $payrollRun->created_by,
            ]);

            $this->createLine($entry, '5000', (float) $payrollRun->total_net, 0, 'Salary expense accrued', $payrollRun->campus_id);
            $this->createLine($entry, '2100', 0, (float) $payrollRun->total_net, 'Salary payable created', $payrollRun->campus_id);

            return $entry;
        });
    }

    public function postPayrollPaymentJournal(PayrollRunItem $payrollItem): ?JournalEntry
    {
        if ((float) $payrollItem->net_salary <= 0) {
            return null;
        }

        $payrollItem->loadMissing(['payrollRun', 'staffProfile.user']);

        return $this->replaceSourceJournal('staff', 'payroll_payment', $payrollItem->id, function () use ($payrollItem) {
            $accountCode = $this->resolveCashAccountCode((string) ($payrollItem->payment_method ?: 'bank'));
            $campusId = $payrollItem->payrollRun?->campus_id ?? $payrollItem->staffProfile?->campus_id;

            $entry = $this->createEntry([
                'entry_date' => $payrollItem->paid_at ?? now(),
                'source_module' => 'staff',
                'source_type' => 'payroll_payment',
                'source_id' => $payrollItem->id,
                'campus_id' => $campusId,
                'description' => 'Salary paid to '.$payrollItem->staffProfile?->user?->name,
                'meta' => [
                    'payroll_run_item_id' => $payrollItem->id,
                    'net_salary' => (float) $payrollItem->net_salary,
                ],
                'created_by' => auth()->id(),
            ]);

            $this->createLine($entry, '2100', (float) $payrollItem->net_salary, 0, 'Salary payable cleared', $campusId);
            $this->createLine($entry, $accountCode, 0, (float) $payrollItem->net_salary, 'Salary payment disbursed', $campusId);

            return $entry;
        });
    }

    public function postTransportExpenseJournal(TransportVehicleExpense $expense): ?JournalEntry
    {
        if ((float) $expense->amount <= 0) {
            return null;
        }

        $accountCode = match ($expense->expense_type) {
            'maintenance', 'repair' => '5020',
            default => '5010',
        };

        return $this->replaceSourceJournal('transport', 'vehicle_expense', $expense->id, function () use ($expense, $accountCode) {
            $cashAccount = $this->resolveCashAccountCode((string) $expense->payment_method);

            $entry = $this->createEntry([
                'entry_date' => $expense->expense_date ?? now(),
                'source_module' => 'transport',
                'source_type' => 'vehicle_expense',
                'source_id' => $expense->id,
                'campus_id' => $expense->campus_id,
                'description' => 'Transport expense: '.$expense->expense_type,
                'meta' => [
                    'transport_vehicle_id' => $expense->transport_vehicle_id,
                    'amount' => (float) $expense->amount,
                    'expense_type' => $expense->expense_type,
                ],
                'created_by' => $expense->created_by,
            ]);

            $this->createLine($entry, $accountCode, (float) $expense->amount, 0, 'Transport expense recognized', $expense->campus_id);
            $this->createLine($entry, $cashAccount, 0, (float) $expense->amount, 'Transport expense paid', $expense->campus_id);

            return $entry;
        });
    }

    protected function replaceSourceJournal(string $sourceModule, string $sourceType, int $sourceId, callable $callback): ?JournalEntry
    {
        $this->deleteSourceJournal($sourceModule, $sourceType, $sourceId);

        return $callback();
    }

    protected function deleteSourceJournal(string $sourceModule, string $sourceType, int $sourceId): void
    {
        $entries = JournalEntry::with('lines')
            ->where('source_module', $sourceModule)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();

        foreach ($entries as $entry) {
            $entry->lines()->delete();
            $entry->forceDelete();
        }
    }

    protected function createEntry(array $attributes): JournalEntry
    {
        return JournalEntry::create([
            'entry_no' => $this->generateEntryNo(),
            'entry_date' => $attributes['entry_date'],
            'source_module' => $attributes['source_module'] ?? null,
            'source_type' => $attributes['source_type'] ?? null,
            'source_id' => $attributes['source_id'] ?? null,
            'campus_id' => $attributes['campus_id'] ?? null,
            'student_id' => $attributes['student_id'] ?? null,
            'status' => 'posted',
            'description' => $attributes['description'] ?? null,
            'meta' => $attributes['meta'] ?? null,
            'created_by' => $attributes['created_by'] ?? auth()->id(),
            'approved_by' => $attributes['created_by'] ?? auth()->id(),
        ]);
    }

    protected function createLine(
        JournalEntry $entry,
        string $accountCode,
        float $debit,
        float $credit,
        string $memo,
        ?int $campusId = null,
        ?int $studentId = null
    ): void {
        $account = $this->findAccount($accountCode);

        $entry->lines()->create([
            'account_id' => $account->id,
            'campus_id' => $campusId,
            'student_id' => $studentId,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'memo' => $memo,
        ]);
    }

    protected function findAccount(string $code): ChartOfAccount
    {
        return ChartOfAccount::where('code', $code)->firstOrFail();
    }

    protected function resolveIncomeAccountCode(?string $sourceModule, ?string $chargeCategory, ?string $sourceType): string
    {
        if ($sourceModule === 'inventory' || $chargeCategory === 'inventory') {
            return '4020';
        }

        if ($sourceModule === 'transport' || $chargeCategory === 'transport') {
            return '4030';
        }

        if ($chargeCategory === 'fine' || str_contains((string) $sourceType, 'fine')) {
            return '4010';
        }

        return '4000';
    }

    protected function resolveCashAccountCode(string $paymentMethod): string
    {
        return $paymentMethod === 'cash' ? '1000' : '1010';
    }

    protected function formatPaymentMethod(FeePayment $payment): string
    {
        $method = $payment->payment_method?->value ?? (string) $payment->payment_method;

        return str_replace('_', ' ', ucfirst($method));
    }

    protected function generateEntryNo(): string
    {
        return DB::transaction(function () {
            $datePrefix = now()->format('Ymd');

            $lastEntry = JournalEntry::where('entry_no', 'like', "JE-{$datePrefix}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextNumber = 1;

            if ($lastEntry && preg_match('/JE-\d{8}-(\d{4})$/', $lastEntry->entry_no, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return 'JE-'.$datePrefix.'-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}
