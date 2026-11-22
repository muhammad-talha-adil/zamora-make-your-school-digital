<?php

/**
 * Case 06 - round 01h browser QA fixes.
 */

use App\Enums\Fee\FeeStructureStatus;
use App\Models\Fee\DiscountType;
use App\Models\Fee\FeeStructureItem;
use App\Models\Fee\StudentDiscount;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Support\Carbon;
use Tests\Support\AdmissionWorld;
use Tests\Support\FeeWorld;

function round01hDiscountPayload(AdmissionWorld $world, $structure, array $extra = []): array
{
    return $world->payload(array_merge([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $world->discountType()->id,
            'fee_head_id' => $world->monthlyHead->id,
            'value' => 500,
            'value_type' => 'fixed',
            'reason' => 'Staff child',
        ]],
    ], $extra));
}

it('accepts a fixed discount when the form sends the string "null" as percentage', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);
    $structure = $world->feeStructure();

    $this->post(route('students.store'), round01hDiscountPayload($world, $structure, ['manual_discount_percentage' => 'null']))
        ->assertSessionHasNoErrors();

    $discount = StudentDiscount::firstOrFail();
    expect($discount->value_type->value)->toBe('fixed')
        ->and((float) $discount->value)->toBe(500.0)
        ->and($discount->reason)->toBe('Staff child');
});

it('requires a reason for a zero or changed manual amount', function (float $amount) {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);
    $structure = $world->feeStructure();

    $payload = $world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'manual',
        'custom_fee_entries' => [['fee_head_id' => $world->monthlyHead->id, 'amount' => $amount]],
    ]);

    $this->post(route('students.store'), $payload)->assertSessionHasErrors('manual_discount_reason');

    $payload['manual_discount_reason'] = 'Agreed with father';
    $this->post(route('students.store'), $payload)->assertSessionHasNoErrors();
})->with([0, 4000]);

it('keeps the profile monthly fee free of transport the student does not use', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);
    $structure = $world->feeStructure();
    FeeStructureItem::where('fee_structure_id', $structure->id)->where('fee_head_id', $world->optionalHead->id)->delete();
    FeeStructureItem::create([
        'fee_structure_id' => $structure->id,
        'fee_head_id' => $world->optionalHead->id,
        'amount' => 2000,
        'frequency' => 'monthly',
        'applicable_on_admission' => false,
        'is_optional' => false,
        'is_transport_related' => true,
    ]);

    $this->post(route('students.store'), $world->payload(['fee_structure_id' => $structure->id, 'fee_mode' => 'structure']))
        ->assertSessionHasNoErrors();

    $summary = app(StudentService::class)->feeSummaryFor(Student::firstOrFail());

    expect($summary['monthly'])->toBe(5000.0);
});

it('has a generic Other Discount type', function () {
    expect(DiscountType::where('code', 'OTHER')->where('name', 'Other Discount')->exists())->toBeTrue();
});

it('sets the voucher due date from the billing month, never in the past', function () {
    Carbon::setTestNow('2026-04-02 09:00:00');
    $fee = FeeWorld::make();
    $this->actingAs($fee->school->actor);
    $fee->structureWithAllFrequencies(['monthly' => 5000]);
    $fee->generate(4);
    expect($fee->voucherFor(4)->due_date->toDateString())->toBe('2026-04-10');

    Carbon::setTestNow();
});

it('says already Inactive when an inactive structure with vouchers is deleted', function () {
    $fee = FeeWorld::make();
    $this->actingAs($fee->school->actor);
    $structure = $fee->structureWithAllFrequencies();
    $fee->enrollment->update(['fee_structure_id' => $structure->id]);
    $fee->generate(4);
    $structure->update(['status' => FeeStructureStatus::INACTIVE]);

    $this->deleteJson(route('fee.structures.destroy', $structure->id))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Used by 1 voucher. It is already Inactive, so no new fees use it.');
});
