<?php

/**
 * Case 06 — placing the child in a class and section.
 *
 * A section belongs to exactly one class. Accepting a mismatch would put the
 * child on another class's roll, and with it the wrong attendance register,
 * date sheet and fee structure.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('requires a section when the class has sections', function () {
    $payload = $this->world->payload();
    unset($payload['section_id']);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasErrors('section_id');

    expect(Student::count())->toBe(0);
});

it('allows no section when the class has none', function () {
    $payload = $this->world->payload(['class_id' => $this->world->classWithoutSections->id]);
    unset($payload['section_id']);

    $this->post(route('students.store'), $payload)
        ->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::firstOrFail()->section_id)->toBeNull();
});

it('rejects a section that belongs to a different class', function () {
    // `otherSection` is on `class`, so pair it with the class that has none.
    $this->post(route('students.store'), $this->world->payload([
        'class_id' => $this->world->classWithoutSections->id,
        'section_id' => $this->world->section->id,
    ]))->assertSessionHasErrors('section_id');

    expect(Student::count())->toBe(0);
});

it('accepts either section of the chosen class', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-6001',
        'section_id' => $this->world->section->id,
    ]))->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-6002',
        'section_id' => $this->world->otherSection->id,
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(2);
});

it('rejects a section id that does not exist', function () {
    $this->post(route('students.store'), $this->world->payload(['section_id' => 999999]))
        ->assertSessionHasErrors('section_id');
});

it('records the campus and session on the enrollment, not on the student', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    $enrollment = StudentEnrollmentRecord::firstOrFail();

    // Class, section and campus change every year, so they live on the
    // enrollment; the student row stays the same across sessions.
    expect($enrollment->campus_id)->toBe($this->world->campus->id)
        ->and($enrollment->session_id)->toBe($this->world->session->id)
        ->and(Student::firstOrFail()->getAttributes())->not->toHaveKey('class_id');
});

it('creates exactly one enrollment row for a new admission', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::count())->toBe(1);
});

it('accepts an admission date in the past', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_date' => now()->subMonth()->toDateString(),
    ]))->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::firstOrFail()->admission_date->toDateString())
        ->toBe(now()->subMonth()->toDateString());
});

it('rejects an admission date in the future', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_date' => now()->addDay()->toDateString(),
    ]))->assertSessionHasErrors('admission_date');

    expect(Student::count())->toBe(0);
});
