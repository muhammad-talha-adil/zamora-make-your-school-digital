<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\SalaryHead;
use App\Models\Staff\StaffSalaryComponent;
use App\Models\StaffProfile;
use App\Services\Staff\StaffSalaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * What one person is paid, named part by part.
 *
 * `manageSalary`/`viewSalary` on `StaffProfilePolicy` were built in Phase 2 —
 * nothing here is new authorization. Nobody had called either ability until now.
 */
class StaffSalaryController extends Controller
{
    public function __construct(
        private StaffSalaryService $salary
    ) {}

    /**
     * The components in force today, and the heads a school offers.
     */
    public function index(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('viewSalary', $staffProfile);

        return response()->json([
            'data' => $this->salary->componentsInForceOn($staffProfile, now()->toDateString()),
            'heads' => SalaryHead::active()->orderBy('sort_order')->get(),
            'gross' => $this->salary->grossOn($staffProfile, now()->toDateString()),
            'deductions' => $this->salary->deductionsOn($staffProfile, now()->toDateString()),
        ]);
    }

    /**
     * Sets what somebody is paid under one head, from a date.
     *
     * A raise is a new row, not an edit — see `StaffSalaryService::setComponent()`.
     */
    public function store(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('manageSalary', $staffProfile);

        $validated = $request->validate([
            'salary_head_id' => ['required', 'integer', 'exists:salary_heads,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
        ]);

        $component = $this->salary->setComponent(
            $staffProfile,
            $validated['salary_head_id'],
            (float) $validated['amount'],
            $validated['effective_from']
        );

        return response()->json(['message' => 'Salary component set', 'data' => $component], 201);
    }

    /**
     * Stops a component from a date, without replacing it.
     */
    public function end(Request $request, StaffSalaryComponent $component)
    {
        Gate::authorize('manageSalary', $component->staffProfile);

        $validated = $request->validate(['effective_to' => ['nullable', 'date']]);

        return response()->json([
            'message' => 'Salary component ended',
            'data' => $this->salary->endComponent($component, $validated['effective_to'] ?? null),
        ]);
    }
}
