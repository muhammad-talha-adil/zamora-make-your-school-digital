<?php

/**
 * Case 12 — discount fee mode, used for sibling and staff concessions.
 *
 * A concession the principal has to sign off must not take effect the moment
 * the clerk types it, so the discount type's approval flag decides whether the
 * saved discount starts approved or pending.
 */

use App\Models\Fee\StudentDiscount;
use App\Models\Student;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
    $this->structure = $this->world->feeStructure();
});

it('records a discount applied at admission', function () {
    $type = $this->world->discountType();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 20,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasNoErrors();

    $discount = StudentDiscount::firstOrFail();

    expect(StudentDiscount::count())->toBe(1)
        ->and($discount->discount_type_id)->toBe($type->id)
        ->and($discount->fee_head_id)->toBe($this->world->monthlyHead->id)
        ->and((float) $discount->value)->toBe(20.0)
        ->and($discount->value_type->value ?? $discount->value_type)->toBe('percent');
});

it('approves a discount straight away when its type needs no approval', function () {
    $type = $this->world->discountType(requiresApproval: false);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 10,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasNoErrors();

    $discount = StudentDiscount::firstOrFail();

    expect($discount->approval_status->value ?? $discount->approval_status)->toBe('approved')
        ->and($discount->approved_by)->toBe($this->world->actor->id);
});

it('holds a discount as pending when its type requires approval', function () {
    $type = $this->world->discountType(requiresApproval: true);

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 50,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasNoErrors();

    $discount = StudentDiscount::firstOrFail();

    expect($discount->approval_status->value ?? $discount->approval_status)->toBe('pending')
        ->and($discount->approved_by)->toBeNull();
});

it('rejects discount mode with no discounts', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
    ]))->assertSessionHasErrors('discounts');

    expect(Student::count())->toBe(0);
});

it('records several discounts in one admission', function () {
    $type = $this->world->discountType();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [
            [
                'discount_type_id' => $type->id,
                'fee_head_id' => $this->world->monthlyHead->id,
                'value' => 20,
                'value_type' => 'percent',
            ],
            [
                'discount_type_id' => $type->id,
                'fee_head_id' => $this->world->annualHead->id,
                'value' => 1000,
                'value_type' => 'fixed',
            ],
        ],
    ]))->assertSessionHasNoErrors();

    expect(StudentDiscount::count())->toBe(2);
});

it('rejects a discount value type outside fixed and percent', function () {
    $type = $this->world->discountType();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 20,
            'value_type' => 'sliding',
        ]],
    ]))->assertSessionHasErrors('discounts.0.value_type');
});

it('rejects a negative discount value', function () {
    $type = $this->world->discountType();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => -5,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasErrors('discounts.0.value');
});

it('rejects a discount type that does not exist', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => 999999,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 20,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasErrors('discounts.0.discount_type_id');
});

it('does not record discounts when the mode is not discount', function () {
    $type = $this->world->discountType();

    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'structure',
        'discounts' => [[
            'discount_type_id' => $type->id,
            'fee_head_id' => $this->world->monthlyHead->id,
            'value' => 20,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasNoErrors();

    // Discounts only take effect in discount mode; sending them alongside a
    // plain structure must not quietly reduce the child's fee.
    expect(StudentDiscount::count())->toBe(0);
});

it('rejects a percentage discount above one hundred', function () {
    $this->post(route('students.store'), $this->world->payload([
        'fee_structure_id' => $this->structure->id,
        'fee_mode' => 'structure',
        'manual_discount_percentage' => 150,
    ]))->assertSessionHasErrors('manual_discount_percentage');
});
