<?php

/**
 * Case 04 — the student's name and date of birth.
 *
 * The name feeds the login and printed documents, and the date of birth
 * decides whether the child is of school age at all, so both are constrained.
 */

use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('accepts a name written with letters, spaces and common punctuation', function (string $name) {
    $this->post(route('students.store'), $this->world->payload(['name' => $name]))
        ->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
})->with([
    'plain' => 'Ahmed Ali',
    'with a dot' => 'Ahmed Ali Jr.',
    'hyphenated' => 'Ahmed-Ali Khan',
    'with an apostrophe' => "Ahmed O'Ali",
]);

it('rejects a name containing digits or symbols', function (string $name) {
    $this->post(route('students.store'), $this->world->payload(['name' => $name]))
        ->assertSessionHasErrors('name');

    expect(Student::count())->toBe(0);
})->with([
    'digits' => 'Ahmed 123',
    'ampersand' => 'Ahmed & Ali',
    'slash' => 'Ahmed/Ali',
    'script tag' => '<script>alert(1)</script>',
]);

it('rejects a name longer than 255 characters', function () {
    $this->post(route('students.store'), $this->world->payload(['name' => str_repeat('a', 256)]))
        ->assertSessionHasErrors('name');
});

it('rejects a date of birth in the future', function () {
    $this->post(route('students.store'), $this->world->payload([
        'dob' => now()->addDay()->toDateString(),
    ]))->assertSessionHasErrors('dob');

    expect(Student::count())->toBe(0);
});

it('rejects today as a date of birth', function () {
    $this->post(route('students.store'), $this->world->payload([
        'dob' => now()->toDateString(),
    ]))->assertSessionHasErrors('dob');
});

it('rejects a child younger than three', function () {
    $this->post(route('students.store'), $this->world->payload([
        'dob' => now()->subYears(2)->toDateString(),
    ]))->assertSessionHasErrors('dob');

    expect(Student::count())->toBe(0);
});

it('accepts a child who has just turned three', function () {
    $this->post(route('students.store'), $this->world->payload([
        'dob' => now()->subYears(3)->toDateString(),
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('rejects a student older than twenty five', function () {
    $this->post(route('students.store'), $this->world->payload([
        'dob' => now()->subYears(26)->toDateString(),
    ]))->assertSessionHasErrors('dob');

    expect(Student::count())->toBe(0);
});

it('accepts a student who has just turned twenty five', function () {
    $this->post(route('students.store'), $this->world->payload([
        'dob' => now()->subYears(25)->toDateString(),
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('rejects a date of birth that is not a date', function () {
    $this->post(route('students.store'), $this->world->payload(['dob' => 'not-a-date']))
        ->assertSessionHasErrors('dob');
});

it('trims surrounding whitespace from the name', function () {
    $this->post(route('students.store'), $this->world->payload(['name' => '  Ahmed Ali  ']))
        ->assertSessionHasNoErrors();

    $student = Student::with('user')->firstOrFail();

    expect($student->user->name)->toBe('Ahmed Ali');
});
