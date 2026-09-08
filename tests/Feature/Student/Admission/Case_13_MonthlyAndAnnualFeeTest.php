<?php

/**
 * Case 13 — the monthly and annual fee boxes on the admission form.
 *
 * These two amounts are the ones an office types most often. They are recorded
 * on the enrollment, alongside the fee_mode that says whether they override the
 * fee structure or simply restate it.
 */

use App\Models\Fee\StudentFeeAssignment;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('stores the monthly and annual amounts on the enrollment', function () {
    $this->post(route('students.store'), $this->world->payload([
        'monthly_fee' => 4500,
        'annual_fee' => 11000,
    ]))->assertSessionHasNoErrors();

    $enrollment = StudentEnrollmentRecord::firstOrFail();

    expect((float) $enrollment->monthly_fee)->toBe(4500.0)
        ->and((float) $enrollment->annual_fee)->toBe(11000.0);
});

it('defaults both amounts to zero when they are left blank', function () {
    $this->post(route('students.store'), $this->world->payload())
        ->assertSessionHasNoErrors();

    $enrollment = StudentEnrollmentRecord::firstOrFail();

    expect((float) $enrollment->monthly_fee)->toBe(0.0)
        ->and((float) $enrollment->annual_fee)->toBe(0.0);
});

it('rejects a negative monthly fee', function () {
    $this->post(route('students.store'), $this->world->payload(['monthly_fee' => -1]))
        ->assertSessionHasErrors('monthly_fee');

    expect(Student::count())->toBe(0);
});

it('rejects a negative annual fee', function () {
    $this->post(route('students.store'), $this->world->payload(['annual_fee' => -1]))
        ->assertSessionHasErrors('annual_fee');
});

it('rejects a fee that is not a number', function () {
    $this->post(route('students.store'), $this->world->payload(['monthly_fee' => 'five thousand']))
        ->assertSessionHasErrors('monthly_fee');
});

it('rejects a fee with more than two decimal places', function () {
    $this->post(route('students.store'), $this->world->payload(['monthly_fee' => '4500.123']))
        ->assertSessionHasErrors('monthly_fee');
});

it('accepts a fee with two decimal places', function () {
    $this->post(route('students.store'), $this->world->payload(['monthly_fee' => '4500.50']))
        ->assertSessionHasNoErrors();

    expect((float) StudentEnrollmentRecord::firstOrFail()->monthly_fee)->toBe(4500.50);
});

it('records the agreed fee on the enrollment, not in a second table', function () {
    $this->post(route('students.store'), $this->world->payload([
        'monthly_fee' => 4500,
        'annual_fee' => 11000,
    ]))->assertSessionHasNoErrors();

    $enrollment = StudentEnrollmentRecord::firstOrFail();

    // The enrollment is the single record of what was agreed. Copying the same
    // figures into student_fee_assignments left two sources with nothing saying
    // which one billing should follow; that table is now reserved for
    // deliberate mid-session overrides.
    expect((float) $enrollment->monthly_fee)->toBe(4500.0)
        ->and((float) $enrollment->annual_fee)->toBe(11000.0)
        ->and(StudentFeeAssignment::count())->toBe(0);
});

it('records the fee mode the office chose', function () {
    $this->post(route('students.store'), $this->world->payload([
        'monthly_fee' => 4500,
        'fee_mode' => 'manual',
        'fee_structure_id' => $this->world->feeStructure()->id,
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 4500],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 11000],
        ],
    ]))->assertSessionHasNoErrors();

    $enrollment = StudentEnrollmentRecord::firstOrFail();

    // `fee_mode` is what tells billing whether these amounts override the
    // structure or merely echo it.
    expect($enrollment->fee_mode->value ?? $enrollment->fee_mode)->toBe('manual')
        ->and(collect($enrollment->custom_fee_entries))->toHaveCount(2);
});

it('defaults to no fee mode when the office just accepts the structure', function () {
    $this->post(route('students.store'), $this->world->payload(['monthly_fee' => 4500]))
        ->assertSessionHasNoErrors();

    expect(StudentEnrollmentRecord::firstOrFail()->fee_mode)->toBeNull();
});
