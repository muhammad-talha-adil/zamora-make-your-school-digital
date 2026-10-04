<?php

namespace App\Services\Staff;

use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\StaffAdvance;
use App\Models\StaffAdvanceDeduction;
use App\Models\StaffProfile;
use App\Services\Attendance\WorkingDayCalculator;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generating a payroll run, and paying it.
 *
 * Extracted from `StaffController`, which had this inline and read only the
 * legacy lump salary columns. The figures now come from `StaffSalaryService`
 * (components where they exist, the lump columns as a fallback) and carry one
 * deduction the old code had no way to know about: **unpaid leave**, read from
 * `StaffLeaveService::unpaidDaysBetween()`.
 *
 * The shape of `PayrollRun` / `PayrollRunItem` this writes, and the two
 * accounting journals it posts, are unchanged — `UnifiedAccountingService`
 * and the existing staff screen both read them as before.
 */
class StaffPayrollService
{
    public function __construct(
        private StaffSalaryService $salary,
        private StaffLeaveService $leave,
        private WorkingDayCalculator $workingDays,
        private UnifiedAccountingService $accounting
    ) {}

    /**
     * Generates (or regenerates) a run for a campus, month and year.
     *
     * @param  array{campus_id?: int|null, payroll_month_id: int, payroll_year: int, title?: string|null}  $data
     */
    public function generateRun(array $data, ?int $actorId = null): PayrollRun
    {
        $month = Month::find($data['payroll_month_id']);
        [$from, $to] = $this->monthSpan($month, (int) $data['payroll_year']);

        $staffProfiles = StaffProfile::with('user')
            ->where('is_active', true)
            ->when($data['campus_id'] ?? null, fn ($q, $campusId) => $q->where('campus_id', $campusId))
            ->get();

        $run = DB::transaction(function () use ($data, $staffProfiles, $from, $to, $actorId) {
            $title = $data['title'] ?? 'Payroll '.$data['payroll_month_id'].'/'.$data['payroll_year'];

            $run = PayrollRun::updateOrCreate(
                [
                    'campus_id' => $data['campus_id'] ?? null,
                    'payroll_month_id' => $data['payroll_month_id'],
                    'payroll_year' => $data['payroll_year'],
                ],
                [
                    'title' => $title,
                    'status' => 'processed',
                    'processed_at' => now(),
                    'created_by' => $actorId,
                ]
            );

            // Force, not soft: `payroll_run_items` carries a unique
            // (payroll_run_id, staff_profile_id) index, so a soft-deleted row
            // still blocks the fresh one a regeneration writes in its place.
            $run->items()->forceDelete();

            $totals = ['gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0];

            foreach ($staffProfiles as $profile) {
                $figures = $this->figuresFor($profile, $from, $to);

                $item = $run->items()->create([
                    'staff_profile_id' => $profile->id,
                    'gross_salary' => $figures['basic'],
                    'allowance_amount' => $figures['allowance'],
                    'deduction_amount' => $figures['deduction'],
                    'net_salary' => $figures['net'],
                    'status' => 'pending',
                    'payment_method' => $profile->payment_method,
                    'notes' => $figures['unpaid_days'] > 0
                        ? "Includes deduction for {$figures['unpaid_days']} unpaid leave day(s)."
                        : null,
                ]);

                $this->applyAdvanceDeduction($item, $to);

                $totals['gross'] += $figures['gross'];
                $totals['deductions'] += $figures['deduction'];
                $totals['net'] += $figures['net'];
            }

            $run->update([
                'total_gross' => $totals['gross'],
                'total_deductions' => $totals['deductions'],
                'total_net' => $totals['net'],
            ]);

            return $run;
        });

        $this->accounting->postPayrollAccrualJournal($run->fresh());

        return $run->fresh(['campus', 'month', 'items.staffProfile.user']);
    }

    /**
     * Releases one person's pay — in full, or a partial instalment.
     *
     * `amount` defaults to whatever is still due, so the existing "mark
     * paid" callers that never sent one keep settling the item in full.
     *
     * @param  array{payment_method: string, reference_no?: string|null, amount?: float|string|null}  $data
     */
    public function pay(PayrollRunItem $item, array $data): PayrollRunItem
    {
        $due = $item->amountDue();
        $amount = isset($data['amount']) && $data['amount'] !== null && $data['amount'] !== ''
            ? round((float) $data['amount'], 2)
            : $due;

        $newPaid = round((float) $item->amount_paid + $amount, 2);
        $payable = round((float) $item->net_salary - (float) $item->advance_deduction_amount, 2);

        DB::transaction(function () use ($item, $data, $newPaid, $payable) {
            $item->update([
                'amount_paid' => $newPaid,
                'status' => $newPaid >= $payable ? 'paid' : 'partial',
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => now(),
            ]);
        });

        $this->accounting->postPayrollPaymentJournal($item->fresh(['payrollRun', 'staffProfile.user']));

        $run = $item->payrollRun()->with('items')->first();

        if ($run && $run->items->every(fn (PayrollRunItem $i) => $i->status === 'paid')) {
            $run->update(['status' => 'paid', 'paid_at' => now()]);
        }

        return $item->fresh(['staffProfile.user']);
    }

    /**
     * Disburses an advance: a lump sum now, outside any payroll item.
     *
     * @param  array{staff_profile_id: int, amount: float, disbursed_date: string, monthly_deduction_amount?: float|null, notes?: string|null}  $data
     */
    public function giveAdvance(array $data, ?int $actorId = null): StaffAdvance
    {
        return StaffAdvance::create([
            'staff_profile_id' => $data['staff_profile_id'],
            'amount' => $data['amount'],
            'disbursed_date' => $data['disbursed_date'],
            'monthly_deduction_amount' => $data['monthly_deduction_amount'] ?? null,
            'balance_remaining' => $data['amount'],
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
            'given_by' => $actorId,
        ]);
    }

    /**
     * Early close: the staff member paid it back directly, or it is waived.
     * Either way, no further payroll runs will deduct against it.
     */
    public function returnAdvance(StaffAdvance $advance, string $status = 'returned'): StaffAdvance
    {
        $advance->update([
            'status' => $status,
            'balance_remaining' => 0,
            'settled_at' => now(),
        ]);

        return $advance->fresh();
    }

    /**
     * Applies the staff member's active advance (if any) against this
     * month's item, reducing what is payable and the advance's balance.
     *
     * A fixed `monthly_deduction_amount` is capped at whatever remains; a
     * plan with none set deducts the whole remaining balance in one go.
     */
    private function applyAdvanceDeduction(PayrollRunItem $item, Carbon $monthEnd): void
    {
        $advance = StaffAdvance::where('staff_profile_id', $item->staff_profile_id)
            ->where('status', 'active')
            ->where('balance_remaining', '>', 0)
            ->orderBy('disbursed_date')
            ->first();

        if (! $advance) {
            return;
        }

        $planned = $advance->monthly_deduction_amount !== null
            ? (float) $advance->monthly_deduction_amount
            : (float) $advance->balance_remaining;

        $installment = min($planned, (float) $advance->balance_remaining, (float) $item->net_salary);

        if ($installment <= 0) {
            return;
        }

        DB::transaction(function () use ($item, $advance, $installment, $monthEnd) {
            $item->update(['advance_deduction_amount' => $installment]);

            $advance->update([
                'balance_remaining' => round((float) $advance->balance_remaining - $installment, 2),
            ]);

            if ((float) $advance->balance_remaining <= 0) {
                $advance->update(['status' => 'settled', 'settled_at' => now()]);
            }

            StaffAdvanceDeduction::create([
                'staff_advance_id' => $advance->id,
                'payroll_run_item_id' => $item->id,
                'amount' => $installment,
                'deducted_on' => $monthEnd->toDateString(),
                'type' => 'auto',
            ]);
        });
    }

    /**
     * One person's figures for the month, as the run stores them.
     *
     * @return array{basic: float, allowance: float, gross: float, deduction: float, net: float, unpaid_days: float}
     */
    private function figuresFor(StaffProfile $profile, Carbon $from, Carbon $to): array
    {
        $onDate = $to->toDateString();

        $basic = (float) $profile->basic_salary;
        $gross = $this->salary->grossOn($profile, $onDate);
        $allowance = max($gross - $basic, 0.0);
        $deduction = $this->salary->deductionsOn($profile, $onDate);

        $unpaidDays = $this->leave->unpaidDaysBetween($profile, $from->toDateString(), $to->toDateString());

        if ($unpaidDays > 0) {
            $workingDays = $this->workingDays->workingDaysBetween($from, $to, $profile->campus_id);
            $perDay = $workingDays > 0 ? $gross / $workingDays : 0.0;
            $deduction += round($perDay * $unpaidDays, 2);
        }

        $net = max($gross - $deduction, 0.0);

        return [
            'basic' => $basic,
            'allowance' => $allowance,
            'gross' => $gross,
            'deduction' => round($deduction, 2),
            'net' => round($net, 2),
            'unpaid_days' => $unpaidDays,
        ];
    }

    /**
     * The calendar span a payroll month covers.
     *
     * `months` is a lookup of the twelve month names (`month_number` 1-12),
     * not a date range, so the year given on the form is what turns "April"
     * into a real span.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function monthSpan(?Month $month, int $year): array
    {
        $number = $month?->month_number ?? (int) now()->month;

        $from = Carbon::create($year, $number, 1)->startOfMonth();

        return [$from, $from->copy()->endOfMonth()];
    }
}
