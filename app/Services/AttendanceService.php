<?php

namespace App\Services;

use App\Enums\AttendanceStatusCode;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\AttendanceStudent;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\StudentLeave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

class AttendanceService
{
    /**
     * Cache for attendance status IDs.
     */
    private ?array $statusIds = null;

    /**
     * Cache TTL in minutes.
     */
    private const CACHE_TTL = 60; // 1 hour

    /**
     * Bumped to retire every cached holiday answer at once. See
     * `forgetHolidays()`.
     */
    private const HOLIDAY_VERSION_KEY = 'attendance:holiday-cache-version';

    /**
     * The seeded status codes and the names this service knows them by.
     */
    private const CODE_TO_NAME = [
        AttendanceStatusCode::PRESENT->value => 'present',
        AttendanceStatusCode::ABSENT->value => 'absent',
        AttendanceStatusCode::LEAVE->value => 'leave',
        AttendanceStatusCode::LATE->value => 'late',
    ];

    /**
     * Check if a date is a holiday for a campus.
     * Returns false if attendance is allowed on the holiday.
     */
    public function isHoliday(string $date, ?int $campusId = null): bool
    {
        $holiday = $this->getHoliday($date, $campusId);

        // If holiday exists but attendance is allowed, treat it as not a holiday
        if ($holiday && $holiday->isAttendanceAllowed()) {
            return false;
        }

        return $holiday !== null;
    }

