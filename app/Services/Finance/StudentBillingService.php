<?php

namespace App\Services\Finance;

use App\Models\Fee\FeePaymentAllocation;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Finance\StudentAccountAdjustment;
use App\Models\Finance\StudentAccountCharge;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentInventory;
use App\Models\StudentInventoryReturn;
use App\Models\TransportStudentAssignment;
use Illuminate\Support\Collection;

class StudentBillingService
{
    public function createVoucherCharge(
        FeeVoucher $voucher,
        ?StudentEnrollmentRecord $enrollment,
        array $attributes
    ): StudentAccountCharge {
        $charge = StudentAccountCharge::create([
            'student_id' => $voucher->student_id,
            'student_enrollment_record_id' => $enrollment?->id,
            'campus_id' => $voucher->campus_id,
            'session_id' => $voucher->session_id,
            'class_id' => $voucher->class_id,
            'section_id' => $voucher->section_id,
            'source_module' => $attributes['source_module'] ?? 'fee',
            'source_type' => $attributes['source_type'],
            'source_id' => $attributes['source_id'] ?? null,
            'source_item_id' => $attributes['source_item_id'] ?? null,
            'charge_category' => $attributes['charge_category'] ?? 'fee',
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'charge_date' => $voucher->issue_date ?? now()->toDateString(),
            'billing_period_month_id' => $voucher->voucher_month_id,
            'billing_period_year' => $voucher->voucher_year,
            'due_date' => $voucher->due_date,
            'amount' => $attributes['amount'],
            'discount_amount' => $attributes['discount_amount'] ?? 0,
            'fine_amount' => $attributes['fine_amount'] ?? 0,
            'net_amount' => $attributes['net_amount'],
            'status' => 'open',
            'billing_status' => 'unbilled',
            'is_recurring' => $attributes['is_recurring'] ?? false,
            'meta' => $attributes['meta'] ?? null,
            'created_by' => $attributes['created_by'] ?? auth()->id(),
            'approved_by' => $attributes['approved_by'] ?? auth()->id(),
        ]);

        return $charge;
    }

    public function linkChargeToVoucherItem(StudentAccountCharge $charge, FeeVoucherItem $item): void
    {
        $charge->update([
            'voucher_id' => $item->fee_voucher_id,
            'voucher_item_id' => $item->id,
            'billing_status' => 'billed',
        ]);
    }

    public function createInventoryCharge(StudentInventory $record): StudentAccountCharge
    {
        $record->loadMissing(['items', 'student']);

        $charge = StudentAccountCharge::create([
            'student_id' => $record->student_id,
            'campus_id' => $record->campus_id,
            'class_id' => $record->student_class_id,
            'section_id' => $record->student_section_id,
            'source_module' => 'inventory',
            'source_type' => 'student_inventory_assignment',
            'source_id' => $record->id,
            'charge_category' => 'inventory',
            'title' => 'Inventory Due - '.$record->student_inventory_id,
            'description' => $record->note ?: 'Student inventory assignment billed outside recurring fee structure.',
            'charge_date' => $record->assigned_date ?? now()->toDateString(),
            'amount' => $record->total_amount,
            'discount_amount' => $record->total_discount,
            'fine_amount' => 0,
            'net_amount' => $record->final_amount,
            'status' => 'open',
            'billing_status' => 'unbilled',
            'is_recurring' => false,
            'meta' => [
                'student_inventory_id' => $record->student_inventory_id,
                'item_ids' => $record->items->pluck('id')->values()->all(),
            ],
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]);

        $record->update([
            'student_account_charge_id' => $charge->id,
            'billing_status' => 'unbilled',
            'is_billable' => true,
        ]);

        foreach ($record->items as $item) {
            $item->update([
                'student_account_charge_id' => $charge->id,
                'is_billed' => false,
                'billed_quantity' => 0,
                'credited_quantity' => 0,
            ]);
        }

        return $charge;
    }

    public function createTransportCharge(TransportStudentAssignment $assignment, int $monthId, int $year, ?string $dueDate = null): StudentAccountCharge
    {
        $assignment->loadMissing(['student', 'enrollmentRecord', 'route', 'stop']);

        $existingCharge = StudentAccountCharge::query()
            ->where('source_module', 'transport')
            ->where('source_type', 'transport_assignment')
            ->where('source_id', $assignment->id)
            ->where('billing_period_month_id', $monthId)
            ->where('billing_period_year', $year)
            ->first();

        if ($existingCharge) {
            return $existingCharge;
        }

        return StudentAccountCharge::create([
            'student_id' => $assignment->student_id,
            'student_enrollment_record_id' => $assignment->student_enrollment_record_id,
            'campus_id' => $assignment->campus_id,
            'session_id' => $assignment->enrollmentRecord?->session_id,
            'class_id' => $assignment->enrollmentRecord?->class_id,
            'section_id' => $assignment->enrollmentRecord?->section_id,
            'source_module' => 'transport',
            'source_type' => 'transport_assignment',
            'source_id' => $assignment->id,
            'charge_category' => 'transport',
            'title' => 'Transport Due - '.($assignment->route?->name ?: 'Route'),
            'description' => $assignment->stop
                ? 'Transport stop: '.$assignment->stop->name
                : 'Monthly transport assignment charge',
            'charge_date' => now()->toDateString(),
            'billing_period_month_id' => $monthId,
            'billing_period_year' => $year,
            'due_date' => $dueDate ?? now()->endOfMonth()->toDateString(),
            'amount' => $assignment->monthly_fee,
            'discount_amount' => 0,
            'fine_amount' => 0,
            'net_amount' => $assignment->monthly_fee,
            'status' => 'open',
            'billing_status' => 'unbilled',
            'is_recurring' => true,
            'meta' => [
                'transport_route_id' => $assignment->transport_route_id,
                'transport_stop_id' => $assignment->transport_stop_id,
            ],
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]);
    }

