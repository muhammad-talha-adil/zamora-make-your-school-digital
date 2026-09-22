<?php

/**
 * Case 11 — manual fee mode, where the office types each fee head's amount.
 *
 * Manual mode exists so a child can be admitted on negotiated amounts. It is
 * per-fee-head: the office can price just one head and leave the rest, since
 * VoucherGenerationService::getAgreedAmounts only overrides fee heads present
 * in custom_fee_entries — every other head simply bills at the structure's
 * default amount. Requiring every mandatory head to be priced here (issue
 * #70) made manual entry effectively all-or-nothing and is no longer enforced;
 * only "at least one entry" is required.
 */

use App\Models\Fee\StudentDiscount;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
    $this->structure = $this->world->feeStructure();
});

it('accepts manual mode when every mandatory head is priced', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 4000],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
        ],
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('stores the typed amounts on the enrollment', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 4000],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
        ],
    ]))->assertSessionHasNoErrors();

    $entries = collect(StudentEnrollmentRecord::firstOrFail()->custom_fee_entries);

    expect($entries)->toHaveCount(2)
        ->and($entries->firstWhere('fee_head_id', $this->world->monthlyHead->id)['amount'])->toEqual(4000);
});

it('rejects manual mode with no entries at all', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
    ]))->assertSessionHasErrors('custom_fee_entries');

    expect(Student::count())->toBe(0);
});

it('allows manual mode to price only one fee head, leaving another mandatory head on the structure default', function () {
    // Only the monthly head is priced; the annual head stays on the structure's default amount.
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 4000],
        ],
    ]))->assertSessionHasNoErrors();

    $entries = collect(StudentEnrollmentRecord::firstOrFail()->custom_fee_entries);

    expect(Student::count())->toBe(1)
        ->and($entries)->toHaveCount(1)
        ->and($entries->firstWhere('fee_head_id', $this->world->monthlyHead->id)['amount'])->toEqual(4000);
});

it('allows an optional head to be priced alongside the mandatory ones', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 4000],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
            ['fee_head_id' => $this->world->optionalHead->id, 'amount' => 1500],
        ],
    ]))->assertSessionHasNoErrors();

    expect(collect(StudentEnrollmentRecord::firstOrFail()->custom_fee_entries))->toHaveCount(3);
});

it('rejects an entry pointing at a fee head that does not exist', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => 999999, 'amount' => 4000],
        ],
    ]))->assertSessionHasErrors('custom_fee_entries.0.fee_head_id');
});

it('rejects a negative amount', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => -100],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
        ],
    ]))->assertSessionHasErrors('custom_fee_entries.0.amount');
});

it('accepts a zero amount, which is how a head is waived in manual mode', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 0],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
        ],
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});

it('ignores stale discount data left over from the discount tab when manual mode is active', function () {
    // Issue #69: a manual-entry fee head that also needs to be free (0) plus
    // a discount lives across two tabs — "free" on manual, "discount" on the
    // discount tab. Only whichever tab is active when the form is submitted
    // may be saved; a discount left over from switching tabs must not sneak
    // in alongside the manual entry.
    $type = $this->world->discountType();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 0],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
        ],
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 20,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasNoErrors();

    expect(StudentDiscount::count())->toBe(0);

    $entries = collect(StudentEnrollmentRecord::firstOrFail()->custom_fee_entries);
    expect($entries->firstWhere('fee_head_id', $this->world->monthlyHead->id)['amount'])->toEqual(0);
});

it('accepts entries sent as a JSON string, as the form posts them', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => json_encode([
            ['fee_head_id' => $this->world->monthlyHead->id, 'amount' => 4000],
            ['fee_head_id' => $this->world->annualHead->id, 'amount' => 9000],
        ]),
    ]))->assertSessionHasNoErrors();

    expect(Student::count())->toBe(1);
});
