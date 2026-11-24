<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceStatus;
use App\Models\Staff\StaffAttendance;
use App\Models\StaffProfile;
use App\Services\AttendanceService;
use App\Services\Staff\StaffAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A member of staff's own attendance register.
 *
 * `viewAttendance`/`markAttendance` on `StaffProfilePolicy` were built in
 * Phase 2, ahead of this controller — nothing here is new authorization.
 */
class StaffAttendanceController extends Controller
{
    public function __construct(
        private StaffAttendanceService $attendance,
        private AttendanceService $studentAttendance
    ) {}

    /**
     * The whole-campus register screen: every active member of staff for one
     * day, so a bulk mark does not require opening each person in turn.
     */
    public function page(Request $request): Response
    {
        Gate::authorize('markAttendance', StaffProfile::class);

        $date = $request->query('date', now()->toDateString());

        $staff = StaffProfile::query()
            ->visibleTo($request->user())
            ->where('is_active', true)
            ->with(['user:id,name', 'designation:id,name'])
            ->orderBy('employee_no')
            ->get(['id', 'user_id', 'employee_no', 'campus_id', 'designation_id']);

        $existing = StaffAttendance::query()
            ->whereIn('staff_profile_id', $staff->pluck('id'))
            ->whereDate('attendance_date', $date)
            ->get(['staff_profile_id', 'attendance_status_id', 'check_in_at', 'check_out_at', 'remarks', 'is_locked'])
            ->keyBy('staff_profile_id');

        $holiday = $this->studentAttendance->getHoliday(Carbon::parse($date)->toDateString(), $request->user()->campusId());

        return Inertia::render('Staff/Attendance/Index', [
            'staff' => $staff,
            'existing' => $existing,
            'date' => $date,
            'holiday' => $holiday ? ['title' => $this->studentAttendance->holidayTitles(Carbon::parse($date)->toDateString(), $request->user()->campusId()) ?? $holiday->title] : null,
            'statuses' => AttendanceStatus::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Per-person Present / Absent / Late / Leave counts for one month.
     */
    public function monthlyReport(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);

        $staff = StaffProfile::query()
            ->visibleTo($request->user())
            ->where('is_active', true)
            ->with(['user:id,name', 'designation:id,name'])
            ->orderBy('employee_no')
            ->get(['id', 'user_id', 'employee_no', 'campus_id', 'designation_id']);

        $rows = $staff->map(fn (StaffProfile $member) => [
            'id' => $member->id,
            'name' => $member->user?->name ?? $member->employee_no,
            'employee_no' => $member->employee_no,
            'designation' => $member->designation?->name,
            ...$this->attendance->monthlySummary($member, $month, $month, $year),
        ])->values();

        return Inertia::render('Staff/Attendance/Report', [
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * One person's day, or a stretch of them.
     */
    public function index(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('viewAttendance', $staffProfile);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $rows = $staffProfile->attendances()
            ->with('status')
            ->when($validated['from'] ?? null, fn ($q, $from) => $q->whereDate('attendance_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($q, $to) => $q->whereDate('attendance_date', '<=', $to))
            ->orderByDesc('attendance_date')
            ->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * Marks one person for one day.
     */
    public function store(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('markAttendance', StaffProfile::class);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance_status_id' => ['required', 'integer', 'exists:attendance_statuses,id'],
            'check_in_at' => ['nullable', 'date_format:H:i'],
            'check_out_at' => ['nullable', 'date_format:H:i'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ], [
            'attendance_date.before_or_equal' => 'Attendance cannot be marked for a future date.',
        ]);

        $row = $this->attendance->mark(
            $staffProfile,
            $validated['attendance_date'],
            $validated['attendance_status_id'],
            $validated['check_in_at'] ?? null,
            $validated['check_out_at'] ?? null,
            $validated['campus_id'] ?? null,
            $request->user(),
            $validated['remarks'] ?? null
        );

        return response()->json(['message' => 'Attendance marked', 'data' => $row]);
    }

    /**
     * A whole campus's register for one day, in one call.
     */
    public function storeMany(Request $request)
    {
        Gate::authorize('markAttendance', StaffProfile::class);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.staff_profile_id' => ['required', 'integer', 'exists:staff_profiles,id'],
            'rows.*.attendance_status_id' => ['required', 'integer', 'exists:attendance_statuses,id'],
            'rows.*.check_in_at' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out_at' => ['nullable', 'date_format:H:i'],
            'rows.*.remarks' => ['nullable', 'string', 'max:255'],
        ], [
            'attendance_date.before_or_equal' => 'Attendance cannot be marked for a future date.',
        ]);

        $marked = $this->attendance->markMany(
            $validated['rows'],
            $validated['attendance_date'],
            $validated['campus_id'] ?? null,
            $request->user()
        );

        return response()->json(['message' => "{$marked} record(s) marked"]);
    }

    /**
     * Closes a day so it cannot be re-marked.
     */
    public function lockDay(Request $request)
    {
        Gate::authorize('markAttendance', StaffProfile::class);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ], [
            'attendance_date.before_or_equal' => 'Attendance cannot be marked for a future date.',
        ]);

        $locked = $this->attendance->lockDay($validated['attendance_date'], $validated['campus_id'] ?? null);

        return response()->json(['message' => "{$locked} record(s) locked"]);
    }

    /**
     * One person's month-range summary — expected, present, absent, late, leave.
     */
    public function summary(Request $request, StaffProfile $staffProfile)
    {
        Gate::authorize('viewAttendance', $staffProfile);

        $validated = $request->validate([
            'from_month' => ['required', 'integer', 'min:1', 'max:12'],
            'to_month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        return response()->json([
            'data' => $this->attendance->monthlySummary(
                $staffProfile,
                $validated['from_month'],
                $validated['to_month'],
                $validated['year']
            ),
        ]);
    }

    /**
     * The statuses a register may use — same list the student register reads.
     */
    public function statuses()
    {
        return response()->json(['data' => AttendanceStatus::orderBy('name')->get(['id', 'name', 'code'])]);
    }
}
