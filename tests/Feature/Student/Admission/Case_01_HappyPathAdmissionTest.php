<?php

/**
 * Case 01 — a straightforward admission with the minimum required fields.
 *
 * Establishes what a successful admission is expected to produce, so the
 * cases that follow can each vary one thing against this baseline:
 * a student, a login for them, an enrollment row, and a linked guardian.
 */

use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('admits a student and redirects to the list', function () {
    $payload = $this->world->payload(['admission_no' => 'ADM-1001']);

    $response = $this->post(route('students.store'), $payload);

    $response->assertRedirect(route('students.index'));
    $response->assertSessionHas('success');
    $response->assertSessionHasNoErrors();

    expect(Student::where('admission_no', 'ADM-1001')->exists())->toBeTrue();
});

it('stores the student details exactly as submitted', function () {
    $payload = $this->world->payload([
        'admission_no' => 'ADM-1002',
        'name' => 'Ahmed Ali',
        'dob' => '2015-04-12',
    ]);

    $this->post(route('students.store'), $payload)->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-1002')->firstOrFail();

    expect($student->dob->toDateString())->toBe('2015-04-12')
        ->and($student->gender_id)->toBe($this->world->maleGender->id)
        ->and($student->student_status_id)->toBe($this->world->activeStatus->id);
});

it('generates a student code and registration number', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-1003']))
        ->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-1003')->firstOrFail();

    expect($student->student_code)->not->toBeEmpty()
        ->and($student->registration_no)->not->toBeEmpty();
});

it('creates a login for the student and gives them the student role', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-1004',
        'name' => 'Ahmed Ali',
    ]))->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-1004')->firstOrFail();
    $user = User::find($student->user_id);

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Ahmed Ali')
        ->and($user->hasRole('student'))->toBeTrue();
});

it('records the enrollment against the chosen campus, session, class and section', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-1005']))
        ->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-1005')->firstOrFail();
    $enrollment = StudentEnrollmentRecord::where('student_id', $student->id)->firstOrFail();

    expect($enrollment->campus_id)->toBe($this->world->campus->id)
        ->and($enrollment->session_id)->toBe($this->world->session->id)
        ->and($enrollment->class_id)->toBe($this->world->class->id)
        ->and($enrollment->section_id)->toBe($this->world->section->id)
        ->and($enrollment->leave_date)->toBeNull()
        ->and($enrollment->previous_enrollment_id)->toBeNull();
});

it('defaults the admission date to today when none is given', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-1006']))
        ->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-1006')->firstOrFail();

    expect($student->admission_date->toDateString())->toBe(now()->toDateString());
});

it('creates the father as a guardian and links him to the student', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-1007',
        'father_name' => 'Muhammad Ali',
        'father_cnic' => '35202-7654321-9',
    ]))->assertSessionHasNoErrors();

    $student = Student::with('guardians')->where('admission_no', 'ADM-1007')->firstOrFail();
    $guardian = Guardian::where('cnic', '35202-7654321-9')->first();

    expect($guardian)->not->toBeNull()
        ->and($student->guardians)->toHaveCount(1)
        ->and($student->guardians->first()->id)->toBe($guardian->id);
});
