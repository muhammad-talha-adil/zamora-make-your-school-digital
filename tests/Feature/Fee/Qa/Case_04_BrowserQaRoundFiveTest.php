<?php

/**
 * Case 04 — browser QA round: percentage discount cap, overlap reason in the
 * activate response, usage count on the edit page.
 */

use Tests\Support\AdmissionWorld;
use Tests\Support\FeeWorld;

it('rejects a percentage discount above 100 at admission', function () {
    $world = AdmissionWorld::make();
    $this->actingAs($world->actor);
    $structure = $world->feeStructure();

    $this->post(route('students.store'), $world->payload([
        'fee_structure_id' => $structure->id,
        'fee_mode' => 'discount',
        'discounts' => [[
            'discount_type_id' => $world->discountType()->id,
            'fee_head_id' => $world->monthlyHead->id,
            'value' => 400,
            'value_type' => 'percent',
        ]],
    ]))->assertSessionHasErrors('discounts.0.value');
});

it('returns the real reason when activating an overlapping structure', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);
    $world->structureWithAllFrequencies();
    $other = $world->school->feeStructure(['title' => 'Other', 'status' => 'inactive']);

    $this->patchJson(route('fee.structures.activate', $other->id))
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'Make it Inactive first'));
});

it('sends the usage count to the structure edit page', function () {
    $world = FeeWorld::make();
    $this->actingAs($world->school->actor);
    $structure = $world->structureWithAllFrequencies();

    $this->get(route('fee.structures.edit', $structure->id))
        ->assertInertia(fn ($page) => $page->has('usage.vouchers'));
});
