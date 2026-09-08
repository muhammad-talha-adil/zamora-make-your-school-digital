<?php

namespace Tests\Support;

use App\Models\Attendance;
use App\Models\AttendancePolicy;
use App\Models\AttendanceStatus;
use App\Models\AttendanceStudent;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use Database\Seeders\AttendanceStatusesSeeder;

/**
 * A class with students on the roll, ready to have its register taken.
 *
 * Builds on AdmissionWorld so the academic structure and lookups are shared,
 * and adds only what attendance needs: the four statuses and some enrolled
 * children.
 */
class AttendanceWorld
{
    public AdmissionWorld $school;

    /** @var array<int, Student> */
    public array $students = [];

    public function __construct()
    {
        $this->school = AdmissionWorld::make();

        (new AttendanceStatusesSeeder)->run();
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Enrols children into the shared class and section.
     *
     * @return array<int, Student>
     */
    public function enrol(int $count = 3, ?int $sectionId = null): array
    {
        $sectionId ??= $this->school->section->id;

        for ($i = 0; $i < $count; $i++) {
            $suffix = count($this->students) + 1;

            $student = Student::create([
                'user_id' => $this->school->actor->id,
                'registration_no' => 'REG-ATT-'.$suffix,
                'student_code' => 'STU-ATT-'.$suffix,
                'admission_no' => 'ADM-ATT-'.$suffix,
                'dob' => now()->subYears(10)->toDateString(),
                'gender_id' => $this->school->maleGender->id,
                'student_status_id' => $this->school->activeStatus->id,
                'admission_date' => '2026-04-01',
            ]);

            StudentEnrollmentRecord::create([
                'student_id' => $student->id,
                'session_id' => $this->school->session->id,
                'class_id' => $this->school->class->id,
                'section_id' => $sectionId,
                'campus_id' => $this->school->campus->id,
                'admission_date' => '2026-04-01',
                'student_status_id' => $this->school->activeStatus->id,
                'monthly_fee' => 0,
                'annual_fee' => 0,
            ]);

            $this->students[] = $student->fresh('currentEnrollment');
        }

        return $this->students;
    }

    public function status(string $code): AttendanceStatus
    {
        return AttendanceStatus::where('code', $code)->firstOrFail();
    }

    /**
     * A user holding a role, with the staff record their campus comes from.
     *
     * Attendance scoping reads the campus off the staff profile, so a teacher
     * without one is a teacher belonging to no campus.
     */
    public function staffUser(string $role, string $email, ?int $campusId = null): User
    {
        $user = $this->school->withFullRoles()->userWithRole($role, $email);

        StaffProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_no' => 'EMP-'.$user->id,
                'campus_id' => $campusId ?? $this->school->campus->id,
                'employment_type' => 'permanent',
                'hire_date' => '2026-04-01',
                'is_active' => true,
            ]
        );

        return $user->fresh();
    }

    /**
     * Puts a teacher in charge of a section.
     */
    public function assignTeacher(
        User $teacher,
        ?int $sectionId = null,
        bool $classTeacher = false,
        ?int $classId = null
    ): TeacherClassAssignment {
        return TeacherClassAssignment::create([
            'staff_profile_id' => $teacher->staffProfile->id,
            'session_id' => $this->school->session->id,
            'class_id' => $classId ?? $this->school->class->id,
            'section_id' => $sectionId,
            'is_class_teacher' => $classTeacher,
            'is_active' => true,
        ]);
    }

    /**
     * The campus's working week, and whether absences are told to guardians.
     *
     * @param  array<int, int>  $workingDays  ISO weekdays, 1 is Monday
     */
    public function policy(array $workingDays = [1, 2, 3, 4, 5, 6], bool $alerts = false): AttendancePolicy
    {
        return AttendancePolicy::updateOrCreate(
            [
                'campus_id' => $this->school->campus->id,
                'session_id' => $this->school->session->id,
            ],
            [
                'working_days' => $workingDays,
                'absence_alert_enabled' => $alerts,
                'is_active' => true,
            ]
        );
    }

    /**
     * A register for the shared class and section on the given date.
     */
    public function register(string $date = '2026-04-06', ?int $sectionId = null): Attendance
    {
        return Attendance::create([
            'attendance_date' => $date,
            'campus_id' => $this->school->campus->id,
            'session_id' => $this->school->session->id,
            'class_id' => $this->school->class->id,
            'section_id' => $sectionId ?? $this->school->section->id,
            'taken_by' => $this->school->actor->id,
            'is_locked' => false,
        ]);
    }

    /**
     * Marks a student on a register.
     */
    public function mark(Attendance $register, Student $student, string $code = 'P'): AttendanceStudent
    {
        return AttendanceStudent::create([
            'attendance_id' => $register->id,
            'student_id' => $student->id,
            'attendance_status_id' => $this->status($code)->id,
        ]);
    }

    /**
     * The payload the mark-attendance screen posts.
     *
     * @param  array<int, Student>|null  $students
     * @return array<string, mixed>
     */
    public function payload(string $code = 'P', string $date = '2026-04-06', ?array $students = null, ?int $sectionId = null): array
    {
        $students ??= $this->students;

        return [
            'attendance_date' => $date,
            'campus_id' => $this->school->campus->id,
            'session_id' => $this->school->session->id,
            'class_id' => $this->school->class->id,
            'section_id' => $sectionId ?? $this->school->section->id,
            'attendances' => array_map(fn (Student $student) => [
                'student_id' => $student->id,
                'attendance_status_id' => $this->status($code)->id,
            ], $students),
        ];
    }
}
