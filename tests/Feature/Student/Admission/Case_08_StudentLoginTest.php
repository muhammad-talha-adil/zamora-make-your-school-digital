<?php

/**
 * Case 08 — the login created for the child.
 *
 * Every admission creates a user account so the child can reach the student
 * portal. Two children must never end up sharing one, and a supplied email
 * must not collide with an existing account.
 */

use App\Models\Student;
use App\Models\User;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('uses the email supplied on the form', function () {
    $this->post(route('students.store'), $this->world->payload([
        'student_email' => 'ahmed.ali@student.test',
    ]))->assertSessionHasNoErrors();

    $student = Student::with('user')->firstOrFail();

    expect($student->user->email)->toBe('ahmed.ali@student.test');
});

it('generates an email when none is supplied', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    $student = Student::with('user')->firstOrFail();

    expect($student->user->email)->not->toBeEmpty();
});

it('refuses an email already registered to another account', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-8001',
        'student_email' => 'taken@student.test',
    ]))->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-8002',
        'student_email' => 'taken@student.test',
    ]))->assertSessionHasErrors('student_email');

    expect(Student::count())->toBe(1);
});

it('rejects an email that is not an email', function () {
    $this->post(route('students.store'), $this->world->payload([
        'student_email' => 'not-an-email',
    ]))->assertSessionHasErrors('student_email');
});

it('gives each admitted child their own account', function () {
    foreach (['ADM-8003', 'ADM-8004'] as $admissionNo) {
        $this->post(route('students.store'), $this->world->payload(['admission_no' => $admissionNo]))
            ->assertSessionHasNoErrors();
    }

    $userIds = Student::pluck('user_id');

    expect($userIds)->toHaveCount(2)
        ->and($userIds->unique())->toHaveCount(2);
});

it('gives the account a username derived from the student code', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    $student = Student::with('user')->firstOrFail();

    expect($student->user->username)->toContain($student->student_code);
});

it('leaves the new account active', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    expect(Student::with('user')->firstOrFail()->user->is_active)->toBeTrue();
});

it('does not expose a usable password on the created account', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    $user = User::find(Student::firstOrFail()->user_id);

    // The generated password is random and hashed; the plain value is never
    // stored, so the column must not hold anything guessable.
    expect($user->password)->not->toBeEmpty()
        ->and($user->password)->not->toBe('password')
        ->and(strlen($user->password))->toBeGreaterThan(20);
});
