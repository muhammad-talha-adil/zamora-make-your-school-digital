<?php

/**
 * Case 02 — the family a child belongs to, and the card they carry.
 *
 * Fee concessions here are given by family — "second child 20%" — and the
 * discount module had nowhere to ask how many of this family are on the roll.
 * The link was always in the data: guardians are deduplicated by phone at
 * admission, so two children with the same father already point at one
 * `guardians` row. Nothing exposed it.
 *
 * The photograph has been uploaded and stored since the beginning, and nothing
 * has ever printed it.
 */

use App\Models\Student;
use App\Models\StudentStatus;
use App\Services\Student\SiblingService;
use App\Services\Student\StudentEnrollmentService;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
    $this->siblings = app(SiblingService::class);

    // Two children, one father — the same phone number, so the same guardian.
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-F1',
        'father_phone' => '03001112233',
        'father_cnic' => '35202-1112233-1',
        'admission_date' => '2024-04-01',
    ]))->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-F2',
        'father_phone' => '03001112233',
        'father_cnic' => '35202-1112233-1',
        'student_email' => 'younger@student.test',
        'admission_date' => '2026-04-01',
    ]))->assertSessionHasNoErrors();

    $this->elder = Student::where('admission_no', 'ADM-F1')->firstOrFail();
    $this->younger = Student::where('admission_no', 'ADM-F2')->firstOrFail();
});

/* -------------------------------------------------------------------- family */

it('finds the other children of the same father', function () {
    expect($this->siblings->siblingsOf($this->elder)->pluck('id'))
        ->toContain($this->younger->id);
});

it('counts the family, this child included', function () {
    expect($this->siblings->familySizeOn($this->elder))->toBe(2);
});

it('orders the family by when they joined, not by when they were born', function () {
    // "Second child" for a concession means the second to be admitted — the one
    // the school is still deciding a fee for.
    expect($this->siblings->birthOrderFor($this->elder))->toBe(1)
        ->and($this->siblings->birthOrderFor($this->younger))->toBe(2);
});

it('does not call two unrelated children with the same name siblings', function () {
    // A different father, same everything else.
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => 'ADM-F3',
        'father_phone' => '03009998877',
        'father_cnic' => '35202-9998877-1',
        'student_email' => 'unrelated@student.test',
    ]))->assertSessionHasNoErrors();

    $stranger = Student::where('admission_no', 'ADM-F3')->firstOrFail();

    expect($this->siblings->siblingsOf($stranger))->toBeEmpty()
        ->and($this->siblings->familySizeOn($this->elder))->toBe(2);
});

it('stops counting a sibling who has left', function () {
    app(StudentEnrollmentService::class)->leave(
        $this->elder,
        null,
        StudentStatus::where('name', 'Left')->firstOrFail()->id
    );

    // An elder brother who left last year does not make this child the second
    // child.
    expect($this->siblings->familySizeOn($this->younger))->toBe(1);
});

it('answers over HTTP', function () {
    $this->getJson(route('students.siblings', $this->elder->id))
        ->assertSuccessful()
        ->assertJsonPath('data.family_size', 2)
        ->assertJsonPath('data.birth_order', 1)
        ->assertJsonCount(1, 'data.siblings');
});

it('does not hand a family to somebody who may not read the child', function () {
    $outsider = $this->world->withFullRoles()
        ->userWithRole('driver', 'driver.family@school.test');

    $this->actingAs($outsider)
        ->getJson(route('students.siblings', $this->elder->id))
        ->assertForbidden();
});

/* --------------------------------------------------------------------- cards */

it('prints an ID card for a section', function () {
    $page = $this->get(route('students.id-cards', [
        'class_id' => $this->world->class->id,
        'section_id' => $this->world->section->id,
    ]))->assertSuccessful()->getContent();

    // Both children, on one sheet, to be cut.
    expect(substr_count($page, 'Adm No'))->toBe(2)
        ->and($page)->toContain('ADM-F1')
        ->and($page)->toContain('ADM-F2');
});

it('puts the guardian s phone on the card', function () {
    // The one field on the card that exists for the child's safety rather than
    // the school's records.
    $this->get(route('students.id-cards', ['class_id' => $this->world->class->id]))
        ->assertSee('03001112233', false);
});

it('prints named children when it is given them', function () {
    $page = $this->get(route('students.id-cards', [
        'student_ids' => [$this->elder->id],
    ]))->assertSuccessful()->getContent();

    expect(substr_count($page, 'Adm No'))->toBe(1)
        ->and($page)->toContain('ADM-F1');
});

it('does not print cards for a section that is not this teacher s', function () {
    $outsider = $this->world->withFullRoles()
        ->userWithRole('driver', 'driver.cards@school.test');

    $this->actingAs($outsider)
        ->get(route('students.id-cards', ['class_id' => $this->world->class->id]))
        ->assertForbidden();
});