    /**
     * Get holiday details for a date and campus.
     * Uses caching for performance.
     * Includes support for recurring holidays.
     */
    public function getHoliday(string $date, ?int $campusId = null): ?Holiday
    {
        $cacheKey = 'holiday:v'.$this->holidayCacheVersion().":{$date}:".($campusId ?? 'national');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $campusId) {
            /*
             * Non-recurring holidays first, compared with `whereDate`: the
             * columns are cast to dates and hold a datetime whose time part is
             * zero, so `start_date <= '2026-04-06'` was false for a holiday
             * that starts on that very day.
             */
            $holiday = Holiday::where(function ($query) use ($date, $campusId) {
                $query->where('is_national', true)
                    ->orWhere(function ($q) use ($date, $campusId) {
                        if ($campusId) {
                            $q->where('campus_id', $campusId);
                        }
                        $q->whereDate('start_date', '<=', $date)
                            ->whereDate('end_date', '>=', $date);
                    });
            })->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->where(function ($q) {
                    $q->whereNull('recurrence_type')
                        ->orWhere('recurrence_type', 'none');
                })
                ->first();

            if ($holiday) {
                return $holiday;
            }

            // Then check recurring holidays
            return Holiday::where(function ($query) use ($campusId) {
                $query->where('is_national', true)
                    ->orWhere(function ($q) use ($campusId) {
                        if ($campusId) {
                            $q->where('campus_id', $campusId);
                        }
                    });
            })
                ->whereNotNull('recurrence_type')
                ->where('recurrence_type', '!=', 'none')
                ->get()
                ->first(function ($holiday) use ($date) {
                    return $holiday->includesDate($date);
                });
        });
    }

    /**
     * Check if attendance is allowed on a specific date.
     * Returns true if it's not a holiday OR if it's a holiday where attendance is allowed.
     */
    public function isAttendanceAllowed(string $date, ?int $campusId = null): bool
    {
        $holiday = $this->getHoliday($date, $campusId);

        // No holiday - attendance allowed
        if (! $holiday) {
            return true;
        }

        // Holiday exists - check if attendance is allowed
        return $holiday->isAttendanceAllowed();
    }

    /**
     * Get all holidays for a date range (including recurring).
     */
    public function getHolidaysInRange(string $startDate, string $endDate, ?int $campusId = null): Collection
    {
        return Cache::remember('holidays:v'.$this->holidayCacheVersion().":{$startDate}:{$endDate}:".($campusId ?? 'all'), self::CACHE_TTL, function () use ($startDate, $endDate, $campusId) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Get all active holidays (non-recurring and recurring)
            $holidays = Holiday::where(function ($query) use ($campusId) {
                if ($campusId) {
                    $query->where('is_national', true)
                        ->orWhere('campus_id', $campusId);
                } else {
                    $query->where('is_national', true);
                }
            })->where(function ($q) use ($end) {
                $q->whereNull('recurrence_end_date')
                    ->orWhere('recurrence_end_date', '>=', $end);
            })->get();

            // Filter holidays that fall within the date range
            return $holidays->filter(function ($holiday) use ($start, $end) {
                // Non-recurring holidays
                if (! $holiday->isRecurring()) {
                    return $holiday->end_date->greaterThanOrEqualTo($start)
                        && $holiday->start_date->lessThanOrEqualTo($end);
                }

                // Recurring holidays - check occurrences
                return count($holiday->getOccurrencesInRange($start, $end)) > 0;
            });
        });
    }

    /**
     * Detect if a student has approved leave for a specific date.
     */
    public function detectStudentLeave(int $studentId, string $date): ?StudentLeave
    {
        return StudentLeave::where('student_id', $studentId)
            ->approved()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Get cached attendance status IDs.
     */
    public function getStatusIds(): array
    {
        if ($this->statusIds === null) {
            $this->statusIds = Cache::remember('attendance_status_ids', self::CACHE_TTL, function () {
                return [
                    'present' => AttendanceStatus::where('code', AttendanceStatusCode::PRESENT->value)->first()?->id,
                    'absent' => AttendanceStatus::where('code', AttendanceStatusCode::ABSENT->value)->first()?->id,
                    'leave' => AttendanceStatus::where('code', AttendanceStatusCode::LEAVE->value)->first()?->id,
                    'late' => AttendanceStatus::where('code', AttendanceStatusCode::LATE->value)->first()?->id,
                ];
            });
        }

        return $this->statusIds;
    }

    /**
     * The id of a status, by its code.
     *
     * The lookup used to index the word-keyed array above with a code — every
     * caller passes `'L'`, the keys are `'leave'` — so it returned null every
     * single time and the automatic leave detection behind it never once ran.
     * Both spellings are accepted now, because both are in use.
     */
    public function getStatusId(string $code): ?int
    {
        $statusIds = $this->getStatusIds();
        $key = strtolower($code);

        if (array_key_exists($key, $statusIds)) {
            return $statusIds[$key];
        }

        return $statusIds[self::CODE_TO_NAME[strtoupper($code)] ?? ''] ?? null;
    }

    /**
     * Whether an id is the "on leave" status.
     *
     * Compared loosely on purpose: the id arrives from a request, where it may
     * be the string `"3"` rather than the integer `3`, and a strict comparison
     * against the looked-up id was false in exactly the cases that mattered.
     */
    public function isLeaveStatus(mixed $statusId): bool
    {
        $leaveId = $this->getStatusId(AttendanceStatusCode::LEAVE->value);

        return $leaveId !== null && (int) $statusId === $leaveId;
    }

    /**
     * Get all attendance statuses (cached).
     */
    public function getStatuses(): Collection
    {
        return Cache::remember('attendance_statuses', self::CACHE_TTL, function () {
            return AttendanceStatus::orderBy('name')->get();
        });
    }

    /**
     * Forgets everything this service has cached, and nothing else.
     *
     * It used to call `Cache::flush()` — three times, in a loop — which threw
     * away the entire application cache on every holiday saved: the permission
     * cache, the theme palette, whatever else was in there, for every campus
     * and every signed-in user.
     */
    public function clearCache(): void
    {
        Cache::forget('attendance_status_ids');
        Cache::forget('attendance_statuses');

        // The per-request copy has to go as well, or a status added in this
        // request is still invisible to the rest of it.
        $this->statusIds = null;

        $this->forgetHolidays();
    }

    /**
     * Invalidates every cached holiday answer.
     *
     * A holiday is cached per date and per campus, so there is no one key to
     * forget and no pattern delete on the database or file stores. Moving the
     * version forward makes every existing entry unreachable at once; they are
     * never read again and fall out on their own TTL.
     */
    public function forgetHolidays(): void
    {
        Cache::forever(self::HOLIDAY_VERSION_KEY, $this->holidayCacheVersion() + 1);
    }

    private function holidayCacheVersion(): int
    {
        return (int) Cache::rememberForever(self::HOLIDAY_VERSION_KEY, fn () => 1);
    }

    /**
     * Calculate attendance statistics from a collection of records.
     *
     * Typed to the base collection on purpose. Demanding an Eloquent one threw
     * a TypeError for any student with no records in the period — `collect()`
     * is the empty default the class report passes — so the whole report
     * returned a 500 the moment one child had not been marked.
     *
     * @param  SupportCollection<int, AttendanceStudent>  $records
     * @return array{present: int, absent: int, leave: int, late: int, total: int}
     */
    public function calculateStats(SupportCollection $records): array
    {
        $stats = [
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'late' => 0,
            'half_day' => 0,
            'total' => $records->count(),

            // Weighted by what each status is worth, so a half day counts as
            // half and a school that adds its own status is counted too.
            'present_equivalent' => 0.0,
        ];

        foreach ($records as $record) {
            $status = $record->attendanceStatus;

            if (! $status) {
                continue;
            }

            $stats['present_equivalent'] += $status->presentWeight();

            match ($status->code) {
                AttendanceStatusCode::PRESENT->value => $stats['present']++,
                AttendanceStatusCode::ABSENT->value => $stats['absent']++,
                AttendanceStatusCode::LEAVE->value => $stats['leave']++,
                AttendanceStatusCode::LATE->value => $stats['late']++,
                AttendanceStatusCode::HALF_DAY->value => $stats['half_day']++,
                default => null,
            };
        }

        $stats['present_equivalent'] = round($stats['present_equivalent'], 2);

        return $stats;
    }

    /**
     * Calculate attendance percentage.
     */
    public function calculatePercentage(int $present, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($present / $total) * 100, 2);
    }

    /**
     * The children on the roll of a class on a given date.
     *
     * Built from the enrollment period that covered **that date**, not from the
     * open one. Opening a past date used to list today's roll: a child who
     * moved from 5-A to 5-B in November appeared under 5-B when September was
     * opened, and a child who had since left was missing from the register they
     * were actually on.
     */
    public function getEligibleStudents($classId, $sectionId = null, ?int $sessionId = null, ?int $campusId = null, ?string $date = null)
    {
        $onDate = $date ?: now()->toDateString();

        $query = Student::with(['user:id,name', 'currentEnrollment.class', 'currentEnrollment.section', 'studentLeaves'])
            ->whereHas('enrollmentRecords', function ($q) use ($classId, $sectionId, $sessionId, $campusId, $onDate) {
                $q->coveringDate($onDate)
                    ->where('class_id', $classId);

                // Only filter by section if a specific section is selected (not null/empty for "all sections")
                if ($sectionId !== null && $sectionId !== '' && $sectionId !== 'all') {
                    $q->where('section_id', $sectionId);
                }

                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }

                if ($campusId) {
                    $q->where('campus_id', $campusId);
                }
            })
            ->orderBy('registration_no');

        $students = $query->get(['id', 'registration_no', 'admission_no', 'user_id']);

        // If a date is provided, load existing attendance records for that date
        $existingAttendance = [];
        if ($date) {
            $attendanceRecords = AttendanceStudent::with(['attendanceStatus', 'studentLeave'])
                ->whereHas('attendance', function ($q) use ($date, $classId, $sectionId, $sessionId, $campusId) {
                    // `whereDate`: the column holds a datetime whose time part
                    // is zero, so a plain `Y-m-d` string never matched it.
                    $q->whereDate('attendance_date', $date)
                        ->where('class_id', $classId);

                    // Filter by section if a specific section is selected
                    if ($sectionId !== null && $sectionId !== '' && $sectionId !== 'all') {
                        $q->where('section_id', $sectionId);
                    }

                    if ($sessionId) {
                        $q->where('session_id', $sessionId);
                    }

                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                })
                ->get();

            // Index by student_id for easy lookup
            foreach ($attendanceRecords as $record) {
                $existingAttendance[$record->student_id] = [
                    'id' => $record->id,
                    'attendance_status_id' => $record->attendance_status_id,
                    'attendance_status_code' => $record->attendanceStatus?->code,
                    'leave_type_id' => $record->leave_type_id,
                    'check_in' => $record->check_in,
                    'check_out' => $record->check_out,
                    'remarks' => $record->remarks,
                    'student_leave_id' => $record->student_leave_id,
                ];
            }
        }

        return $students->map(function ($student) use ($existingAttendance) {
            // Get name from user relationship or fallback to Student #
            $studentName = 'Student #'.$student->registration_no;
            if ($student->user && ! empty($student->user->name)) {
                $studentName = $student->user->name;
            }

            // Check if this student has existing attendance
            $existingRecord = $existingAttendance[$student->id] ?? null;

            return [
                'id' => $student->id,
                'registration_no' => $student->registration_no,
                'admission_no' => $student->admission_no,
                'name' => $studentName,
                'user' => $student->user,
                'current_enrollment' => $student->currentEnrollment,
                'student_leaves' => $student->studentLeaves,
                // Existing attendance data
                'existing_attendance' => $existingRecord,
                'has_existing_attendance' => $existingRecord !== null,
            ];
        });
    }
}
