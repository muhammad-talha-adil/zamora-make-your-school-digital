<?php

/**
 * Case 03 — the admission number, which identifies the student to the school.
 *
 * Two students sharing one number is the failure this guards against; the
 * school's own registers, fee vouchers and result cards are all keyed on it.
 */

use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('refuses a duplicate admission number', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-2001']))
        ->assertSessionHasNoErrors();

    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-2001']))
        ->assertSessionHasErrors('admission_no');

    expect(Student::where('admission_no', 'ADM-2001')->count())->toBe(1);
});

it('leaves the admission number exactly as typed', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'adm-2002']))
        ->assertSessionHasNoErrors();

    // Not upper-cased or otherwise normalised on the way in, so the number the
    // office typed is the number on the register.
    expect(Student::first()->admission_no)->toBe('adm-2002');
});

it('rejects an admission number longer than fifty characters', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => str_repeat('A', 51),
    ]))->assertSessionHasErrors('admission_no');

    expect(Student::count())->toBe(0);
});

it('accepts an admission number of exactly fifty characters', function () {
    $this->post(route('students.store'), $this->world->payload([
        'admission_no' => str_repeat('A', 50),
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('rejects a blank admission number', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => '   ']))
        ->assertSessionHasErrors('admission_no');

    expect(Student::count())->toBe(0);
});

it('keeps the admission number reserved after a student is deleted', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-2003']))
        ->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-2003')->firstOrFail();
    $this->delete(route('students.destroy', $student));

    expect(Student::withTrashed()->where('admission_no', 'ADM-2003')->exists())->toBeTrue();

    // A deleted student keeps their number: the register, old vouchers and
    // result cards still refer to it, so issuing it to a second child would
    // make those records ambiguous.
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-2003']))
        ->assertSessionHasErrors('admission_no');

    expect(Student::withTrashed()->where('admission_no', 'ADM-2003')->count())->toBe(1);
});

it('keeps the number reserved even after the student is restored', function () {
    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-2004']))
        ->assertSessionHasNoErrors();

    $student = Student::where('admission_no', 'ADM-2004')->firstOrFail();
    $this->delete(route('students.destroy', $student));
    $this->post(route('students.restore', $student->id));

    $this->post(route('students.store'), $this->world->payload(['admission_no' => 'ADM-2004']))
        ->assertSessionHasErrors('admission_no');
});
