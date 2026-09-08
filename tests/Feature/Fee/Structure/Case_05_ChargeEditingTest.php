<?php

/**
 * Case 05 — adding, repricing and removing a charge on a saved structure.
 *
 * The structure screen edits its charges through their own endpoints rather
 * than resubmitting the whole structure. Those endpoints answered every request
 * with a 500: the route carries the structure *and* the item, but the methods
 * declared only the item, so the framework handed the structure's id to the
 * item argument. Nothing on the screen could be edited at all.
 */

use App\Enums\Fee\FeeFrequency;
use App\Models\Fee\FeeStructureItem;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->structure = $this->world->structureWithAllFrequencies(['monthly' => 5000]);
    $this->tuition = $this->structure->items()
        ->where('fee_head_id', $this->world->school->monthlyHead->id)
        ->firstOrFail();
});

it('reprices a charge', function () {
    $this->put(route('fee.structures.items.update', [$this->structure, $this->tuition]), [
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'amount' => 7000,
        'frequency' => 'monthly',
    ])->assertRedirect();

    expect((float) $this->tuition->fresh()->amount)->toBe(7000.0);
});

it('changes a charge frequency', function () {
    $this->put(route('fee.structures.items.update', [$this->structure, $this->tuition]), [
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'amount' => 5000,
        'frequency' => 'yearly',
    ])->assertRedirect();

    expect($this->tuition->fresh()->frequency)->toBe(FeeFrequency::YEARLY);
});

it('keeps flags the form did not send', function () {
    $this->tuition->update(['is_optional' => true, 'applicable_on_admission' => true]);

    $this->put(route('fee.structures.items.update', [$this->structure, $this->tuition]), [
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'amount' => 5000,
    ])->assertRedirect();

    // Sending only the amount used to clear is_optional and force
    // applicable_on_admission on.
    $item = $this->tuition->fresh();

    expect((bool) $item->is_optional)->toBeTrue()
        ->and((bool) $item->applicable_on_admission)->toBeTrue();
});

it('adds a charge to a saved structure', function () {
    $this->post(route('fee.structures.items.store', $this->structure), [
        'fee_head_id' => $this->world->school->optionalHead->id,
        'amount' => 1500,
        'frequency' => 'once',
    ])->assertRedirect();

    expect($this->structure->items()->where('fee_head_id', $this->world->school->optionalHead->id)->exists())
        ->toBeTrue();
});

it('refuses to charge the same head twice', function () {
    $this->post(route('fee.structures.items.store', $this->structure), [
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'amount' => 3000,
    ])->assertSessionHasErrors('fee_head_id');

    expect($this->structure->items()->where('fee_head_id', $this->world->school->monthlyHead->id)->count())
        ->toBe(1);
});

it('removes a charge', function () {
    $this->delete(route('fee.structures.items.destroy', [$this->structure, $this->tuition]))
        ->assertRedirect();

    expect(FeeStructureItem::find($this->tuition->id))->toBeNull();
});

it('will not touch an item through a structure that does not own it', function () {
    $other = $this->world->school->feeStructure(['title' => 'Another Structure']);

    // Reaching a charge through the wrong structure must not work, or one
    // campus could reprice another's fees by changing the id in the URL.
    $this->put(route('fee.structures.items.update', [$other, $this->tuition]), [
        'fee_head_id' => $this->world->school->monthlyHead->id,
        'amount' => 99000,
    ])->assertNotFound();

    expect((float) $this->tuition->fresh()->amount)->toBe(5000.0);
});
