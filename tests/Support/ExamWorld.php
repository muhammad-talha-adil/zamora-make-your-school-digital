<?php

namespace Tests\Support;

use App\Enums\Exam\SubjectRole;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamType;
use App\Models\Exam\GradeSystem;
use App\Models\Exam\GradeSystemItem;
use App\Models\Permission;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\Subject;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * A school with an exam on: papers timetabled, children enrolled, and a grading
 * scale to mark against.
 *
 * Builds on AdmissionWorld so the academic structure and lookups are shared,
 * and adds only what the exam module needs.
 */
class ExamWorld
{
    public AdmissionWorld $school;

    public ExamType $examType;

    public Exam $exam;

    public GradeSystem $gradeSystem;

    /** @var array<int, Student> */
    public array $students = [];

    /** @var array<string, ExamPaper> */
    public array $papers = [];

    public function __construct()
    {
        $this->school = AdmissionWorld::make();

        $this->examType = ExamType::create([
            'name' => 'First Term',
            'short_name' => 'FT',
            'is_active' => true,
        ]);

        $this->exam = Exam::create([
            'session_id' => $this->school->session->id,
            'exam_type_id' => $this->examType->id,
            'name' => 'First Term 2026',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-15',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);

        $this->gradeSystem = $this->seedGradeSystem();

        $this->grantExamAbilities();
    }

    /**
     * The exam abilities, given to the shared actor.
     *
     * The routes are behind `permission:` middleware, which reads the
     * permission tables rather than `Gate::before` — so the developer shortcut
     * that satisfies every policy does not get past the middleware. Seeding all
     * 106 permissions costs several seconds a test; these thirteen cost
     * nothing, and the authorisation cases seed the real thing.
     */
    private function grantExamAbilities(): void
    {
        $abilities = [
            'exam.view', 'exam.manage', 'exam.delete',
            'exam.paper.view', 'exam.paper.manage',
            'exam.registration.manage',
            'exam.marks.enter', 'exam.marks.verify',
            'exam.result.view', 'exam.result.view.own', 'exam.result.publish',
            'exam.revaluation.manage', 'exam.settings',
        ];

        foreach ($abilities as $ability) {
            Permission::firstOrCreate(['name' => $ability, 'guard_name' => 'web']);
        }

        $this->school->actor->givePermissionTo($abilities);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * The grading scale a school here would actually use.
     */
    private function seedGradeSystem(): GradeSystem
    {
        $system = GradeSystem::create([
            'name' => 'Standard',
            'campus_id' => $this->school->campus->id,
            'session_id' => $this->school->session->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        $bands = [
            ['A+', 80, 100, 4.0],
            ['A', 70, 79.99, 3.5],
            ['B', 60, 69.99, 3.0],
            ['C', 50, 59.99, 2.5],
            ['D', 40, 49.99, 2.0],
            ['F', 0, 39.99, 0.0],
        ];

        foreach ($bands as $index => [$letter, $min, $max, $point]) {
            GradeSystemItem::create([
                'grade_system_id' => $system->id,
                'grade_letter' => $letter,
                'min_percentage' => $min,
                'max_percentage' => $max,
                'grade_point' => $point,
                'sort_order' => $index,
            ]);
        }

        return $system;
    }

    /**
     * A paper on the timetable, for the shared class and section.
     */
    public function paper(
        string $subjectName,
        float $total = 100,
        float $passing = 40,
        ?Exam $exam = null,
        SubjectRole $role = SubjectRole::Core
    ): ExamPaper {
        $subject = Subject::firstOrCreate(
            ['name' => $subjectName],
            ['short_name' => substr($subjectName, 0, 4), 'is_active' => true]
        );

        $paper = ExamPaper::create([
            'exam_id' => ($exam ?? $this->exam)->id,
            'campus_id' => $this->school->campus->id,
            'class_id' => $this->school->class->id,
            'section_id' => $this->school->section->id,
            'scope_type' => 'SECTION',
            'subject_id' => $subject->id,
            'subject_role' => $role,
            'paper_date' => '2026-10-06',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'total_marks' => $total,
            'passing_marks' => $passing,
            'status' => 'scheduled',
        ]);

        $this->papers[$subjectName] = $paper;

        return $paper;
    }

    /**
     * Children on the roll of the shared class.
     *
     * @return array<int, Student>
     */
    public function enrol(int $count = 1): array
    {
        for ($i = 0; $i < $count; $i++) {
            $suffix = count($this->students) + 1;

            $student = Student::create([
                'user_id' => $this->school->actor->id,
                'registration_no' => 'REG-EXM-'.$suffix,
                'student_code' => 'STU-EXM-'.$suffix,
                'admission_no' => 'ADM-EXM-'.$suffix,
                'dob' => now()->subYears(12)->toDateString(),
                'gender_id' => $this->school->maleGender->id,
                'student_status_id' => $this->school->activeStatus->id,
                'admission_date' => '2026-04-01',
            ]);

            StudentEnrollmentRecord::create([
                'student_id' => $student->id,
                'session_id' => $this->school->session->id,
                'class_id' => $this->school->class->id,
                'section_id' => $this->school->section->id,
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

    /**
     * A member of staff with a real role, a staff record and a campus.
     *
     * The campus comes from the staff record — that is where a member of
     * staff's campus has always been kept — and the whole role and permission
     * set is seeded, because an authorisation case tested against a stub is not
     * an authorisation case.
     */
    public function staff(string $role, ?int $campusId = null): User
    {
        $user = $this->school->withFullRoles()
            ->userWithRole($role, $role.'.'.uniqid().'@school.test');

        StaffProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_no' => 'EMP-EXM-'.$user->id,
                'campus_id' => $campusId ?? $this->school->campus->id,
                'employment_type' => 'permanent',
                'hire_date' => '2026-04-01',
                'is_active' => true,
            ]
        );

        return $user->fresh();
    }

    /**
     * A teacher, given the shared section or given nothing.
     */
    public function teacher(bool $assigned = true, ?int $sectionId = null): User
    {
        $teacher = $this->staff('teacher');

        if ($assigned) {
            TeacherClassAssignment::create([
                'staff_profile_id' => $teacher->staffProfile->id,
                'session_id' => $this->school->session->id,
                'class_id' => $this->school->class->id,
                'section_id' => $sectionId ?? $this->school->section->id,
                'is_class_teacher' => true,
                'is_active' => true,
            ]);
        }

        return $teacher->fresh();
    }

    /**
     * The payload the marking grid posts for one child.
     *
     * @param  array<string, array<string, mixed>>  $marks  keyed by subject name
     * @return array<string, mixed>
     */
    public function marksPayload(Student $student, array $marks): array
    {
        $byPaper = [];

        foreach ($marks as $subject => $mark) {
            $byPaper[$this->papers[$subject]->id] = $mark;
        }

        return [
            'exam_id' => $this->exam->id,
            'student_id' => $student->id,
            'enrollment_id' => $student->currentEnrollment?->id,
            'marks' => $byPaper,
        ];
    }
}
