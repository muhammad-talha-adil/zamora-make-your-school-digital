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

        FeeVoucher::query()
            ->with('voucherMonth')
            ->whereIn('status', [VoucherStatus::UNPAID, VoucherStatus::PARTIAL, VoucherStatus::OVERDUE])
            ->each(function (FeeVoucher $voucher) use (&$totalsFixed, &$datesFixed) {
                $before = [(float) $voucher->net_amount, (float) $voucher->balance_amount, (float) $voucher->gross_amount, (float) $voucher->fine_amount];
                $dueDate = Carbon::create($voucher->voucher_year, $voucher->voucherMonth->month_number, 10)->startOfDay();

                if (! $voucher->due_date || ! $voucher->due_date->isSameDay($dueDate)) {
                    $datesFixed++;
                    $this->line("{$voucher->voucher_no}: due date -> {$dueDate->toDateString()}");

                    if (! $this->option('dry-run')) {
                        $voucher->update(['due_date' => $dueDate]);
                    }
                }

                if ($voucher->items()->doesntExist()) {
                    return;
                }

                if ($this->option('dry-run')) {
                    return;
                }

                $voucher->recalculateTotals();
                $after = [(float) $voucher->net_amount, (float) $voucher->balance_amount, (float) $voucher->gross_amount, (float) $voucher->fine_amount];

                if ($before !== $after) {
                    $totalsFixed++;
                    $this->line("{$voucher->voucher_no}: net {$before[0]} -> {$after[0]}, balance {$before[1]} -> {$after[1]}");
                }
            });

        $this->info("Totals fixed: {$totalsFixed}. Due dates fixed: {$datesFixed}.");

        return self::SUCCESS;
    }
}