    public function applyInventoryReturn(StudentInventory $record, StudentInventoryReturn $returnRecord, Collection $returnedItems, float $creditAmount): ?StudentAccountAdjustment
    {
        $charge = $record->studentAccountCharge;

        if (! $charge || $creditAmount <= 0) {
            return null;
        }

        $newAmount = max(0, (float) $charge->amount - $creditAmount);
        $newNetAmount = max(0, (float) $charge->net_amount - $creditAmount);

        $charge->update([
            'amount' => $newAmount,
            'net_amount' => $newNetAmount,
            'status' => $newNetAmount <= 0 ? 'adjusted' : 'open',
            'meta' => array_merge($charge->meta ?? [], [
                'last_return_id' => $returnRecord->id,
                'last_return_credit' => $creditAmount,
            ]),
        ]);

        $adjustment = StudentAccountAdjustment::create([
            'student_id' => $record->student_id,
            'student_account_charge_id' => $charge->id,
            'adjustment_type' => 'credit_note',
            'source_module' => 'inventory',
            'source_id' => $returnRecord->id,
            'amount' => $creditAmount,
            'reason' => 'Inventory return credit - '.$returnRecord->return_id,
            'meta' => [
                'student_inventory_record_id' => $record->id,
                'returned_items' => $returnedItems->all(),
                'charge_amount_after' => $newNetAmount,
            ],
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]);

        $returnRecord->update([
            'financial_effect' => 'credit',
            'student_account_adjustment_id' => $adjustment->id,
        ]);

        $record->update([
            'billing_status' => $newNetAmount <= 0 ? 'partially_billed' : $record->billing_status,
        ]);

        return $adjustment;
    }

    public function syncVoucherChargeStatuses(FeeVoucher $voucher): void
    {
        $voucher->loadMissing('items.studentAccountCharge');

        foreach ($voucher->items as $item) {
            if (! $item->studentAccountCharge) {
                continue;
            }

            $this->syncChargeSettlement($item->studentAccountCharge);
        }

        $this->syncVoucherSettlement($voucher);
    }

    public function updateChargeFromVoucherItem(FeeVoucher $voucher, FeeVoucherItem $item): ?StudentAccountCharge
    {
        $charge = $item->studentAccountCharge;

        if (! $charge) {
            return null;
        }

        $status = $voucher->status instanceof \BackedEnum
            ? $voucher->status->value
            : (string) $voucher->status;

        $charge->update([
            'title' => $item->feeHead?->name ?? $charge->title,
            'description' => $item->description,
            'due_date' => $voucher->due_date,
            'amount' => $item->amount,
            'discount_amount' => $item->discount_amount,
            'fine_amount' => $item->fine_amount,
            'net_amount' => $item->net_amount,
            'status' => $status === 'paid' ? 'paid' : ($status === 'partial' ? 'partial' : 'open'),
            'voucher_id' => $voucher->id,
            'voucher_item_id' => $item->id,
            'billing_status' => 'billed',
        ]);

        return $charge;
    }

    public function getChargeAllocatedAmount(StudentAccountCharge $charge): float
    {
        return (float) FeePaymentAllocation::where('student_account_charge_id', $charge->id)->sum('allocated_amount');
    }

    public function getChargeBalance(StudentAccountCharge $charge): float
    {
        return max(0, (float) $charge->net_amount - $this->getChargeAllocatedAmount($charge));
    }

    public function getVoucherItemAllocatedAmount(FeeVoucherItem $item): float
    {
        return (float) FeePaymentAllocation::where('fee_voucher_item_id', $item->id)->sum('allocated_amount');
    }

    public function getVoucherItemBalance(FeeVoucherItem $item): float
    {
        if ($item->studentAccountCharge) {
            return $this->getChargeBalance($item->studentAccountCharge);
        }

        return max(0, (float) $item->net_amount - $this->getVoucherItemAllocatedAmount($item));
    }

    public function syncChargeSettlement(StudentAccountCharge $charge): void
    {
        $allocated = $this->getChargeAllocatedAmount($charge);
        $netAmount = (float) $charge->net_amount;
        $status = $charge->status;

        if ($status !== 'cancelled' && $status !== 'adjusted' && $status !== 'written_off') {
            if ($allocated <= 0) {
                $status = 'open';
            } elseif ($allocated >= $netAmount) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }
        }

        $charge->update([
            'status' => $status,
            'billing_status' => $charge->voucher_item_id ? 'billed' : $charge->billing_status,
        ]);
    }

    public function syncVoucherSettlement(FeeVoucher $voucher): void
    {
        $paidAmount = (float) FeePaymentAllocation::where('fee_voucher_id', $voucher->id)->sum('allocated_amount');
        $netAmount = (float) $voucher->net_amount;
        $balanceAmount = max(0, $netAmount - $paidAmount);

        $status = $voucher->status instanceof \BackedEnum
            ? $voucher->status->value
            : (string) $voucher->status;

        if ($status !== 'cancelled') {
            if ($balanceAmount <= 0 && $netAmount > 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            } else {
                $status = 'unpaid';
            }
        }

        $voucher->update([
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'status' => $status,
        ]);
    }
}
