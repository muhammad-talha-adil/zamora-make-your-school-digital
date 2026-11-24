<?php

use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use Illuminate\Database\Migrations\Migration;

/**
 * Payroll item `net_salary` now already has the advance instalment taken off
 * it. Rows written before that still hold the pre-advance figure, so fold the
 * instalment in once and bring the run totals into line.
 */
return new class extends Migration
{
    public function up(): void
    {
        $runIds = [];

        PayrollRunItem::withTrashed()
            ->where('advance_deduction_amount', '>', 0)
            ->each(function (PayrollRunItem $item) use (&$runIds) {
                $advance = (float) $item->advance_deduction_amount;

                $item->forceFill(['net_salary' => max(round((float) $item->net_salary - $advance, 2), 0)])->saveQuietly();
                $runIds[$item->payroll_run_id] = ($runIds[$item->payroll_run_id] ?? 0) + $advance;
            });

        foreach ($runIds as $runId => $advance) {
            $run = PayrollRun::find($runId);

            $run?->forceFill([
                'total_deductions' => round((float) $run->total_deductions + $advance, 2),
                'total_net' => max(round((float) $run->total_net - $advance, 2), 0),
            ])->saveQuietly();
        }
    }

    public function down(): void {}
};
