<?php

namespace App\Console\Commands;

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeVoucher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RepairFeeVouchers extends Command
{
    protected $signature = 'fee:repair-vouchers {--dry-run : Only show what would change}';

    protected $description = 'Recompute totals of open vouchers from their items and set due date to the 10th of the billing month. Safe to run again.';

    public function handle(): int
    {
        $totalsFixed = 0;
        $datesFixed = 0;
        $removedFines = 0;

        FeeVoucher::query()
            ->with('voucherMonth')
            ->whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL, VoucherStatus::OVERDUE, VoucherStatus::ADJUSTED])
            ->each(function (FeeVoucher $voucher) use (&$totalsFixed, &$datesFixed, &$removedFines) {
                $before = [(float) $voucher->net_amount, (float) $voucher->balance_amount, (float) $voucher->gross_amount, (float) $voucher->fine_amount];
                $dueDate = Carbon::create($voucher->voucher_year, $voucher->voucherMonth->month_number, 10)->startOfDay();

                $isOpen = $voucher->status !== VoucherStatus::ADJUSTED;

                if ($isOpen && (! $voucher->due_date || ! $voucher->due_date->isSameDay($dueDate))) {
                    $datesFixed++;
                    $this->line("{$voucher->voucher_no}: due date -> {$dueDate->toDateString()}");

                    if (! $this->option('dry-run')) {
                        $voucher->update(['due_date' => $dueDate]);
                    }
                }

                if (! $this->option('dry-run')) {
                    $removedFines += $this->removeAutoLateFees($voucher);
                }

                if ($voucher->items()->doesntExist()) {
                    return;
                }

                if ($this->option('dry-run')) {
                    return;
                }

                if ($isOpen) {
                    $voucher->recalculateTotals();
                } else {
                    $this->recalculateClosedTotals($voucher);
                }
                $voucher->refresh();
                $after = [(float) $voucher->net_amount, (float) $voucher->balance_amount, (float) $voucher->gross_amount, (float) $voucher->fine_amount];

                if ($before !== $after) {
                    $totalsFixed++;
                    $this->line("{$voucher->voucher_no}: net {$before[0]} -> {$after[0]}, balance {$before[1]} -> {$after[1]}");
                }
            });

        $grossFixed = $this->fixStoredGross();

        $this->info("Gross/fine fixed on other vouchers: {$grossFixed}.");
        $this->info("Totals fixed: {$totalsFixed}. Due dates fixed: {$datesFixed}. Auto late fees removed: {$removedFines}.");

        return self::SUCCESS;
    }

    /**
     * Late fees nobody asked for, added to a voucher while it was generated.
     * Only on vouchers with no payment, so nothing already collected changes.
     */
    protected function removeAutoLateFees(FeeVoucher $voucher): int
    {
        $items = $voucher->items()->where('description', 'like', 'Late Fee - voucher %')->get()
            ->reject(fn ($item) => $voucher->paymentAllocations()->where('fee_voucher_item_id', $item->id)->exists());

        $lateTotal = (float) $items->sum('net_amount');

        if ($items->isEmpty() || (float) $voucher->paid_amount > (float) $voucher->net_amount - $lateTotal) {
            return 0;
        }

        foreach ($items as $item) {
            $item->studentAccountCharge?->delete();
            $item->delete();
            $this->line("{$voucher->voucher_no}: removed auto late fee {$item->description}");
        }

        return $items->count();
    }

    /**
     * Adjusted and paid vouchers keep their balance; only the shown totals follow the items.
     */
    protected function recalculateClosedTotals(FeeVoucher $voucher): void
    {
        $items = $voucher->items()->with('feeHead:id,code')->get();
        $lateFees = $items->filter(fn ($item) => $item->feeHead?->code === 'LATE_FINE' || str_starts_with((string) $item->description, 'Late fee') || str_starts_with((string) $item->description, 'Late Fee'));

        $voucher->update([
            'gross_amount' => $items->diff($lateFees)->sum('amount'),
            'discount_amount' => $items->sum('discount_amount'),
            'fine_amount' => $lateFees->sum('net_amount'),
            'net_amount' => round((float) $items->sum('net_amount'), 2),
        ]);
    }

    /**
     * Paid and cancelled vouchers: fix stale gross/fine shown on screens.
     */
    protected function fixStoredGross(): int
    {
        $fixed = 0;

        FeeVoucher::query()
            ->whereIn('status', [VoucherStatus::PAID, VoucherStatus::CANCELLED])
            ->each(function (FeeVoucher $voucher) use (&$fixed) {
                $items = $voucher->items()->with('feeHead:id,code')->get();

                if ($items->isEmpty()) {
                    return;
                }

                $late = $items->filter(fn ($item) => $item->feeHead?->code === 'LATE_FINE' || str_starts_with(strtolower((string) $item->description), 'late fee'));
                $gross = (float) $items->diff($late)->sum('amount');
                $fine = (float) $late->sum('net_amount');

                if (round($gross, 2) !== round((float) $voucher->gross_amount, 2) || round($fine, 2) !== round((float) $voucher->fine_amount, 2)) {
                    $fixed++;
                    $this->line("{$voucher->voucher_no}: gross {$voucher->gross_amount} -> {$gross}, fine {$voucher->fine_amount} -> {$fine}");

                    if (! $this->option('dry-run')) {
                        $voucher->update(['gross_amount' => $gross, 'fine_amount' => $fine]);
                    }
                }
            });

        return $fixed;
    }
}
