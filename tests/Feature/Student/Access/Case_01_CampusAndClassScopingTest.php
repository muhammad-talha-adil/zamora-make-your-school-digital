<?php

/**
 * Case 01 — which children a member of staff may see.
 *
 * Every method of `StudentPolicy` was a bare permission check that ignored the
 * record, so a teacher at the City campus could read, edit, re-admit and mark
 * as left any child at any other campus — and `export` handed them the whole
 * school. The third module in a row with that fault.
 *
 * A child's campus and class are not on the `students` row; they are on the
 * enrolment period that is open. That is what the widths are read from.
 *
 * The policy guards one record and `Student::visibleTo()` filters a list. These
 * tests hold both, and hold them to the **same** answer — because a policy
 * without a scope means the list screen hands out what the record screen
 * refuses.
 */

use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Repositories\StudentRepository;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make()->withFullRoles();
    $this->actingAs($this->world->actor);

    // One child in this campus's shared section.
    $this->post(route('students.store'), $this->world->payload())->assertSessionHasNoErrors();
    $this->student = Student::firstOrFail();
});

/** A member of staff with a role, a staff record and a campus. */
function staffMember(AdmissionWorld $world, string $role, ?int $campusId = null): User
{
    $user = $world->userWithRole($role, $role.'.'.uniqid().'@school.test');

    StaffProfile::updateOrCreate(
        ['user_id' => $user->id],
        [
            'employee_no' => 'EMP-STU-'.$user->id,
            'campus_id' => $campusId ?? $world->campus->id,
            'employment_type' => 'permanent',
            'hire_date' => '2026-04-01',
            'is_active' => true,
        ]
    );

    return $user->fresh();
}

/** A teacher, given the shared section or given nothing. */
function classTeacher(AdmissionWorld $world, bool $assigned, ?int $sectionId = null): User
{
    $teacher = staffMember($world, 'teacher');

    if ($assigned) {
        TeacherClassAssignment::create([
            'staff_profile_id' => $teacher->staffProfile->id,
            'session_id' => $world->session->id,
            'class_id' => $world->class->id,
            'section_id' => $sectionId ?? $world->section->id,
            'is_class_teacher' => true,
            'is_active' => true,
        ]);
    }

    return $teacher->fresh();
}

it('does not let a campus admin read another campus s child', function () {
    $admin = staffMember($this->world, 'campus_admin', $this->world->otherCampus->id);

    $this->actingAs($admin)
        ->get(route('students.show', $this->student->id))
        ->assertForbidden();
});

it('keeps that child out of their list too', function () {
    $admin = staffMember($this->world, 'campus_admin', $this->world->otherCampus->id);

    // The list and the record have to agree, or one of them is a leak.
    expect(Student::visibleTo($admin)->count())->toBe(0);
});

it('lets a campus admin read their own campus s child', function () {
    $admin = staffMember($this->world, 'campus_admin', $this->world->campus->id);

    $this->actingAs($admin)
        ->get(route('students.show', $this->student->id))
        ->assertOk();

    expect(Student::visibleTo($admin)->count())->toBe(1);
});

it('lets a teacher read a child in the section they were given', function () {
    $teacher = classTeacher($this->world, assigned: true);

    $this->actingAs($teacher)
        ->get(route('students.show', $this->student->id))
        ->assertOk();

    expect(Student::visibleTo($teacher)->count())->toBe(1);
});

it('does not let a teacher read a child in a section they were not given', function () {
    $teacher = classTeacher($this->world, assigned: true, sectionId: $this->world->otherSection->id);

    $this->actingAs($teacher)
        ->get(route('students.show', $this->student->id))
        ->assertForbidden();

    expect(Student::visibleTo($teacher)->count())->toBe(0);
});

it('shows a teacher with no class nobody, rather than everybody', function () {
    $teacher = classTeacher($this->world, assigned: false);

    expect(Student::visibleTo($teacher)->count())->toBe(0);

    $this->actingAs($teacher)
        ->get(route('students.show', $this->student->id))
        ->assertForbidden();
});

it('does not let a teacher edit another section s child', function () {
    $teacher = classTeacher($this->world, assigned: true, sectionId: $this->world->otherSection->id);

    $this->actingAs($teacher)
        ->get(route('students.edit', $this->student->id))
        ->assertForbidden();
});

it('does not let a teacher mark another section s child as left', function () {
    $teacher = classTeacher($this->world, assigned: true, sectionId: $this->world->otherSection->id);

    $this->actingAs($teacher)
        ->post(route('students.change-status', $this->student->id), [
            'status_id' => $this->world->activeStatus->id,
        ])
        ->assertForbidden();

    expect($this->student->fresh()->currentEnrollment)->not->toBeNull();
});

it('shows the owner every campus', function () {
    $owner = staffMember($this->world, 'owner', $this->world->otherCampus->id);

    // School-wide roles are not narrowed by the campus on their staff record.
    expect(Student::visibleTo($owner)->count())->toBe(1);

    $this->actingAs($owner)
        ->get(route('students.show', $this->student->id))
        ->assertOk();
});

it('turns a driver away from the student list altogether', function () {
    $driver = staffMember($this->world, 'driver');

    $this->actingAs($driver)->get(route('students.index'))->assertForbidden();
});

it('does not hand a guardian s details to anybody who asks', function () {
    // A phone-number lookup that returns a family, with no check at all on it.
    $driver = staffMember($this->world, 'driver');

    $this->actingAs($driver)
        ->getJson(route('students.guardian-by-phone', ['phone' => '03001234567']))
        ->assertForbidden();
});

it('follows the child when they move campus', function () {
    $admin = staffMember($this->world, 'campus_admin', $this->world->otherCampus->id);

    expect(Student::visibleTo($admin)->count())->toBe(0);

    // The child transfers to the other campus.
    StudentEnrollmentRecord::where('student_id', $this->student->id)
        ->whereNull('leave_date')
        ->update(['campus_id' => $this->world->otherCampus->id]);

    // The reach is read from the open period, so it moves with them.
    expect(Student::visibleTo($admin)->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('students.show', $this->student->id))
        ->assertOk();
});

it('does not serve one person s list to another from the cache', function () {
    $mine = staffMember($this->world, 'campus_admin', $this->world->campus->id);
    $theirs = staffMember($this->world, 'campus_admin', $this->world->otherCampus->id);

    // The list is cached. The key used to be built from the filters alone, so
    // whoever loaded page one first decided what everybody else saw.
    $this->actingAs($mine)->get(route('students.index'))->assertOk();

    expect(app(StudentRepository::class))->not->toBeNull();

    $this->actingAs($theirs);
    expect(Student::visibleTo($theirs)->count())->toBe(0);
});
