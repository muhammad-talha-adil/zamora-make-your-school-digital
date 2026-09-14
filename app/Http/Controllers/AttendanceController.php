<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatusCode;
use App\Http\Requests\Attendance\AttendanceReportRequest;
use App\Http\Requests\Attendance\StoreBulkAttendanceRequest;
use App\Http\Requests\Attendance\StudentsForAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\AttendanceStudent;
use App\Models\Campus;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use App\Services\Attendance\AbsenceAlertService;
use App\Services\Attendance\AttendanceSummaryService;
use App\Services\Attendance\LateArrivalResolver;
use App\Services\Attendance\WorkingDayCalculator;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected AttendanceSummaryService $summaries,
        protected AbsenceAlertService $absenceAlerts,
        protected WorkingDayCalculator $workingDays,
        protected LateArrivalResolver $lateArrivals,
    ) {}

    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with(['campus', 'session', 'class', 'section', 'takenBy'])
            ->visibleTo($request->user());

        // Filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('date')) {
            $query->where('attendance_date', $request->date);
        }

        if ($request->filled('locked')) {
            $query->where('is_locked', $request->boolean('locked'));
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
            ->orderBy('class_id')
            ->paginate(15)
            ->withQueryString();

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $sessions = Session::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $classes = SchoolClass::orderBy('id', 'asc')->get(['id', 'name']);
        $sections = Section::orderBy('name')->get(['id', 'name', 'class_id']);
        $attendanceStatuses = AttendanceStatus::orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('attendance/Index', [
            'attendances' => $attendances,
            'campuses' => $campuses,
            'sessions' => $sessions,
            'classes' => $classes,
            'sections' => $sections,
            'attendanceStatuses' => $attendanceStatuses,
            'filters' => $request->only(['campus_id', 'session_id', 'class_id', 'section_id', 'date', 'locked']),
        ]);
    }

    /**
     * Display the attendance dashboard with today's summary.
     */
    public function dashboard(Request $request): Response
    {
        $this->authorize('viewAny', Attendance::class);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Get current session and campus from request or use defaults
        $currentSessionId = $request->filled('session_id')
            ? $request->session_id
            : Session::where('is_active', true)->first()?->id;
        $currentCampusId = $request->filled('campus_id') ? $request->campus_id : null;

        // Today's attendance summary
        $todayQuery = Attendance::with(['class', 'section', 'attendanceStudents.attendanceStatus'])
            ->visibleTo($request->user())
            ->whereDate('attendance_date', $today);

        if ($currentCampusId) {
            $todayQuery->where('campus_id', $currentCampusId);
        }
        if ($currentSessionId) {
            $todayQuery->where('session_id', $currentSessionId);
        }

        $todayAttendances = $todayQuery->get();

        // Calculate today's statistics
        $todayStats = $this->calculateDashboardStats($todayAttendances);

        // Yesterday's summary for comparison
        $yesterdayQuery = Attendance::visibleTo($request->user())
            ->whereDate('attendance_date', $yesterday);
        if ($currentCampusId) {
            $yesterdayQuery->where('campus_id', $currentCampusId);
        }
        if ($currentSessionId) {
            $yesterdayQuery->where('session_id', $currentSessionId);
        }
        $yesterdayStats = $this->calculateDashboardStats($yesterdayQuery->get());

        // Get classes with attendance status for today
        $classSummaries = $todayAttendances->map(function ($attendance) {
            $students = $attendance->attendanceStudents;

            return [
                'id' => $attendance->id,
                'class_name' => $attendance->class?->name ?? 'N/A',
                'section_name' => $attendance->section?->name ?? 'All',
                'total' => $students->count(),
                'present' => $students->where('attendanceStatus.code', AttendanceStatusCode::PRESENT->value)->count(),
                'absent' => $students->where('attendanceStatus.code', AttendanceStatusCode::ABSENT->value)->count(),
                'leave' => $students->where('attendanceStatus.code', AttendanceStatusCode::LEAVE->value)->count(),
                'late' => $students->where('attendanceStatus.code', AttendanceStatusCode::LATE->value)->count(),
                'is_locked' => $attendance->is_locked,
                'taken_by' => $attendance->takenBy?->name,
                'attendance_date' => $attendance->attendance_date,
            ];
        });

        // Get upcoming holidays
        $upcomingHolidays = Holiday::where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(5)
            ->get(['id', 'title', 'start_date', 'end_date', 'is_national']);

        // Get recent attendance records
        $recentAttendances = Attendance::with(['class', 'section'])
            ->visibleTo($request->user())
            ->orderBy('attendance_date', 'desc')
            ->limit(10)
            ->get();

        // Get quick stats - attendance taken vs total classes
        $totalClasses = SchoolClass::count();
        $classesWithAttendance = $todayAttendances->pluck('class_id')->unique()->count();

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $sessions = Session::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $weeklyTrend = $this->getWeeklyAttendanceTrend($request->user(), $currentCampusId, $currentSessionId);

        return Inertia::render('attendance/Dashboard', [
            'todayStats' => $todayStats,
            'yesterdayStats' => $yesterdayStats,
            'classSummaries' => $classSummaries,
            'upcomingHolidays' => $upcomingHolidays,
            'recentAttendances' => $recentAttendances,
            'totalClasses' => $totalClasses,
            'classesWithAttendance' => $classesWithAttendance,
            'campuses' => $campuses,
            'sessions' => $sessions,
            'selectedCampusId' => $currentCampusId,
            'selectedSessionId' => $currentSessionId,
            'today' => $today,
            'weeklyTrend' => $weeklyTrend,
        ]);
    }

    /**
     * Attendance percentage for each of the last 7 days, for the dashboard's
     * trend chart. Reuses `calculateDashboardStats`, the same routine the
     * today/yesterday cards are built from, so the chart cannot disagree with
     * them about what "present" means.
     *
     * @return array<int, array{date: string, attendance_percentage: float}>
     */
    private function getWeeklyAttendanceTrend(?User $user, ?int $campusId, ?int $sessionId): array
    {
        $trend = [];

        for ($day = now()->subDays(6)->startOfDay(); $day->lte(now()->endOfDay()); $day->addDay()) {
            $date = $day->toDateString();

            $query = Attendance::with('attendanceStudents.attendanceStatus')
                ->visibleTo($user)
                ->whereDate('attendance_date', $date);

            if ($campusId) {
                $query->where('campus_id', $campusId);
            }
            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $stats = $this->calculateDashboardStats($query->get());

            $trend[] = [
                'date' => $date,
                'attendance_percentage' => $stats['attendance_percentage'],
            ];
        }

        return $trend;
    }

    /**
     * Calculate dashboard statistics from attendance collections.
     */
    private function calculateDashboardStats($attendances): array
    {
        $totalStudents = 0;
        $present = 0;
        $absent = 0;
        $leave = 0;
        $late = 0;

        foreach ($attendances as $attendance) {
            $students = $attendance->attendanceStudents;
            $totalStudents += $students->count();
            $present += $students->where('attendanceStatus.code', AttendanceStatusCode::PRESENT->value)->count();
            $absent += $students->where('attendanceStatus.code', AttendanceStatusCode::ABSENT->value)->count();
            $leave += $students->where('attendanceStatus.code', AttendanceStatusCode::LEAVE->value)->count();
            $late += $students->where('attendanceStatus.code', AttendanceStatusCode::LATE->value)->count();
        }

        return [
            'total_students' => $totalStudents,
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'late' => $late,
            'attendance_percentage' => $totalStudents > 0
                ? round(($present / $totalStudents) * 100, 1)
                : 0,
            'total_classes' => $attendances->count(),
        ];
    }

    /**
     * Show the form for creating attendance.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Attendance::class);

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $sessions = Session::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $classes = SchoolClass::orderBy('id', 'asc')->get(['id', 'name']);
        $sections = Section::orderBy('name')->get(['id', 'name', 'class_id']);
        $attendanceStatuses = AttendanceStatus::all();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Pre-select values if provided in request
        $selectedCampusId = $request->filled('campus_id') ? $request->campus_id : null;
        $selectedSessionId = $request->filled('session_id') ? $request->session_id : null;
        $selectedClassId = $request->filled('class_id') ? $request->class_id : null;
        // Handle empty string for "all sections" - use special value to indicate all sections
        $selectedSectionId = $request->has('section_id') && $request->section_id !== '' ? $request->section_id : 'all';
        $selectedDate = $request->filled('date') ? $request->date : now()->toDateString();

        // Check if selected date is a Sunday
        $isSunday = Carbon::parse($selectedDate)->dayOfWeek === Carbon::SUNDAY;

        // Check if selected date is a holiday
        $holiday = null;
        if ($selectedDate) {
            $holiday = $this->attendanceService->getHoliday($selectedDate, $selectedCampusId);
        }

        // Get students for the selected class/section if all required params are present
        $students = [];
        if ($selectedClassId && ($selectedSectionId === 'all' || $selectedSectionId) && ! $isSunday && ! $holiday) {
            $students = $this->attendanceService->getEligibleStudents(
                $selectedClassId,
                $selectedSectionId === 'all' ? null : $selectedSectionId,
                $selectedSessionId,
                $selectedCampusId,
                $selectedDate
            );
        }

        return Inertia::render('attendance/Create', [
            'campuses' => $campuses,
            'sessions' => $sessions,
            'classes' => $classes,
            'sections' => $sections,
            'attendanceStatuses' => $attendanceStatuses,
            'leaveTypes' => $leaveTypes,
            'students' => $students,
            'selectedCampusId' => $selectedCampusId,
            'selectedSessionId' => $selectedSessionId,
            'selectedClassId' => $selectedClassId,
            'selectedSectionId' => $selectedSectionId,
            'selectedDate' => $selectedDate,
            'isSunday' => $isSunday,
            'holiday' => $holiday ? [
                'title' => $holiday->title,
                'start_date' => $holiday->start_date,
                'end_date' => $holiday->end_date,
                'is_national' => $holiday->is_national,
                'campus' => $holiday->campus ? $holiday->campus->name : null,
            ] : null,
        ]);
    }

    /**
     * Get students for a specific class/section for attendance (AJAX).
     */
    public function getStudentsByClassSection(StudentsForAttendanceRequest $request): JsonResponse
    {
        $students = $this->attendanceService->getEligibleStudents(
            $request->class_id,
            $request->section_id,
            $request->session_id,
            $request->campus_id,
            $request->date
        );

        return response()->json([
            'students' => $students,
            'date' => $request->date ?? now()->toDateString(),
        ]);
    }

    /**
     * Store bulk attendance for a class.
     */
    public function storeBulk(StoreBulkAttendanceRequest $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validated();

        /*
         * Saving a register that already exists is editing it, not creating it.
         * `create` is a class-level check that never sees a record, so it cannot
         * see the lock either — which meant a signed-off register could be
         * overwritten by opening the same class and date again. Authorising
         * `update` on each register that would be touched enforces both the
         * permission and the lock, and does so before the transaction opens so
         * a refusal is a 403 rather than a swallowed error message.
         */
        foreach ($this->registersTouchedBy($validated) as $register) {
            $this->authorize('update', $register);
        }

        try {
            DB::transaction(function () use ($validated) {
                $this->processBulkAttendance($validated);

                // Rebuilt from the records that were just written, inside the
                // same transaction: a summary that disagrees with the register
                // is worse than no summary at all.
                $this->refreshDerivedRecords($validated);
            });

            return redirect()->route('attendance.index')
                ->with('success', 'Attendance recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to record attendance: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * The registers a bulk save would write to that already exist.
     *
     * A register for a section that has not been marked yet is a creation and
     * is not returned; only the ones being rewritten need an update check.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Attendance>
     */
    private function registersTouchedBy(array $data)
    {
        $query = $this->registersOn($data['attendance_date'], $data['class_id']);

        if ($this->marksWholeClass($data['section_id'] ?? null)) {
            // Each student is filed under their own section, so every section
            // represented in the payload is in play.
            // The sections as they stood on the day being marked, for the same
            // reason the write path uses them: a back-dated save belongs to the
            // roll of that day.
            $sectionIds = StudentEnrollmentRecord::query()
                ->whereIn('student_id', array_column($data['attendances'], 'student_id'))
                ->coveringDate($data['attendance_date'])
                ->pluck('section_id')
                ->unique();

            /*
             * A class with no sections files its children under a null section,
             * and dropping those — as `filter()` did — left the query looking
             * for nothing, so the lock was never checked for those classes.
             */
            return $query->where(function ($q) use ($sectionIds) {
                $ids = $sectionIds->filter()->all();

                if ($ids !== []) {
                    $q->whereIn('section_id', $ids);
                }

                if ($sectionIds->contains(null)) {
                    $q->orWhereNull('section_id');
                }
            })->get();
        }

        return $this->scopedToSection($query, $data['section_id'] ?? null)->get();
    }

    /**
     * Whether this save covers the class as a whole rather than one section.
     *
     * The form sends zero for "all sections", and nothing at all for a class
     * that has none — both mean each child is filed under their own section,
     * which for a section-less class is no section.
     */
    private function marksWholeClass($sectionId): bool
    {
        return $sectionId === null
            || (int) $sectionId === StoreBulkAttendanceRequest::ALL_SECTIONS;
    }

    /**
     * Narrows a register query to one section, or to the absence of one.
     *
     * `where('section_id', null)` is not the same question as
     * `whereNull('section_id')` in SQL, and a class without sections needs the
     * second.
     */
    private function scopedToSection($query, $sectionId)
    {
        return $sectionId === null
            ? $query->whereNull('section_id')
            : $query->where('section_id', $sectionId);
    }

    /**
     * Brings the monthly summaries and the absence alerts up to date.
     *
     * Both are derived from the register: the summary is recomputed from the
     * month's records rather than adjusted, so a correction to an old day
     * cannot leave it quietly wrong, and an alert is recorded once per child
     * per day however often the register is saved.
     *
     * @param  array<string, mixed>  $data
     */
    private function refreshDerivedRecords(array $data): void
    {
        $registers = $this->registersTouchedBy($data);

        foreach ($registers as $register) {
            $this->summaries->refreshForRegister($register);
            $this->absenceAlerts->recordForRegister($register);
        }
    }

    /**
     * Refuses a date the school is closed on.
     *
     * A holiday the school has marked as a working day — an exam day in the
     * holidays, a make-up class — is allowed through. This lived inline in the
     * bulk path only; the individual path computed the same answer and then
     * threw it away, so the two disagreed about whether a day was workable.
     *
     * @throws \Exception
     */
    private function assertNotAHoliday(string $date, ?int $campusId): void
    {
        if ($this->attendanceService->isAttendanceAllowed($date, $campusId)) {
            return;
        }

        throw new \Exception('Cannot mark attendance on a holiday. Please select another date.');
    }

    /**
     * Registers for a class on a date.
     *
     * Compared with `whereDate`, because the column holds a datetime whose time
     * part is zero: matching it against a plain `Y-m-d` string found nothing,
     * so an existing register was never recognised and the save tried to insert
     * a second one for the same day.
     */
    private function registersOn(string $date, int $classId)
    {
        return Attendance::whereDate('attendance_date', Carbon::parse($date)->toDateString())
            ->where('class_id', $classId);
    }

    /**
     * Process bulk attendance with auto-leave detection and holiday checking.
     * Supports both creating new and updating existing attendance.
     * Refactored to eliminate code duplication.
     */
    private function processBulkAttendance(array $data): Attendance
    {
        $attendanceDate = $data['attendance_date'];
        $campusId = $data['campus_id'];
        $classId = $data['class_id'];
        $sectionId = $data['section_id'];

        // Get status IDs from service
        if ($this->marksWholeClass($sectionId)) {
            return $this->processAllSectionsAttendance($data);
        }

        // Check if attendance already exists for this date/class/section
        $existingAttendance = $this->scopedToSection(
            $this->registersOn($attendanceDate, $classId),
            $sectionId
        )->first();

        if ($existingAttendance) {
            return $this->processAttendanceUpdate($existingAttendance, $data['attendances']);
        }

        $this->assertNotAHoliday($attendanceDate, $campusId);

        // Create new attendance record
        $attendance = Attendance::create([
            'attendance_date' => $attendanceDate,
            'campus_id' => $campusId,
            'session_id' => $data['session_id'],
            'class_id' => $classId,
            'section_id' => $sectionId,
            'taken_by' => auth()->id(),
            'is_locked' => false,
        ]);

        return $this->processAttendanceCreation($attendance, $data['attendances']);
    }

    /**
     * Process attendance for all sections of a class.
     */
    private function processAllSectionsAttendance(array $data): Attendance
    {
        $attendanceDate = $data['attendance_date'];
        $classId = $data['class_id'];
        $firstAttendance = null;

        foreach ($data['attendances'] as $studentAttendance) {
            $studentId = $studentAttendance['student_id'];
            $statusId = $studentAttendance['attendance_status_id'];
            $checkIn = $studentAttendance['check_in'] ?? null;
            $checkOut = $studentAttendance['check_out'] ?? null;
            $remarks = $studentAttendance['remarks'] ?? null;
            $leaveTypeId = $studentAttendance['leave_type_id'] ?? null;

            /*
             * The section the child was in **on the day being marked**. Taking
             * the open enrollment instead filed a back-dated register under
             * wherever they sit today, so a child who moved from 5-A to 5-B in
             * November had September's register written against 5-B.
             */
            $enrollment = StudentEnrollmentRecord::where('student_id', $studentId)
                ->coveringDate($attendanceDate)
                ->orderByDesc('admission_date')
                ->first();

            if (! $enrollment) {
                continue;
            }

            $studentSectionId = $enrollment->section_id;

            // Check if attendance record exists for this section
            $attendance = $this->scopedToSection(
                $this->registersOn($attendanceDate, $classId),
                $studentSectionId
            )->first();

            // If not exists, create new attendance record
            if (! $attendance) {
                // The whole-class path never checked at all.
                $this->assertNotAHoliday($attendanceDate, $data['campus_id']);

                $attendance = Attendance::create([
                    'attendance_date' => $attendanceDate,
                    'campus_id' => $data['campus_id'],
                    'session_id' => $data['session_id'],
                    'class_id' => $classId,
                    'section_id' => $studentSectionId,
                    'taken_by' => auth()->id(),
                    'is_locked' => false,
                ]);

                if ($firstAttendance === null) {
                    $firstAttendance = $attendance;
                }
            }

            $this->upsertStudentAttendance(
                $attendance->id,
                $studentId,
                $statusId,
                $leaveTypeId,
                $checkIn,
                $checkOut,
                $remarks,
                $attendanceDate,
                $attendance
            );
        }

        return $firstAttendance ?? new Attendance;
    }

    /**
     * Process attendance update for existing records.
     */
    private function processAttendanceUpdate(Attendance $attendance, array $studentAttendances): Attendance
    {
        foreach ($studentAttendances as $studentAttendance) {
            $this->upsertStudentAttendance(
                $attendance->id,
                $studentAttendance['student_id'],
                $studentAttendance['attendance_status_id'],
                $studentAttendance['leave_type_id'] ?? null,
                $studentAttendance['check_in'] ?? null,
                $studentAttendance['check_out'] ?? null,
                $studentAttendance['remarks'] ?? null,
                $attendance->attendance_date,
                $attendance
            );
        }

        return $attendance;
    }

    /**
     * Process attendance creation for new records.
     */
    private function processAttendanceCreation(Attendance $attendance, array $studentAttendances): Attendance
    {
        foreach ($studentAttendances as $studentAttendance) {
            $studentId = $studentAttendance['student_id'];
            $checkIn = $studentAttendance['check_in'] ?? null;

            // A child marked present who walked in after the deadline is late,
            // against whichever clock the campus is running this month.
            $statusId = $this->lateArrivals->resolveForRegister(
                $attendance,
                (int) $studentAttendance['attendance_status_id'],
                $checkIn
            );

            // Auto-detect approved leaves using service
            $studentLeaveId = null;
            if ($this->attendanceService->isLeaveStatus($statusId)) {
                $leave = $this->attendanceService->detectStudentLeave(
                    $studentId,
                    $attendance->attendance_date
                );
                $studentLeaveId = $leave?->id;
            }

            // Create attendance student record
            AttendanceStudent::create([
                'attendance_id' => $attendance->id,
                'student_id' => $studentId,
                'attendance_status_id' => $statusId,
                'student_leave_id' => $studentLeaveId,
                'leave_type_id' => $studentAttendance['leave_type_id'] ?? null,
                'check_in' => $studentAttendance['check_in'] ?? null,
                'check_out' => $studentAttendance['check_out'] ?? null,
                'remarks' => $studentAttendance['remarks'] ?? null,
            ]);
        }

        return $attendance;
    }

    /**
     * Upsert student attendance record (create or update).
     * This is the core logic extracted to avoid duplication.
     */
    private function upsertStudentAttendance(
        int $attendanceId,
        int $studentId,
        int $statusId,
        ?int $leaveTypeId,
        ?string $checkIn,
        ?string $checkOut,
        ?string $remarks,
        string $attendanceDate,
        ?Attendance $register = null
    ): void {
        if ($register) {
            $statusId = $this->lateArrivals->resolveForRegister($register, $statusId, $checkIn);
        }

        // Auto-detect approved leaves using service
        $studentLeaveId = null;
        if ($this->attendanceService->isLeaveStatus($statusId)) {
            $leave = $this->attendanceService->detectStudentLeave($studentId, $attendanceDate);
            $studentLeaveId = $leave?->id;
        }

        // Keyed on the pair the unique constraint covers, so a second save for
        // the same student updates the row it finds instead of racing another
        // request to insert a duplicate.
        AttendanceStudent::updateOrCreate(
            [
                'attendance_id' => $attendanceId,
                'student_id' => $studentId,
            ],
            [
                'attendance_status_id' => $statusId,
                'student_leave_id' => $studentLeaveId,
                'leave_type_id' => $leaveTypeId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'remarks' => $remarks,
            ]
        );
    }

    /**
     * Show the attendance for a specific date/class/section.
     */
    public function show(Attendance $attendance): Response
    {
        $this->authorize('view', $attendance);

        $attendance->load(['campus', 'session', 'class', 'section', 'takenBy']);

        $attendanceStudents = AttendanceStudent::with(['student.user', 'attendanceStatus', 'studentLeave'])
            ->where('attendance_id', $attendance->id)
            ->orderBy('id')
            ->get();

        $attendanceStatuses = AttendanceStatus::all();

        return Inertia::render('attendance/Show', [
            'attendance' => $attendance,
            'attendanceStudents' => $attendanceStudents,
            'attendanceStatuses' => $attendanceStatuses,
        ]);
    }

    /**
     * Show the form for editing attendance.
     */
    public function edit(Attendance $attendance): Response|RedirectResponse
    {
        $this->authorize('update', $attendance);

        // The guard below returns a redirect, so the signature has to allow one.
        // Declared as `Response` alone, clicking edit on a locked register threw
        // a TypeError instead of showing the message written for it.
        if ($attendance->is_locked) {
            return redirect()->route('attendance.index')
                ->with('error', 'Cannot edit locked attendance record');
        }

        $attendance->load(['campus', 'session', 'class', 'section', 'takenBy']);

        $attendanceStudents = AttendanceStudent::with(['student.user', 'attendanceStatus', 'studentLeave'])
            ->where('attendance_id', $attendance->id)
            ->orderBy('id')
            ->get();

        $attendanceStatuses = AttendanceStatus::all();

        return Inertia::render('attendance/Edit', [
            'attendance' => $attendance,
            'attendanceStudents' => $attendanceStudents,
            'attendanceStatuses' => $attendanceStatuses,
        ]);
    }

    /**
     * Update attendance for a class.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $attendance) {
                foreach ($validated['attendances'] as $studentAttendance) {
                    // Scoped again at the query, so the rule above cannot be the
                    // only thing standing between one class and another's marks.
                    $attendanceStudent = $attendance->attendanceStudents()
                        ->findOrFail($studentAttendance['id']);

                    $statusId = $this->lateArrivals->resolveForRegister(
                        $attendance,
                        (int) $studentAttendance['attendance_status_id'],
                        $studentAttendance['check_in'] ?? null
                    );

                    // Auto-detect leave using service
                    $studentLeaveId = null;
                    if ($this->attendanceService->isLeaveStatus($statusId)) {
                        $leave = $this->attendanceService->detectStudentLeave(
                            $studentAttendance['student_id'],
                            $attendance->attendance_date
                        );
                        $studentLeaveId = $leave?->id;
                    }

                    $attendanceStudent->update([
                        'attendance_status_id' => $statusId,
                        'student_leave_id' => $studentLeaveId,
                        'check_in' => $studentAttendance['check_in'] ?? null,
                        'check_out' => $studentAttendance['check_out'] ?? null,
                        'remarks' => $studentAttendance['remarks'] ?? null,
                    ]);
                }
            });

            return redirect()->route('attendance.index')
                ->with('success', 'Attendance updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update attendance: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Lock attendance to prevent further modifications.
     */
    public function lock(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('lock', $attendance);

        try {
            if ($attendance->is_locked) {
                return redirect()->back()->with('error', 'Attendance is already locked');
            }

            $attendance->lock();

            return redirect()->back()->with('success', 'Attendance locked successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to lock attendance: '.$e->getMessage());
        }
    }

    /**
     * Unlock attendance to allow modifications.
     */
    public function unlock(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('unlock', $attendance);

        try {
            if (! $attendance->is_locked) {
                return redirect()->back()->with('error', 'Attendance is already unlocked');
            }

            $attendance->unlock();

            return redirect()->back()->with('success', 'Attendance unlocked successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unlock attendance: '.$e->getMessage());
        }
    }

    /**
     * Delete attendance record.
     */
    public function destroy(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);

        try {
            if ($attendance->is_locked) {
                return redirect()->back()->with('error', 'Cannot delete locked attendance record');
            }

            $attendance->delete();

            return redirect()->route('attendance.index')
                ->with('success', 'Attendance deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete attendance: '.$e->getMessage());
        }
    }

    /**
     * Get attendance report for a student.
     */
    public function studentReport(AttendanceReportRequest $request, Student $student): Response
    {
        $this->authorize('viewReports', Attendance::class);

        /*
         * The route is `/student/{student}/report` and binds the child. The
         * method used to ignore that and require a `student_id` in the query
         * string instead, so calling the route as it is named failed
         * validation and the bound model was fetched a second time by hand.
         */
        $student->load(['user', 'currentEnrollment']);

        $startDate = $request->startOfMonth();
        $endDate = $request->endOfMonth();

        $attendanceRecords = AttendanceStudent::with(['attendance', 'attendanceStatus'])
            ->where('student_id', $student->id)
            ->whereHas('attendance', function ($q) use ($startDate, $endDate) {
                $q->whereDate('attendance_date', '>=', $startDate->toDateString())
                    ->whereDate('attendance_date', '<=', $endDate->toDateString());
            })
            ->orderBy('id')
            ->get();

        // Calculate statistics using service
        $stats = $this->attendanceService->calculateStats($attendanceRecords);

        // The placement they held during the month reported on, so a child who
        // has since left still has the months they were present.
        $enrollment = $student->enrollmentRecords()
            ->overlappingPeriod($startDate, $endDate)
            ->orderByDesc('admission_date')
            ->first();

        // The same denominator the class report uses, so the two agree.
        $expectedDays = $enrollment
            ? $this->workingDays->expectedDaysFor($enrollment, $startDate, $endDate)
            : 0;

        return Inertia::render('attendance/StudentReport', [
            'student' => $student,
            'attendanceRecords' => $attendanceRecords,
            'stats' => $stats,
            'expectedDays' => $expectedDays,
            'percentage' => $expectedDays > 0
                ? round(($stats['present_equivalent'] / $expectedDays) * 100, 2)
                : 0.0,
            'unmarkedDays' => max($expectedDays - $stats['total'], 0),
            'month' => $request->month(),
            'year' => $request->year(),
        ]);
    }

    /**
     * Get attendance summary report for a class.
     */
    public function classReport(Request $request): Response
    {
        $this->authorize('viewReports', Attendance::class);

        /*
         * A teacher may report on their own classes and no others. The
         * permission says they may run a report; this says on whom — the same
         * separation the policy makes for a register.
         */
        $this->assertMayReportOn($request->user(), $request->class_id, $request->section_id);

        $classes = $this->reportableClasses($request->user());
        $sections = $request->filled('class_id')
            ? Section::where('class_id', $request->class_id)->orderBy('name')->get(['id', 'name'])
            : [];

        // If no class_id provided, show empty report form
        if (! $request->filled('class_id')) {
            return Inertia::render('attendance/ClassReport', [
                'class' => null,
                'sections' => [],
                'summary' => [],
                'month' => $request->month ?? now()->month,
                'year' => $request->year ?? now()->year,
                'classes' => $classes,
                'selectedClassId' => null,
                'selectedSectionId' => null,
            ]);
        }

        // Validate required parameters
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $class = SchoolClass::with('sections')->findOrFail($request->class_id);

        $startDate = now()->setDate($request->year, $request->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        /*
         * Every child who was on this class's roll at any point in the month,
         * not only the ones still on it. Reading `currentEnrollment` meant a
         * child who left in the middle of the month vanished from the report
         * entirely, taking the weeks they *were* present with them — which is
         * exactly the history the enrollment periods exist to keep.
         */
        $students = Student::with([
            'user',
            'studentGuardians.guardian.user',
            'enrollmentRecords' => fn ($q) => $q->overlappingPeriod($startDate, $endDate)
                ->with(['campus', 'class', 'section'])
                ->orderByDesc('admission_date'),
        ])
            ->whereHas('enrollmentRecords', function ($q) use ($request, $startDate, $endDate) {
                $q->overlappingPeriod($startDate, $endDate)
                    ->where('class_id', $request->class_id);

                if ($request->filled('section_id')) {
                    $q->where('section_id', $request->section_id);
                }
            })
            ->orderBy('registration_no')
            ->get();

        // Optimize: Get all attendance records in one query
        $studentIds = $students->pluck('id')->toArray();

        $allAttendanceRecords = AttendanceStudent::with(['attendanceStatus'])
            ->whereIn('student_id', $studentIds)
            ->whereHas('attendance', function ($q) use ($startDate, $endDate) {
                $q->whereDate('attendance_date', '>=', $startDate->toDateString())
                    ->whereDate('attendance_date', '<=', $endDate->toDateString());
            })
            ->get()
            ->groupBy('student_id');

        $summary = $students->map(function ($student) use ($allAttendanceRecords, $startDate, $endDate) {
            $studentRecords = $allAttendanceRecords->get($student->id, collect());

            // Calculate stats using service
            $stats = $this->attendanceService->calculateStats($studentRecords);

            // Get guardian info
            $guardianInfo = $this->getGuardianInfo($student);

            // The placement they held during the month being reported on.
            $enrollment = $student->enrollmentRecords->first();
            $enrollmentInfo = $enrollment ? (
                ($enrollment->campus ? $enrollment->campus->name : '').' / '.
                ($enrollment->class ? $enrollment->class->name : '').' / '.
                ($enrollment->section ? $enrollment->section->name : '')
            ) : '';

            /*
             * The days this child was actually expected — the campus's working
             * weekdays, less holidays, less anything outside their enrollment.
             * `total` was the count of days somebody happened to mark, so a
             * child marked on three days out of twenty-two read as 100%.
             */
            $expectedDays = $enrollment
                ? $this->workingDays->expectedDaysFor($enrollment, $startDate, $endDate)
                : 0;

            // Weighted, so a half day counts as half.
            $presentEquivalent = round(
                $studentRecords->sum(fn ($record) => $record->attendanceStatus?->presentWeight() ?? 0),
                2
            );

            return [
                'student' => $student,
                'registration_no' => $student->registration_no,
                'name' => $student->name,
                'present' => $stats['present'],
                'absent' => $stats['absent'],
                'leave' => $stats['leave'],
                'late' => $stats['late'],
                'half_day' => $stats['half_day'],
                'total' => $stats['total'],
                'expected_days' => $expectedDays,
                'present_equivalent' => $presentEquivalent,
                'percentage' => $expectedDays > 0
                    ? round(($presentEquivalent / $expectedDays) * 100, 2)
                    : 0.0,
                'unmarked_days' => max($expectedDays - $studentRecords->count(), 0),
                'guardian_info' => $guardianInfo,
                'enrollment_info' => $enrollmentInfo,
            ];
        });

        return Inertia::render('attendance/ClassReport', [
            'class' => $class,
            'sections' => $sections,
            'summary' => $summary,
            'month' => $request->month,
            'year' => $request->year,
            'classes' => $classes,
            'selectedClassId' => $request->class_id,
            'selectedSectionId' => $request->section_id,
        ]);
    }

    /**
     * Refuses a report on a class the user is not responsible for.
     *
     * @throws AuthorizationException
     */
    private function assertMayReportOn(?User $user, $classId, $sectionId): void
    {
        if (! $user || ! $classId || ! $user->isClassRestricted()) {
            return;
        }

        abort_unless(
            $user->teachesSection((int) $classId, $sectionId ? (int) $sectionId : null),
            403,
            'You may only report on your own classes.'
        );
    }

    /**
     * The classes a user may pick from on a report screen.
     *
     * Offering every class to a teacher who may open only one is a menu of
     * things that will be refused.
     *
     * @return \Illuminate\Support\Collection<int, SchoolClass>
     */
    private function reportableClasses(?User $user)
    {
        $classes = SchoolClass::orderBy('name');

        if ($user && $user->isClassRestricted()) {
            $classes->whereIn(
                'id',
                $user->teachingAssignments()->active()->pluck('class_id')->unique()
            );
        }

        return $classes->get(['id', 'name']);
    }

    /**
     * Get guardian information for a student.
     */
    private function getGuardianInfo(Student $student): string
    {
        $guardians = $student->studentGuardians;

        if ($guardians->isEmpty()) {
            return '-';
        }

        // The pivot column is `is_primary`, a boolean. Matching a `type` column
        // that does not exist meant this never found anyone, and the report
        // quietly showed whichever guardian happened to be first — on a class
        // report, the number the office rings when a child is absent.
        $primaryGuardian = $guardians->where('is_primary', true)->first();

        if ($primaryGuardian && $primaryGuardian->guardian && $primaryGuardian->guardian->user) {
            $name = $primaryGuardian->guardian->user->name;
            $phone = $primaryGuardian->guardian->phone ?? '';

            return $name.' - '.$phone;
        }

        // Fall back to any guardian
        $otherGuardian = $guardians->first();
        if ($otherGuardian && $otherGuardian->guardian && $otherGuardian->guardian->user) {
            $name = $otherGuardian->guardian->user->name;
            $phone = $otherGuardian->guardian->phone ?? '';

            return $name.' - '.$phone;
        }

        return '-';
    }

    /**
     * Check if a date is a holiday for a campus.
     */
    public function checkHoliday(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        $holiday = $this->attendanceService->getHoliday($request->date, $request->campus_id);

        return response()->json([
            // A holiday the school has marked as a working day is workable, and
            // the save now accepts it. Reporting the bare existence of a holiday
            // row had the screen blocking a date the backend would take.
            'is_holiday' => ! $this->attendanceService->isAttendanceAllowed($request->date, $request->campus_id),
            'attendance_allowed' => $this->attendanceService->isAttendanceAllowed($request->date, $request->campus_id),
            'holiday' => $holiday ? [
                'title' => $holiday->title,
                'start_date' => $holiday->start_date,
                'end_date' => $holiday->end_date,
            ] : null,
        ]);
    }
}
