<?php

namespace App\Services;

use App\Models\AttendanceStatus;
use App\Models\Holiday;
use App\Models\StudentLeave;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    /**
     * Cache for attendance status IDs.
     */
    private ?array $statusIds = null;

    /**
     * Check if a date is a holiday for a campus.
     */
    public function isHoliday(string $date, ?int $campusId = null): bool
    {
        $holiday = Holiday::where(function ($query) use ($date, $campusId) {
            $query->where('is_national', true)
                ->orWhere(function ($q) use ($date, $campusId) {
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                    $q->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
                });
        })->where('start_date', '<=', $date)
          ->where('end_date', '>=', $date)
          ->first();

        return $holiday !== null;
    }

    /**
     * Get holiday details for a date and campus.
     */
    public function getHoliday(string $date, ?int $campusId = null): ?Holiday
    {
        return Holiday::where(function ($query) use ($date, $campusId) {
            $query->where('is_national', true)
                ->orWhere(function ($q) use ($date, $campusId) {
                    if ($campusId) {
                        $q->where('campus_id', $campusId);
                    }
                    $q->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
                });
        })->where('start_date', '<=', $date)
          ->where('end_date', '>=', $date)
          ->first();
    }

    /**
     * Detect if a student has approved leave for a specific date.
     */
    public function detectStudentLeave(int $studentId, string $date): ?StudentLeave
    {
        return StudentLeave::where('student_id', $studentId)
            ->where('status', 'approved')
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
            $this->statusIds = [
                'present' => AttendanceStatus::where('code', 'P')->first()?->id,
                'absent' => AttendanceStatus::where('code', 'A')->first()?->id,
                'leave' => AttendanceStatus::where('code', 'L')->first()?->id,
                'late' => AttendanceStatus::where('code', 'LT')->first()?->id,
            ];
        }

        return $this->statusIds;
    }

    /**
     * Get a specific status ID by code.
     */
    public function getStatusId(string $code): ?int
    {
        $statusIds = $this->getStatusIds();
        return $statusIds[strtolower($code)] ?? null;
    }

    /**
     * Calculate attendance statistics from a collection of records.
     */
    public function calculateStats(Collection $records): array
    {
        $stats = [
            'present' => 0,
            'absent' => 0,
            'leave' => 0,
            'late' => 0,
            'total' => $records->count(),
        ];

        foreach ($records as $record) {
            $code = $record->attendanceStatus?->code;
            
            switch ($code) {
                case 'P':
                    $stats['present']++;
                    break;
                case 'A':
                    $stats['absent']++;
                    break;
                case 'L':
                    $stats['leave']++;
                    break;
                case 'LT':
                    $stats['late']++;
                    break;
            }
        }

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
     * Validate check-in and check-out times.
     */
    public function validateTimes(?string $checkIn, ?string $checkOut): array
    {
        $errors = [];

        if ($checkIn && $checkOut) {
            $checkInTime = Carbon::parse($checkIn);
            $checkOutTime = Carbon::parse($checkOut);

            if ($checkOutTime->lessThanOrEqualTo($checkInTime)) {
                $errors[] = 'Check-out time must be after check-in time.';
            }
        }

        return $errors;
    }

    /**
     * Mark all students with a specific status.
     */
    public function markAllStudents(array &$studentAttendances, string $statusCode): void
    {
        $statusId = $this->getStatusId($statusCode);
        
        if ($statusId) {
            foreach ($studentAttendances as &$attendance) {
                $attendance['attendance_status_id'] = $statusId;
            }
        }
    }

    /**
     * Get students eligible for attendance on a date with existing attendance data.
     */
    public function getEligibleStudents($classId, $sectionId = null, ?int $sessionId = null, ?int $campusId = null, ?string $date = null)
    {
        $query = \App\Models\Student::with(['user:id,name', 'currentEnrollment.class', 'currentEnrollment.section', 'studentLeaves'])
            ->whereHas('currentEnrollment', function ($q) use ($classId, $sectionId, $sessionId, $campusId) {
                $q->where('class_id', $classId)
                  ->whereNull('leave_date');

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
            $attendanceRecords = \App\Models\AttendanceStudent::with(['attendanceStatus', 'studentLeave'])
                ->whereHas('attendance', function ($q) use ($date, $classId, $sectionId, $sessionId, $campusId) {
                    $q->where('attendance_date', $date)
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
            $studentName = 'Student #' . $student->registration_no;
            if ($student->user && !empty($student->user->name)) {
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
