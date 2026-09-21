<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\AttendancePolicy;
use App\Models\AttendanceStudent;
use App\Models\Staff\StaffAttendance;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Services\AttendanceService;
use App\Services\Staff\StaffAttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Marks today's attendance for one person from a signed QR/URL scan.
 *
 * This is the only entry point the unauthenticated `attendance/qr/*` routes
 * use. It exists so a phone's camera app, or a USB barcode scanner typing a
 * signed URL into an address bar, can mark a single, already-identified
 * person present without a login — the signature on the URL is what proves
 * the request is genuine, not a session.
 *
 * Deliberately narrow: it only ever marks *present*, only for *today*, and
 * only once. Anything else (absent, late, editing a past day) stays behind
 * the normal authenticated register screens.
 */
class QrAttendanceMarkingService
{
    public function __construct(
        private AttendanceService $attendance,
        private WorkingDayCalculator $workingDays,
        private StaffAttendanceService $staffAttendance,
    ) {}

    /**
     * Marks a student present for today, scanned off their ID card.
     *
     * @throws ValidationException when today is not a working day, or the
     *                             student has no current class placement to
     *                             register against
     */
    public function markStudent(Student $student): AttendanceStudent
    {
        $enrollment = $student->currentEnrollment;

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'student' => 'This student has no current class enrolment to mark attendance against.',
            ]);
        }

        $today = Carbon::today();
        $campusId = $enrollment->campus_id;

        $this->assertWorkingDay($today, $campusId, $enrollment->session_id);

        // Matched on `attendance_date` + `class_id` + `section_id` only — that
        // is the register's actual unique constraint (see the migration
        // that enforces one register per class/section/day); campus and
        // session are carried along as attributes of that same triple, not
        // extra parts of the identity. `whereDate()` rather than
        // `firstOrCreate()`'s plain equality, because `attendance_date` is
        // stored as a full datetime and a bare date string never matches it.
        $register = Attendance::whereDate('attendance_date', $today->toDateString())
            ->where('class_id', $enrollment->class_id)
            ->where('section_id', $enrollment->section_id)
            ->first();

        if (! $register) {
            $register = Attendance::create([
                'attendance_date' => $today->toDateString(),
                'class_id' => $enrollment->class_id,
                'section_id' => $enrollment->section_id,
                'campus_id' => $campusId,
                'session_id' => $enrollment->session_id,
                'taken_by' => null,
                'is_locked' => false,
            ]);
        }

        if ($register->is_locked) {
            throw ValidationException::withMessages([
                'attendance' => 'Today\'s register is already locked.',
            ]);
        }

        $presentStatusId = $this->attendance->getStatusId('P');

        $existing = AttendanceStudent::where('attendance_id', $register->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return AttendanceStudent::create([
            'attendance_id' => $register->id,
            'student_id' => $student->id,
            'attendance_status_id' => $presentStatusId,
            'check_in' => $today->format('H:i:s'),
        ]);
    }

    /**
     * Marks a member of staff present for today, scanned off their ID card.
     *
     * Reuses `StaffAttendanceService::mark()` so the same late-arrival rule
     * and locked-day guard the authenticated screen uses applies here too.
     *
     * @throws ValidationException when today is not a working day for their
     *                             campus, or the day is already locked
     */
    public function markStaff(StaffProfile $staff): StaffAttendance
    {
        $today = Carbon::today();

        $this->assertWorkingDay($today, $staff->campus_id, null);

        $existing = StaffAttendance::where('staff_profile_id', $staff->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing) {
            return $existing;
        }

        $presentStatusId = $this->attendance->getStatusId('P');

        return $this->staffAttendance->mark(
            staff: $staff,
            date: $today->toDateString(),
            statusId: $presentStatusId,
            checkIn: $today->format('H:i:s'),
            campusId: $staff->campus_id,
        );
    }

    /**
     * @throws ValidationException when the day is not a working day
     */
    private function assertWorkingDay(Carbon $date, ?int $campusId, ?int $sessionId): void
    {
        $policy = AttendancePolicy::query()
            ->where('campus_id', $campusId)
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->where('is_active', true)
            ->first();

        if ($policy && ! $this->workingDays->isWorkingDay($date, $policy, $campusId)) {
            throw ValidationException::withMessages([
                'date' => 'Today is not a working day, so attendance cannot be marked.',
            ]);
        }

        if (! $policy && ! $this->attendance->isAttendanceAllowed($date->toDateString(), $campusId)) {
            throw ValidationException::withMessages([
                'date' => 'Today is not a working day, so attendance cannot be marked.',
            ]);
        }
    }
}
