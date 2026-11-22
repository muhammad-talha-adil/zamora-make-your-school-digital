<?php

/**
 * Case 05 — fixed-amount discount at admission, and the removed
 * "default structure" flag.
 */

use App\Models\Fee\FeeStructure;
use App\Models\Fee\StudentDiscount;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
    $this->structure = $this->world->feeStructure();
});

function fixedDiscountPayload(AdmissionWorld $world, $structure, float $value, string $type = 'fixed'): array
{
    return $world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $world->discountType()->id,
            'fee_head_id' => $world->monthlyHead->id,
            'value' => $value,
            'value_type' => $type,
            'reason' => 'Staff child',
        ]],
    ]);
}

it('saves a fixed discount with its reason at admission', function () {
    $this->post(route('students.store'), fixedDiscountPayload($this->world, $this->structure, 500))
        ->assertSessionHasNoErrors();

    $discount = StudentDiscount::firstOrFail();

    expect((float) $discount->value)->toBe(500.0)
        ->and($discount->value_type->value)->toBe('fixed')
        ->and($discount->reason)->toBe('Staff child');
});

it('rejects a fixed discount bigger than the fee', function () {
    $this->post(route('students.store'), fixedDiscountPayload($this->world, $this->structure, 5001))
        ->assertSessionHasErrors('discounts.0.value');

    expect(StudentDiscount::count())->toBe(0);
});

it('rejects a percentage discount above 100', function () {
    $this->post(route('students.store'), fixedDiscountPayload($this->world, $this->structure, 101, 'percent'))
        ->assertSessionHasErrors('discounts.0.value');
});

it('shows the fixed discount on the profile fee card', function () {
    $this->post(route('students.store'), fixedDiscountPayload($this->world, $this->structure, 500))
        ->assertSessionHasNoErrors();

    $student = Student::firstOrFail();
    $summary = app(StudentService::class)->feeSummaryFor($student);

    expect($summary['monthly'])->toBe(5000.0)
        ->and($summary['monthly_net'])->toBe(4500.0)
        ->and($summary['discount'])->toBe(500.0);
});

it('no longer has a default flag on fee structures', function () {
    expect(Schema::hasColumn('fee_structures', 'is_default'))->toBeFalse()
        ->and(in_array('is_default', (new FeeStructure)->getFillable(), true))->toBeFalse();
});
