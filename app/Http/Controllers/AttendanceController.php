<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use App\Models\AttendanceStatus;
use App\Models\Campus;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Policies\AttendancePolicy;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with(['campus', 'session', 'class', 'section', 'takenBy']);

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
        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
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
     * Show the form for creating attendance.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Attendance::class);

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $sessions = Session::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $classes = SchoolClass::orderBy('id')->get(['id', 'name']);
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
        $isSunday = \Carbon\Carbon::parse($selectedDate)->dayOfWeek === \Carbon\Carbon::SUNDAY;

        // Check if selected date is a holiday
        $holiday = null;
        if ($selectedDate) {
            $holiday = $this->attendanceService->getHoliday($selectedDate, $selectedCampusId);
        }

        // Get students for the selected class/section if all required params are present
        $students = [];
        if ($selectedClassId && ($selectedSectionId === 'all' || $selectedSectionId) && !$isSunday && !$holiday) {
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
    public function getStudentsByClassSection(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'nullable|exists:academic_sessions,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $students = $this->attendanceService->getEligibleStudents(
            $request->class_id,
            $request->section_id,
            $request->session_id,
            $request->campus_id
        );

        return response()->json([
            'students' => $students,
            'date' => $request->date ?? now()->toDateString(),
        ]);
    }

    /**
     * Store bulk attendance for a class.
     */
    public function storeBulk(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'campus_id' => 'required|exists:campuses,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'required',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.attendance_status_id' => 'required|exists:attendance_statuses,id',
            'attendances.*.leave_type_id' => 'nullable|exists:leave_types,id',
            'attendances.*.check_in' => 'nullable|date_format:H:i',
            'attendances.*.check_out' => 'nullable|date_format:H:i',
            'attendances.*.remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $this->processBulkAttendance($validated);
            });

            return redirect()->route('attendance.index')
                ->with('success', 'Attendance recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to record attendance: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process bulk attendance with auto-leave detection and holiday checking.
     * Supports both creating new and updating existing attendance.
     */
    private function processBulkAttendance(array $data): Attendance
    {
        $attendanceDate = $data['attendance_date'];
        $campusId = $data['campus_id'];
        $classId = $data['class_id'];
        $sectionId = $data['section_id'];

        // Get status IDs from service
        $statusIds = $this->attendanceService->getStatusIds();

        // For section_id = 0, it means "all sections" - we need to handle differently
        if ($sectionId == 0) {
            // Get all sections for this class
            $sections = Section::where('class_id', $classId)->get();
            
            // Process each student attendance
            foreach ($data['attendances'] as $studentAttendance) {
                $studentId = $studentAttendance['student_id'];
                $statusId = $studentAttendance['attendance_status_id'];
                $checkIn = $studentAttendance['check_in'] ?? null;
                $checkOut = $studentAttendance['check_out'] ?? null;
                $remarks = $studentAttendance['remarks'] ?? null;
                $leaveTypeId = $studentAttendance['leave_type_id'] ?? null;

                // Get student's current section
                $student = Student::with('currentEnrollment')->find($studentId);
                if (!$student || !$student->currentEnrollment) {
                    continue;
                }

                $studentSectionId = $student->currentEnrollment->section_id;

                // Check if attendance record exists for this section
                $attendance = Attendance::where('attendance_date', $attendanceDate)
                    ->where('class_id', $classId)
                    ->where('section_id', $studentSectionId)
                    ->first();

                // If not exists, create new attendance record
                if (!$attendance) {
                    $attendance = Attendance::create([
                        'attendance_date' => $attendanceDate,
                        'campus_id' => $campusId,
                        'session_id' => $data['session_id'],
                        'class_id' => $classId,
                        'section_id' => $studentSectionId,
                        'taken_by' => auth()->id(),
                        'is_locked' => false,
                    ]);
                }

                // Check if student already has attendance in this record
                $existingRecord = AttendanceStudent::where('attendance_id', $attendance->id)
                    ->where('student_id', $studentId)
                    ->first();

                // Auto-detect approved leaves using service
                $studentLeaveId = null;
                if ($statusIds['leave'] && $statusId === $statusIds['leave']) {
                    $leave = $this->attendanceService->detectStudentLeave($studentId, $attendanceDate);
                    $studentLeaveId = $leave?->id;
                }

                if ($existingRecord) {
                    // Update existing record
                    $existingRecord->update([
                        'attendance_status_id' => $statusId,
                        'student_leave_id' => $studentLeaveId,
                        'leave_type_id' => $leaveTypeId,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'remarks' => $remarks,
                    ]);
                } else {
                    // Create new attendance student record
                    AttendanceStudent::create([
                        'attendance_id' => $attendance->id,
                        'student_id' => $studentId,
                        'attendance_status_id' => $statusId,
                        'student_leave_id' => $studentLeaveId,
                        'leave_type_id' => $leaveTypeId,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'remarks' => $remarks,
                    ]);
                }
            }

            // Return any attendance record (just for returning)
            return Attendance::where('attendance_date', $attendanceDate)
                ->where('class_id', $classId)
                ->first() ?? new Attendance();
        }

        // Original logic for single section
        // Check if attendance already exists for this date/class/section
        $existingAttendance = Attendance::where('attendance_date', $attendanceDate)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->first();

        if ($existingAttendance) {
            // Update existing attendance - process each student
            foreach ($data['attendances'] as $studentAttendance) {
                $studentId = $studentAttendance['student_id'];
                $statusId = $studentAttendance['attendance_status_id'];
                $checkIn = $studentAttendance['check_in'] ?? null;
                $checkOut = $studentAttendance['check_out'] ?? null;
                $remarks = $studentAttendance['remarks'] ?? null;
                $leaveTypeId = $studentAttendance['leave_type_id'] ?? null;

                // Check if student already has attendance in this record
                $existingRecord = AttendanceStudent::where('attendance_id', $existingAttendance->id)
                    ->where('student_id', $studentId)
                    ->first();

                // Auto-detect approved leaves using service
                $studentLeaveId = null;
                if ($statusIds['leave'] && $statusId === $statusIds['leave']) {
                    $leave = $this->attendanceService->detectStudentLeave($studentId, $attendanceDate);
                    $studentLeaveId = $leave?->id;
                }

                if ($existingRecord) {
                    // Update existing record
                    $existingRecord->update([
                        'attendance_status_id' => $statusId,
                        'student_leave_id' => $studentLeaveId,
                        'leave_type_id' => $leaveTypeId,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'remarks' => $remarks,
                    ]);
                } else {
                    // Create new attendance student record
                    AttendanceStudent::create([
                        'attendance_id' => $existingAttendance->id,
                        'student_id' => $studentId,
                        'attendance_status_id' => $statusId,
                        'student_leave_id' => $studentLeaveId,
                        'leave_type_id' => $leaveTypeId,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'remarks' => $remarks,
                    ]);
                }
            }

            return $existingAttendance;
        }

        // Check if date is a holiday using service
        $isHoliday = $this->attendanceService->isHoliday($attendanceDate, $campusId);

        if ($isHoliday) {
            // You may choose to throw an exception or allow recording with holiday status
            // For now, we'll proceed but could mark all as Holiday
        }

        // Create or get attendance record
        $attendance = Attendance::create([
            'attendance_date' => $attendanceDate,
            'campus_id' => $campusId,
            'session_id' => $data['session_id'],
            'class_id' => $classId,
            'section_id' => $sectionId,
            'taken_by' => auth()->id(),
            'is_locked' => false,
        ]);

        // Process each student attendance
        foreach ($data['attendances'] as $studentAttendance) {
            $studentId = $studentAttendance['student_id'];
            $statusId = $studentAttendance['attendance_status_id'];

            // Auto-detect approved leaves using service
            $studentLeaveId = null;
            if ($statusIds['leave'] && $statusId === $statusIds['leave']) {
                $leave = $this->attendanceService->detectStudentLeave($studentId, $attendanceDate);
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
     * Store individual student attendance.
     */
    public function storeIndividual(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'attendance_date' => 'required|date',
            'attendance_status_id' => 'required|exists:attendance_statuses,id',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Validate check-in/check-out times
        $timeErrors = $this->attendanceService->validateTimes(
            $validated['check_in'] ?? null,
            $validated['check_out'] ?? null
        );

        if (!empty($timeErrors)) {
            return redirect()->back()
                ->withErrors(['check_time' => $timeErrors[0]])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($validated) {
                // Get student's current enrollment
                $student = Student::with('currentEnrollment')->findOrFail($validated['student_id']);

                if (!$student->currentEnrollment) {
                    throw new \Exception('Student has no active enrollment');
                }

                $enrollment = $student->currentEnrollment;

                // Check if attendance already exists for this student/date
                $existingRecord = AttendanceStudent::where('student_id', $validated['student_id'])
                    ->whereHas('attendance', function ($q) use ($validated) {
                        $q->where('attendance_date', $validated['attendance_date']);
                    })
                    ->first();

                if ($existingRecord) {
                    throw new \Exception('Attendance already exists for this student on the selected date');
                }

                // Check for holidays using service
                $isHoliday = $this->attendanceService->isHoliday($validated['attendance_date'], $enrollment->campus_id);

                // Find or create attendance record
                $attendance = Attendance::firstOrCreate(
                    [
                        'attendance_date' => $validated['attendance_date'],
                        'class_id' => $enrollment->class_id,
                        'section_id' => $enrollment->section_id,
                    ],
                    [
                        'campus_id' => $enrollment->campus_id,
                        'session_id' => $enrollment->session_id,
                        'taken_by' => auth()->id(),
                        'is_locked' => false,
                    ]
                );

                // Check if student already marked in this attendance
                $existingInAttendance = AttendanceStudent::where('attendance_id', $attendance->id)
                    ->where('student_id', $validated['student_id'])
                    ->first();

                if ($existingInAttendance) {
                    throw new \Exception('Student already marked in attendance for this date');
                }

                // Auto-detect leave using service
                $studentLeaveId = null;
                $leaveStatusId = $this->attendanceService->getStatusId('L');

                if ($leaveStatusId && $validated['attendance_status_id'] === $leaveStatusId) {
                    $leave = $this->attendanceService->detectStudentLeave(
                        $validated['student_id'],
                        $validated['attendance_date']
                    );
                    $studentLeaveId = $leave?->id;
                }

                // Create attendance record
                AttendanceStudent::create([
                    'attendance_id' => $attendance->id,
                    'student_id' => $validated['student_id'],
                    'attendance_status_id' => $validated['attendance_status_id'],
                    'student_leave_id' => $studentLeaveId,
                    'check_in' => $validated['check_in'] ?? null,
                    'check_out' => $validated['check_out'] ?? null,
                    'remarks' => $validated['remarks'] ?? null,
                ]);
            });

            return redirect()->back()->with('success', 'Attendance recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to record attendance: ' . $e->getMessage())
                ->withInput();
        }
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
    public function edit(Attendance $attendance): Response
    {
        $this->authorize('update', $attendance);

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
    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.id' => 'required|exists:attendance_students,id',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.attendance_status_id' => 'required|exists:attendance_statuses,id',
            'attendances.*.check_in' => 'nullable|date_format:H:i',
            'attendances.*.check_out' => 'nullable|date_format:H:i',
            'attendances.*.remarks' => 'nullable|string|max:500',
        ]);

        // Validate check-in/check-out times for each attendance
        foreach ($validated['attendances'] as $studentAttendance) {
            $timeErrors = $this->attendanceService->validateTimes(
                $studentAttendance['check_in'] ?? null,
                $studentAttendance['check_out'] ?? null
            );

            if (!empty($timeErrors)) {
                return redirect()->back()
                    ->withErrors(['check_time' => $timeErrors[0]])
                    ->withInput();
            }
        }

        try {
            DB::transaction(function () use ($validated, $attendance) {
                $leaveStatusId = $this->attendanceService->getStatusId('L');

                foreach ($validated['attendances'] as $studentAttendance) {
                    $attendanceStudent = AttendanceStudent::findOrFail($studentAttendance['id']);

                    // Auto-detect leave using service
                    $studentLeaveId = null;
                    if ($leaveStatusId && $studentAttendance['attendance_status_id'] === $leaveStatusId) {
                        $leave = $this->attendanceService->detectStudentLeave(
                            $studentAttendance['student_id'],
                            $attendance->attendance_date
                        );
                        $studentLeaveId = $leave?->id;
                    }

                    $attendanceStudent->update([
                        'attendance_status_id' => $studentAttendance['attendance_status_id'],
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
                ->with('error', 'Failed to update attendance: ' . $e->getMessage())
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
                ->with('error', 'Failed to lock attendance: ' . $e->getMessage());
        }
    }

    /**
     * Unlock attendance to allow modifications.
     */
    public function unlock(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('unlock', $attendance);

        try {
            if (!$attendance->is_locked) {
                return redirect()->back()->with('error', 'Attendance is already unlocked');
            }

            $attendance->unlock();

            return redirect()->back()->with('success', 'Attendance unlocked successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unlock attendance: ' . $e->getMessage());
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
                ->with('error', 'Failed to delete attendance: ' . $e->getMessage());
        }
    }

    /**
     * Get attendance report for a student.
     */
    public function studentReport(Request $request): Response
    {
        $this->authorize('viewReports', Attendance::class);

        $request->validate([
            'student_id' => 'required|exists:students,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $student = Student::with(['user', 'currentEnrollment'])
            ->findOrFail($request->student_id);

        $startDate = now()->setDate($request->year, $request->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendanceRecords = AttendanceStudent::with(['attendance', 'attendanceStatus'])
            ->where('student_id', $request->student_id)
            ->whereHas('attendance', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('attendance_date', [$startDate, $endDate]);
            })
            ->orderBy('id')
            ->get();

        // Calculate statistics using service
        $stats = $this->attendanceService->calculateStats($attendanceRecords);

        return Inertia::render('attendance/StudentReport', [
            'student' => $student,
            'attendanceRecords' => $attendanceRecords,
            'stats' => $stats,
            'month' => $request->month,
            'year' => $request->year,
        ]);
    }

    /**
     * Get attendance summary report for a class.
     */
    public function classReport(Request $request): Response
    {
        $this->authorize('viewReports', Attendance::class);

        $classes = SchoolClass::orderBy('name')->get(['id', 'name']);
        $sections = $request->filled('class_id')
            ? Section::where('class_id', $request->class_id)->orderBy('name')->get(['id', 'name'])
            : [];

        // If no class_id provided, show empty report form
        if (!$request->filled('class_id')) {
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

        $students = Student::with(['user', 'currentEnrollment.campus', 'currentEnrollment.section', 'studentGuardians.guardian.user'])
            ->whereHas('currentEnrollment', function ($q) use ($request) {
                $q->where('class_id', $request->class_id)
                  ->whereNull('leave_date');

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
                $q->whereBetween('attendance_date', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('student_id');

        $summary = $students->map(function ($student) use ($allAttendanceRecords) {
            $studentRecords = $allAttendanceRecords->get($student->id, collect());
            
            // Calculate stats using service
            $stats = $this->attendanceService->calculateStats($studentRecords);

            // Get guardian info
            $guardianInfo = $this->getGuardianInfo($student);

            // Get enrollment info
            $enrollment = $student->currentEnrollment;
            $enrollmentInfo = $enrollment ? (
                ($enrollment->campus ? $enrollment->campus->name : '') . ' / ' .
                ($enrollment->class ? $enrollment->class->name : '') . ' / ' .
                ($enrollment->section ? $enrollment->section->name : '')
            ) : '';

            return [
                'student' => $student,
                'registration_no' => $student->registration_no,
                'name' => $student->name,
                'present' => $stats['present'],
                'absent' => $stats['absent'],
                'leave' => $stats['leave'],
                'late' => $stats['late'],
                'total' => $stats['total'],
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
     * Get guardian information for a student.
     */
    private function getGuardianInfo(Student $student): string
    {
        $guardians = $student->studentGuardians;
        
        if ($guardians->isEmpty()) {
            return '-';
        }
        
        // Try to find primary guardian first
        $primaryGuardian = $guardians->where('type', 'primary')->first();
        
        if ($primaryGuardian && $primaryGuardian->guardian && $primaryGuardian->guardian->user) {
            $name = $primaryGuardian->guardian->user->name;
            $phone = $primaryGuardian->guardian->phone ?? '';
            return $name . ' - ' . $phone;
        }
        
        // Fall back to any guardian
        $otherGuardian = $guardians->first();
        if ($otherGuardian && $otherGuardian->guardian && $otherGuardian->guardian->user) {
            $name = $otherGuardian->guardian->user->name;
            $phone = $otherGuardian->guardian->phone ?? '';
            return $name . ' - ' . $phone;
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
            'is_holiday' => $holiday !== null,
            'holiday' => $holiday ? [
                'title' => $holiday->title,
                'start_date' => $holiday->start_date,
                'end_date' => $holiday->end_date,
            ] : null,
        ]);
    }
}
