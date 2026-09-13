<?php

namespace App\Services\Staff;

use App\Models\Staff\SalaryHead;
use App\Models\Staff\StaffSalaryComponent;
use App\Models\StaffProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Named parts of a salary.
 *
 * `staff_profiles.allowance_amount` is one lump sum, and `basic_salary` stays
 * the base pay — that part of the plan was never the problem. What could not
 * be answered was "what is this two thousand *for*". `salary_heads` names it.
 *
 * A profile with no components at all still works: the legacy lump columns are
 * the fallback everywhere this is read, so nothing already running (payroll,
 * the ID card, the old staff screen) breaks while components are filled in.
 */
class StaffSalaryService
{
    /**
     * Sets what somebody is paid under one head, from a date.
     *
     * **A raise is a new row, not an edit.** Whatever was in force under this
     * head is closed the day before, so a payroll run for June still finds
     * June's figure after July's has been set.
     *
     * @throws ValidationException
     */
    public function setComponent(
        StaffProfile $staff,
        int $salaryHeadId,
        float $amount,
        string $effectiveFrom,
        ?string $notes = null
    ): StaffSalaryComponent {
        $head = SalaryHead::find($salaryHeadId);

        if (! $head || ! $head->is_active) {
            throw ValidationException::withMessages([
                'salary_head_id' => 'That salary head is not available.',
            ]);
        }

        return DB::transaction(function () use ($staff, $head, $amount, $effectiveFrom) {
            $dayBefore = Carbon::parse($effectiveFrom)->subDay()->toDateString();

            StaffSalaryComponent::where('staff_profile_id', $staff->id)
                ->where('salary_head_id', $head->id)
                ->where('effective_from', '<', $effectiveFrom)
                ->whereNull('effective_to')
                ->update(['effective_to' => $dayBefore]);

            return StaffSalaryComponent::create([
                'staff_profile_id' => $staff->id,
                'salary_head_id' => $head->id,
                'amount' => $amount,
                'effective_from' => $effectiveFrom,
            ]);
        });
    }

    /**
     * Stops a component from a date, without replacing it.
     */
    public function endComponent(StaffSalaryComponent $component, ?string $effectiveTo = null): StaffSalaryComponent
    {
        $component->update(['effective_to' => $effectiveTo ?? now()->toDateString()]);

        return $component->fresh();
    }

    /**
     * The components in force on a date, with their heads.
     *
     * @return Collection<int, StaffSalaryComponent>
     */
    public function componentsInForceOn(StaffProfile $staff, string $date)
    {
        return StaffSalaryComponent::where('staff_profile_id', $staff->id)
            ->with('salaryHead')
            ->inForceOn($date)
            ->get();
    }

    /**
     * What counts as the salary on a date: basic pay plus whichever allowance
     * figure is in force — the components once any exist, the legacy lump
     * otherwise.
     */
    public function grossOn(StaffProfile $staff, string $date): float
    {
        $components = $this->componentsInForceOn($staff, $date);
        $basic = (float) $staff->basic_salary;

        if ($components->isEmpty()) {
            return $basic + (float) $staff->allowance_amount;
        }

        $allowances = $components
            ->filter(fn (StaffSalaryComponent $c) => $c->salaryHead?->type === SalaryHead::TYPE_ALLOWANCE
                && $c->salaryHead->is_part_of_gross)
            ->sum('amount');

        return $basic + (float) $allowances;
    }

    /**
     * Deductions in force on a date, from the components alone — payroll adds
     * the leave-based deduction separately.
     */
    public function deductionsOn(StaffProfile $staff, string $date): float
    {
        $components = $this->componentsInForceOn($staff, $date);

        if ($components->isEmpty()) {
            return (float) $staff->deduction_amount;
        }

        return (float) $components
            ->filter(fn (StaffSalaryComponent $c) => $c->salaryHead?->type === SalaryHead::TYPE_DEDUCTION)
            ->sum('amount');
    }
}
