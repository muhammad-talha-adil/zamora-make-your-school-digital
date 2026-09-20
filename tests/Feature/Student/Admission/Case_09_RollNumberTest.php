<?php

/**
 * Case 09 — the class roll number (issue #85).
 *
 * The admission form suggests the next roll number for the chosen
 * session/class/section, but the office can type over it. The one thing that
 * must never happen is two active students in the same class sharing a roll
 * number.
 */

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('accepts an admission with an explicit roll number', function () {
    $this->post(route('students.store'), $this->world->payload(['roll_number' => 5]))
        ->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::first()->roll_number)->toBe(5);
});

it('suggests roll number 1 for an empty class section', function () {
    $response = $this->getJson(route('students.next-roll-number', [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
        'section_id' => $this->world->section->id,
    ]));

    $response->assertSuccessful();
    expect($response->json('next_roll_number'))->toBe(1);
});

it('suggests the next roll number after an existing admission', function () {
    $this->post(route('students.store'), $this->world->payload(['roll_number' => 7]))
        ->assertSessionHasNoErrors();

    $response = $this->getJson(route('students.next-roll-number', [
        'session_id' => $this->world->session->id,
        'class_id' => $this->world->class->id,
        'section_id' => $this->world->section->id,
    ]));

    expect($response->json('next_roll_number'))->toBe(8);
});

it('rejects a roll number already used by another active student in the same class and section', function () {
    $this->post(route('students.store'), $this->world->payload(['roll_number' => 3]))
        ->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload(['roll_number' => 3]))
        ->assertSessionHasErrors('roll_number');

    expect(Student::count())->toBe(1);
});

it('allows the same roll number in a different section', function () {
    $this->post(route('students.store'), $this->world->payload(['roll_number' => 3]))
        ->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload([
        'roll_number' => 3,
        'section_id' => $this->world->otherSection->id,
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(2);
});
