<?php

/**
 * Case 05 — the child's B-Form number.
 *
 * B-Form is the national identifier for a child in Pakistan and appears on
 * board registrations, so its shape is fixed and no two students may carry the
 * same one.
 */

use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('accepts a correctly formatted B-Form', function () {
    $this->post(route('students.store'), $this->world->payload([
        'b_form' => '35201-1234567-8',
    ]))->assertSessionHasNoErrors();

    expect(Student::firstOrFail()->b_form)->toBe('35201-1234567-8');
});

it('allows an admission with no B-Form, since it is often pending', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    expect(Student::firstOrFail()->b_form)->toBeNull();
});

it('rejects a B-Form written in the wrong shape', function (string $bForm) {
    $this->post(route('students.store'), $this->world->payload(['b_form' => $bForm]))
        ->assertSessionHasErrors('b_form');

    expect(Student::count())->toBe(0);
})->with([
    'no dashes' => '3520112345678',
    'wrong grouping' => '3520-11234567-8',
    'letters' => '35201-ABCDEFG-8',
    'too short' => '35201-123456-8',
    'missing check digit' => '35201-1234567-',
]);

it('refuses a B-Form already registered to another student', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-5001',
        'b_form' => '35201-1234567-8',
    ]))->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-5002',
        'b_form' => '35201-1234567-8',
    ]))->assertSessionHasErrors('b_form');

    expect(Student::count())->toBe(1);
});

it('allows two students to both have no B-Form', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-5003']))
        ->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-5004']))
        ->assertSessionHasNoErrors();

    expect(Student::count())->toBe(2);
});
