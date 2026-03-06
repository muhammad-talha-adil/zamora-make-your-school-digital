<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\Holiday;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingsController extends Controller
{
    /**
     * Display the attendance settings page.
     */
    public function show(Request $request): Response
    {
        $this->authorize('settings.manage');

        $leaveTypes = LeaveType::orderBy('id', 'desc')->paginate(10);
        
        // Only show current and future holidays (past holidays are handled separately)
        $holidays = Holiday::with('campus')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->paginate(10);

        // Add is_past flag to each holiday
        $holidays->getCollection()->transform(function ($holiday) {
            $holiday->is_past = $holiday->end_date < now()->toDateString();
            return $holiday;
        });

        $campuses = Campus::orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('attendance/Settings', [
            'leaveTypes' => $leaveTypes,
            'holidays' => $holidays,
            'campuses' => $campuses,
        ]);
    }

    /**
     * Display a listing of leave types.
     */
    public function indexLeaveTypes(Request $request)
    {
        $this->authorize('settings.manage');

        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        $query = LeaveType::orderBy('id', 'desc');

        if ($request->status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($request->status === 'active') {
            $query->where('is_active', true);
        }

        $leaveTypes = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($leaveTypes);
    }

    /**
     * Display a listing of holidays.
     */
    public function indexHolidays(Request $request)
    {
        $this->authorize('settings.manage');

        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        $query = Holiday::with('campus')
            ->select('holidays.*')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc');

        $holidays = $query->paginate($perPage, ['*'], 'page', $page);

        // Add is_past flag to each holiday
        $holidays->getCollection()->transform(function ($holiday) {
            $holiday->is_past = $holiday->end_date < now()->toDateString();
            return $holiday;
        });

        return response()->json($holidays);
    }

    /**
     * Display a listing of past holidays.
     */
    public function indexPastHolidays(Request $request)
    {
        $this->authorize('settings.manage');

        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;

        $query = Holiday::with('campus')
            ->where('end_date', '<', now()->toDateString())
            ->orderBy('start_date', 'desc');

        $holidays = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($holidays);
    }

    /**
     * Store a newly created leave type.
     */
    public function storeLeaveType(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        LeaveType::create($validated);

        return back()->with('success', 'Leave type created successfully.');
    }

    /**
     * Update the specified leave type.
     */
    public function updateLeaveType(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,' . $leaveType->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $leaveType->update($validated);

        return back()->with('success', 'Leave type updated successfully.');
    }

    /**
     * Remove the specified leave type.
     */
    public function destroyLeaveType(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('settings.manage');

        $leaveType->delete();

        return back()->with('success', 'Leave type deleted successfully.');
    }

    /**
     * Store a newly created holiday.
     */
    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'campus_id' => 'nullable|exists:campuses,id',
            'is_national' => 'boolean',
            'description' => 'nullable|string',
        ]);

        Holiday::create($validated);

        return back()->with('success', 'Holiday created successfully.');
    }

    /**
     * Update the specified holiday.
     */
    public function updateHoliday(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'campus_id' => 'nullable|exists:campuses,id',
            'is_national' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $holiday->update($validated);

        return back()->with('success', 'Holiday updated successfully.');
    }

    /**
     * Remove the specified holiday.
     */
    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $this->authorize('settings.manage');

        $holiday->delete();

        return back()->with('success', 'Holiday deleted successfully.');
    }
}
