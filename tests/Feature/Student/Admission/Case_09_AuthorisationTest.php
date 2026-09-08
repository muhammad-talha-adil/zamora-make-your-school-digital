<?php

/**
 * Case 09 — who is allowed to admit a student.
 *
 * Admission creates a login, a guardian and a billing record, so it is limited
 * to the office roles. A teacher or driver reaching this endpoint is a bug,
 * not a convenience.
 *
 * These are the only cases that need the full permission set seeded.
 */

use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make()->withFullRoles();
});

it('turns away a guest', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertRedirect(route('login'));

    expect(Student::count())->toBe(0);
});

it('lets a role holding students.create admit a student', function (string $role) {
    $user = $this->world->userWithRole($role, $role.'@school.test');

    $this->actingAs($user)
        ->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
})->with(['campus_admin', 'super_admin', 'owner', 'clerk']);

it('refuses a role without students.create', function (string $role) {
    $user = $this->world->userWithRole($role, $role.'@school.test');

    $this->actingAs($user)
        ->post(route('students.store'), $this->world->payload())
        ->assertForbidden();

    expect(Student::count())->toBe(0);
})->with(['teacher', 'head_teacher', 'accountant', 'driver', 'receptionist', 'maid', 'student', 'guardian']);

it('lets the developer admit a student without an explicit permission row', function () {
    $this->actingAs($this->world->actor)
        ->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('refuses the admission form to a role that cannot admit', function () {
    $teacher = $this->world->userWithRole('teacher', 'teacher.form@school.test');

    $this->actingAs($teacher)
        ->get(route('students.create'))
        ->assertForbidden();
});

it('lets a viewer see the student list without being able to admit', function () {
    $teacher = $this->world->userWithRole('teacher', 'teacher.list@school.test');

    // A teacher holds students.view but not students.create.
    $this->actingAs($teacher)->get(route('students.index'))->assertOk();

    $this->actingAs($teacher)
        ->post(route('students.store'), $this->world->payload())
        ->assertForbidden();
});
