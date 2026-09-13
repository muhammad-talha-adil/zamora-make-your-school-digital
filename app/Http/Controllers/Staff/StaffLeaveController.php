<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\StaffLeave;
use App\Models\Staff\StaffLeaveType;
use App\Models\StaffProfile;
use App\Services\Staff\StaffLeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Leave applications.
 *
 * `applyForLeave`/`decideLeave` on `StaffProfilePolicy` were built in Phase 2
 * — nothing here is new authorization.
 */
class StaffLeaveController extends Controller
{
    public function __construct(
        private StaffLeaveService $leave
    ) {}

    /**
     * One person's leave history.
     */
    public function index(StaffProfile $staffProfile)
    {
        Gate::authorize('viewAttendance', $staffProfile);

        $rows = StaffLeave::where('staff_profile_id', $staffProfile->id)
            ->with(['leaveType', 'decidedBy:id,name'])
            ->orderByDesc('from_date')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * Applies for leave.
     */
    public function store(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('applyForLeave', $staffProfile);

        $validated = $request->validate([
            'staff_leave_type_id' => ['required', 'integer', 'exists:staff_leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'days' => ['nullable', 'numeric', 'min:0.5'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $row = $this->leave->apply(
            $staffProfile,
            $validated['staff_leave_type_id'],
            $validated['from_date'],
            $validated['to_date'],
            $validated['days'] ?? null,
            $validated['reason'] ?? null,
            $request->user()
        );

        return response()->json(['message' => 'Leave application submitted', 'data' => $row], 201);
    }

    /**
     * Approves or rejects a pending application.
     */
    public function decide(Request $request, StaffLeave $leave)
    {
        Gate::authorize('decideLeave', $leave->staffProfile);

        $validated = $request->validate([
            'approve' => ['required', 'boolean'],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        $row = $this->leave->decide($leave, $validated['approve'], $request->user(), $validated['decision_note'] ?? null);

        return response()->json([
            'message' => $validated['approve'] ? 'Leave approved' : 'Leave rejected',
            'data' => $row,
        ]);
    }

    /**
     * Withdraws an application that has not been decided yet.
     */
    public function cancel(StaffLeave $leave)
    {
        Gate::authorize('applyForLeave', $leave->staffProfile);

        return response()->json(['message' => 'Leave application cancelled', 'data' => $this->leave->cancel($leave)]);
    }

    /**
     * What is left of a year's entitlement, per leave type.
     */
    public function balance(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('viewAttendance', $staffProfile);

        $year = (int) $request->query('year', now()->year);

        $balances = StaffLeaveType::active()->get()->map(fn (StaffLeaveType $type) => [
            'leave_type' => [
                'id' => $type->id,
                'name' => $type->name,
                'code' => $type->code,
                'days_per_year' => $type->days_per_year,
            ],
            'remaining' => $this->leave->balanceRemaining($staffProfile, $type, $year),
        ]);

        return response()->json(['data' => $balances]);
    }

    /**
     * The leave types a school offers.
     */
    public function types()
    {
        return response()->json(['data' => StaffLeaveType::active()->orderBy('name')->get()]);
    }
}
