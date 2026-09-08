<?php

/**
 * Case 02 — the fields an admission cannot be saved without.
 *
 * Each field is removed from an otherwise valid payload, so a failure here
 * means that one field, and nothing else, stopped being required.
 */

use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('rejects an admission missing a required field', function (string $field) {
    $payload = $this->world->payload();
    unset($payload[$field]);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(Student::count())->toBe(0);
})->with([
    'admission number' => 'admission_no',
    'name' => 'name',
    'date of birth' => 'dob',
    'gender' => 'gender_id',
    'student status' => 'student_status_id',
    'campus' => 'campus_id',
    'academic session' => 'session_id',
    'class' => 'class_id',
]);

it('requires a father name when no existing guardian is linked', function () {
    $payload = $this->world->payload();
    unset($payload['father_name']);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors('father_name');

    expect(Student::count())->toBe(0);
});

it('requires the relationship once a father name is given', function () {
    $payload = $this->world->payload();
    unset($payload['father_relation_id']);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors('father_relation_id');
});

it('accepts an admission without the optional fields', function () {
    $payload = $this->world->payload();

    // The guardian's phone is not optional; it is how the school reaches the
    // family and how a sibling admission finds this guardian again.
    foreach (['father_cnic', 'father_occupation', 'father_address'] as $optional) {
        unset($payload[$optional]);
    }

    $this->post(route('students.store'), $payload)
        ->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('requires the guardian phone at admission', function () {
    $payload = $this->world->payload();
    unset($payload['father_phone']);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors('father_phone');

    expect(Student::count())->toBe(0);
});

it('rejects a reference that does not exist', function (string $field) {
    $payload = $this->world->payload([$field => 999999]);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(Student::count())->toBe(0);
})->with([
    'gender' => 'gender_id',
    'student status' => 'student_status_id',
    'campus' => 'campus_id',
    'academic session' => 'session_id',
    'class' => 'class_id',
    'section' => 'section_id',
    'father relation' => 'father_relation_id',
]);
