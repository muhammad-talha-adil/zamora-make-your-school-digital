<?php

namespace Tests\Support;

use App\Enums\Fee\VoucherStatus;
use App\Models\Attendance;
use App\Models\AttendanceStatus;
use App\Models\AttendanceStudent;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamType;
use App\Models\Fee\FeeVoucher;
use App\Models\Guardian;
use App\Models\Month;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Database\Seeders\AttendanceStatusesSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * A family the portal can be exercised against: a school (via `FeeWorld`,
 * which already grants the shared staff actor every `fee.*` ability), a
 * child with their own portal login (not the staff actor `FeeWorld` enrols by
 * default), and the fee/exam/attendance records a portal screen reads.
 *
 * `withFullRoles()` seeds the real permission tables so `student`/`guardian`
 * accounts hold the real seeded abilities — `portal.student.access`,
 * `fee.view.own`, `exam.result.view.own`, `attendance.view.own` — the same
 * fence the portal routes actually sit behind.
 */
class PortalWorld
{
    public FeeWorld $fee;

    public function __construct()
    {
        $this->fee = FeeWorld::make();
        $this->fee->structureWithAllFrequencies();

        $this->fee->school->withFullRoles();

        (new AttendanceStatusesSeeder)->run();
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * A child with their own portal login, holding the real `student` role.
     *
     * @return array{0: User, 1: Student}
     */
    public function portalStudent(string $suffix = '1'): array
    {
        $user = $this->fee->school->userWithRole('student', 'student.'.$suffix.'.'.uniqid().'@school.test');

        $student = Student::create([
            'user_id' => $user->id,
            'registration_no' => 'REG-PORTAL-'.$suffix,
            'student_code' => 'STU-PORTAL-'.$suffix,
            'admission_no' => 'ADM-PORTAL-'.$suffix,
            'dob' => now()->subYears(10)->toDateString(),
            'gender_id' => $this->fee->school->maleGender->id,
            'student_status_id' => $this->fee->school->activeStatus->id,
            'admission_date' => '2026-04-01',
        ]);

        StudentEnrollmentRecord::create([
            'student_id' => $student->id,
            'session_id' => $this->fee->school->session->id,
            'class_id' => $this->fee->school->class->id,
            'section_id' => $this->fee->school->section->id,
            'campus_id' => $this->fee->school->campus->id,
            'admission_date' => '2026-04-01',
            'student_status_id' => $this->fee->school->activeStatus->id,
            'monthly_fee' => 0,
            'annual_fee' => 0,
        ]);

        return [$user, $student->fresh('currentEnrollment')];
    }

    /**
     * A family login holding the real `guardian` role, linked to these
     * children.
     *
     * @param  array<int, Student>  $students
     */
    public function guardianFor(array $students, string $suffix = '1'): User
    {
        $user = $this->fee->school->userWithRole('guardian', 'guardian.'.$suffix.'.'.uniqid().'@school.test');

        $guardian = Guardian::create([
            'user_id' => $user->id,
            'phone' => '0300-0000000',
        ]);

        foreach ($students as $student) {
            $guardian->students()->attach($student->id, [
                'relation_id' => $this->fee->school->fatherRelation->id,
                'is_primary' => true,
            ]);
        }

        return $user->fresh();
    }

    /**
     * A fee voucher for this child.
     *
     * Built directly rather than through `VoucherGenerationService`, which
     * bills every student enrolled in the class/section for the month — the
     * portal students share a class/section with `FeeWorld`'s own student, so
     * a real generation run would hand back somebody else's voucher too.
     */
    public function voucherFor(Student $student, int $month = 4, int $year = 2026): FeeVoucher
    {
        $monthModel = Month::where('month_number', $month)->firstOrFail();

        return FeeVoucher::create([
            'voucher_no' => 'FV-PORTAL-'.$student->id.'-'.$month.'-'.$year,
            'student_id' => $student->id,
            'student_enrollment_record_id' => $student->currentEnrollment->id,
            'session_id' => $this->fee->school->session->id,
            'campus_id' => $this->fee->school->campus->id,
            'class_id' => $this->fee->school->class->id,
            'section_id' => $this->fee->school->section->id,
            'voucher_month_id' => $monthModel->id,
            'voucher_year' => $year,
            'issue_date' => "{$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-01',
            'due_date' => "{$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-10',
            'status' => VoucherStatus::UNPAID,
            'gross_amount' => 1000,
            'net_amount' => 1000,
            'balance_amount' => 1000,
        ]);
    }

    /**
     * A published exam result for this child.
     */
    public function examResultFor(Student $student, string $status = ExamResultHeader::STATUS_PUBLISHED): ExamResultHeader
    {
        $examType = ExamType::firstOrCreate(
            ['name' => 'Portal Term'],
            ['short_name' => 'PT', 'is_active' => true]
        );

        $exam = Exam::create([
            'session_id' => $this->fee->school->session->id,
            'exam_type_id' => $examType->id,
            'name' => 'Portal Term Exam',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);

        return ExamResultHeader::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'campus_id' => $this->fee->school->campus->id,
            'class_id' => $this->fee->school->class->id,
            'section_id' => $this->fee->school->section->id,
            'status' => $status,
            'total_obtained_cache' => 80,
            'overall_percentage_cache' => 80,
            'result_status' => ExamResultHeader::RESULT_PASS,
            'failed_subject_count' => 0,
        ]);
    }

    /**
     * Today's attendance status for this child.
     */
    public function attendanceFor(Student $student, string $statusCode = 'P', ?string $date = null): AttendanceStudent
    {
        $date ??= now()->toDateString();

        // One register per class/section/day — a second student marked on
        // the same day shares it rather than colliding with the unique
        // constraint on (attendance_date, class_id, section_id). Looked up
        // with `whereDate`, not `firstOrCreate`'s raw equality, because the
        // `date` cast persists a full datetime and a bare "Y-m-d" string
        // would never match it.
        $attendance = Attendance::whereDate('attendance_date', $date)
            ->where('class_id', $this->fee->school->class->id)
            ->where('section_id', $this->fee->school->section->id)
            ->first();

        $attendance ??= Attendance::create([
            'attendance_date' => $date,
            'class_id' => $this->fee->school->class->id,
            'section_id' => $this->fee->school->section->id,
            'campus_id' => $this->fee->school->campus->id,
            'session_id' => $this->fee->school->session->id,
            'taken_by' => $this->fee->school->actor->id,
            'is_locked' => false,
        ]);

        return AttendanceStudent::create([
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'attendance_status_id' => AttendanceStatus::where('code', $statusCode)->firstOrFail()->id,
        ]);
    }

    /**
     * The permission cache Spatie keeps must be cleared after roles/abilities
     * change mid-test, or a freshly-granted permission is invisible.
     */
    public function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
