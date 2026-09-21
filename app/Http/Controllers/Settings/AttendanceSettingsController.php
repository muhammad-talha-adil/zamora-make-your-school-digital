<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AttendanceTiming;
use App\Models\Campus;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingsController extends Controller
{
    /**
     * Display the attendance settings page.
     */
    public function show(Request $request): Response
    {
        $this->authorize('attendance.settings');

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
        $classes = SchoolClass::orderBy('id', 'asc')->get(['id', 'name']);
        $shiftTimings = AttendanceTiming::with('campus')->orderBy('id', 'desc')->get();

        return Inertia::render('attendance/Settings', [
            'leaveTypes' => $leaveTypes,
            'holidays' => $holidays,
            'campuses' => $campuses,
            'classes' => $classes,
            'shiftTimings' => $shiftTimings,
        ]);
    }

    /**
     * Store a newly created shift timing (#103): a school's own timing/shift
     * group — check-in, check-out, the grace window before a late mark, and a
     * break — optionally scoped to a set of classes.
     */
    public function storeShiftTiming(Request $request): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $validated = $this->validateShiftTiming($request);

        AttendanceTiming::create($validated);

        return back()->with('success', 'Shift timing created successfully.');
    }

    /**
     * Update the specified shift timing.
     */
    public function updateShiftTiming(Request $request, AttendanceTiming $shiftTiming): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $validated = $this->validateShiftTiming($request);

        $shiftTiming->update($validated);

        return back()->with('success', 'Shift timing updated successfully.');
    }

    /**
     * Remove the specified shift timing.
     */
    public function destroyShiftTiming(AttendanceTiming $shiftTiming): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $shiftTiming->delete();

        return back()->with('success', 'Shift timing deleted successfully.');
    }

    /**
     * Toggle the active status of a shift timing.
     */
    public function toggleShiftTimingActive(AttendanceTiming $shiftTiming): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $shiftTiming->update(['is_active' => ! $shiftTiming->is_active]);

        return back()->with('success', 'Shift timing status updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateShiftTiming(Request $request): array
    {
        $validated = $request->validate([
            'campus_id' => ['required', 'integer', Rule::exists('campuses', 'id')],
            'session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', Rule::exists('school_classes', 'id')],
            'name' => ['required', 'string', 'max:60'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'day_starts_at' => ['required', 'date_format:H:i'],
            'late_after' => ['required', 'date_format:H:i'],
            'break_starts_at' => ['nullable', 'date_format:H:i'],
            'break_ends_at' => ['nullable', 'date_format:H:i', 'after:break_starts_at'],
            'day_ends_at' => ['nullable', 'date_format:H:i'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['class_ids'] = empty($validated['class_ids']) ? null : $validated['class_ids'];

        return $validated;
    }

    /**
     * Display a listing of leave types.
     */
    public function indexLeaveTypes(Request $request)
    {
        $this->authorize('attendance.settings');

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
        $this->authorize('attendance.settings');

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
        $this->authorize('attendance.settings');

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
        $this->authorize('attendance.settings');

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
        $this->authorize('attendance.settings');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,'.$leaveType->id,
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
        $this->authorize('attendance.settings');

        $leaveType->delete();

        return back()->with('success', 'Leave type deleted successfully.');
    }

    /**
     * Toggle the active status of a leave type.
     */
    public function toggleLeaveTypeActive(LeaveType $leaveType): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        return back()->with('success', 'Leave type status updated successfully.');
    }

    /**
     * Store a newly created holiday.
     */
    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'campus_id' => 'nullable|exists:campuses,id',
            'is_national' => 'boolean',
            'description' => 'nullable|string',
            'recurrence_type' => 'nullable|in:none,yearly,monthly,weekly',
            'recurrence_end_date' => 'nullable|date|after:start_date',
            'is_attendance_allowed' => 'boolean',
        ]);

        // If national holiday, campus_id must be null
        if ($validated['is_national'] ?? false) {
            $validated['campus_id'] = null;
        }

        Holiday::create($validated);

        return back()->with('success', 'Holiday created successfully.');
    }

    /**
     * Update the specified holiday.
     */
    public function updateHoliday(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'campus_id' => 'nullable|exists:campuses,id',
            'is_national' => 'boolean',
            'description' => 'nullable|string',
            'recurrence_type' => 'nullable|in:none,yearly,monthly,weekly',
            'recurrence_end_date' => 'nullable|date|after:start_date',
            'is_attendance_allowed' => 'boolean',
        ]);

        // If national holiday, campus_id must be null
        if ($validated['is_national'] ?? false) {
            $validated['campus_id'] = null;
        }

        $holiday->update($validated);

        return back()->with('success', 'Holiday updated successfully.');
    }

    /**
     * Remove the specified holiday.
     */
    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $this->authorize('attendance.settings');

        $holiday->delete();

        return back()->with('success', 'Holiday deleted successfully.');
    }
}
