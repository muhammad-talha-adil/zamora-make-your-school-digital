<?php

/**
 * Case 10 — choosing a fee structure at admission.
 *
 * A structure belongs to one campus, session and (optionally) class or
 * section. Attaching the wrong one silently bills the child against another
 * class's rates, so each mismatch is rejected rather than saved.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('accepts an active structure matching the campus, session and class', function () {
    $structure = $this->world->feeStructure();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
    ]))->assertSessionHasNoErrors();

    $enrollment = StudentEnrollmentRecord::firstOrFail();

    expect($enrollment->fee_structure_id)->toBe($structure->id)
        ->and($enrollment->fee_mode->value ?? $enrollment->fee_mode)->toBe('structure');
});

it('rejects a structure that is not active', function (string $status) {
    $structure = $this->world->feeStructure(['status' => $status]);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
    ]))->assertSessionHasErrors('fee_structure_id');

    expect(Student::count())->toBe(0);
})->with(['draft', 'inactive']);

it('rejects a structure belonging to another campus', function () {
    $structure = $this->world->feeStructure(['campus_id' => $this->world->otherCampus->id]);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
    ]))->assertSessionHasErrors('fee_structure_id');

    expect(Student::count())->toBe(0);
});

it('rejects a structure belonging to another session', function () {
    $structure = $this->world->feeStructure(['session_id' => $this->world->otherSession->id]);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
    ]))->assertSessionHasErrors('fee_structure_id');
});

it('rejects a structure belonging to another class', function () {
    $structure = $this->world->feeStructure(['class_id' => $this->world->classWithoutSections->id]);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
    ]))->assertSessionHasErrors('fee_structure_id');
});

it('rejects a structure tied to a different section', function () {
    $structure = $this->world->feeStructure(['section_id' => $this->world->otherSection->id]);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
        'section_id' => $this->world->section->id,
    ]))->assertSessionHasErrors('fee_structure_id');
});

it('accepts a class-wide structure for any section of that class', function () {
    $structure = $this->world->feeStructure(['section_id' => null]);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'structure',
        'section_id' => $this->world->otherSection->id,
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('rejects a fee structure id that does not exist', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => 999999,
        'fee_mode' => 'structure',
    ]))->assertSessionHasErrors('fee_structure_id');
});

it('allows an admission with no fee structure at all', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::firstOrFail()->fee_structure_id)->toBeNull();
});

it('rejects a fee mode outside the allowed set', function () {
    $this->post(route('students.store'), $this->world->payload(['fee_mode' => 'freestyle']))
        ->assertSessionHasErrors('fee_mode');
});
