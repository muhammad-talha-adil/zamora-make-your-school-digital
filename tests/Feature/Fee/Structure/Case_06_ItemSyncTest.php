<?php

/**
 * Case 06 — updating a structure with its whole set of charges.
 *
 * The update request validates `items` but used to throw them away, so an
 * import or an API client got a success with the charges silently dropped.
 * Sending the key now states the whole set; leaving it out changes nothing,
 * which is what the edit screen does — it edits charges one at a time through
 * their own endpoints.
 */

use App\Enums\Fee\FeeFrequency;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->structure = $this->world->structureWithAllFrequencies(['monthly' => 5000]);
    $this->school = $this->world->school;
});

/**
 * Submits an update, optionally carrying a set of charges.
 *
 * @param  array<int, array<string, mixed>>|null  $items
 */
function updateStructure(FeeWorld $world, ?array $items = null)
{
    $payload = [
        'title' => $world->structure->title,
        'session_id' => $world->school->session->id,
        'campus_id' => $world->school->campus->id,
        'class_id' => $world->school->class->id,
        'status' => 'active',
    ];

    if ($items !== null) {
        $payload['items'] = $items;
    }

    return test()->put(route('fee.structures.update', $world->structure), $payload);
}

it('leaves the charges alone when no items are sent', function () {
    updateStructure($this->world)->assertRedirect();

    // The edit screen submits without them; they must survive.
    expect($this->structure->items()->count())->toBe(3);
});

it('reprices a charge that was sent', function () {
    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 7000, 'frequency' => 'monthly'],
        ['fee_head_id' => $this->school->annualHead->id, 'amount' => 12000, 'frequency' => 'yearly'],
        ['fee_head_id' => $this->world->admissionHead()->id, 'amount' => 20000, 'frequency' => 'once'],
    ])->assertRedirect();

    $tuition = $this->structure->items()->where('fee_head_id', $this->school->monthlyHead->id)->first();

    expect((float) $tuition->amount)->toBe(7000.0);
});

it('keeps the row id when a charge is repriced', function () {
    $before = $this->structure->items()->where('fee_head_id', $this->school->monthlyHead->id)->first();

    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 7000, 'frequency' => 'monthly'],
    ])->assertRedirect();

    $after = $this->structure->items()->where('fee_head_id', $this->school->monthlyHead->id)->first();

    // Voucher lines name the item as their source, so the row must survive a
    // reprice rather than being deleted and recreated under a new id.
    expect($after->id)->toBe($before->id);
});

it('adds a charge that was not there before', function () {
    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 5000, 'frequency' => 'monthly'],
        ['fee_head_id' => $this->school->optionalHead->id, 'amount' => 1500, 'frequency' => 'once'],
    ])->assertRedirect();

    expect($this->structure->items()->where('fee_head_id', $this->school->optionalHead->id)->exists())
        ->toBeTrue();
});

it('removes a charge left out of the set', function () {
    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 5000, 'frequency' => 'monthly'],
    ])->assertRedirect();

    expect($this->structure->items()->count())->toBe(1)
        ->and($this->structure->items()->where('fee_head_id', $this->school->annualHead->id)->exists())
        ->toBeFalse();
});

it('clears every charge when an empty set is sent', function () {
    updateStructure($this->world, [])->assertRedirect();

    expect($this->structure->items()->count())->toBe(0);
});

it('changes a charge frequency', function () {
    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 5000, 'frequency' => 'yearly'],
    ])->assertRedirect();

    expect($this->structure->items()->first()->frequency)->toBe(FeeFrequency::YEARLY);
});

it('still refuses a set with the same head twice', function () {
    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 5000],
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 3000],
    ])->assertSessionHasErrors('items');

    expect($this->structure->items()->count())->toBe(3);
});

it('bills the new set on the next voucher', function () {
    $this->world->enrollment->update(['fee_structure_id' => $this->structure->id]);

    updateStructure($this->world, [
        ['fee_head_id' => $this->school->monthlyHead->id, 'amount' => 7000, 'frequency' => 'monthly'],
    ])->assertRedirect();

    $items = $this->world->generate(4);
    $tuition = $items->firstWhere('fee_head_id', $this->school->monthlyHead->id);

    expect((float) $tuition->amount)->toBe(7000.0)
        ->and($items)->toHaveCount(1);
});
