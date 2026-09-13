<?php

use App\Models\InventoryAdjustment;
use Tests\Support\InventoryWorld;

/**
 * A manual stock adjustment (stock take, damage write-off, correction) must
 * keep `available_quantity` (a stored `quantity - reserved_quantity` column)
 * from ever going negative — including when it "sets" quantity below what is
 * already reserved.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
});

it('adds to stock', function () {
    $this->world->stockAt()->update(['quantity' => 5]);

    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.adjustments.store'), [
            'campus_id' => $this->world->school->campus->id,
            'inventory_item_id' => $this->world->item->id,
            'type' => 'add',
            'quantity' => 10,
            'reason' => 'Stock take correction',
        ])
        ->assertRedirect();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(15);
});

it('subtracts from stock without going below zero', function () {
    $this->world->stockAt()->update(['quantity' => 5]);

    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.adjustments.store'), [
            'campus_id' => $this->world->school->campus->id,
            'inventory_item_id' => $this->world->item->id,
            'type' => 'subtract',
            'quantity' => 20,
            'reason' => 'Damaged stock write-off',
        ])
        ->assertRedirect();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(0);
});

it('keeps reserved_quantity from exceeding quantity when a set adjustment lowers stock', function () {
    $this->world->stockAt()->update(['quantity' => 10, 'reserved_quantity' => 8]);

    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.adjustments.store'), [
            'campus_id' => $this->world->school->campus->id,
            'inventory_item_id' => $this->world->item->id,
            'type' => 'set',
            'quantity' => 3,
            'reason' => 'Recount after audit',
        ])
        ->assertRedirect();

    $stock = $this->world->stockAt()->fresh();
    expect($stock->quantity)->toBe(3);
    // Without the clamp this would have gone negative (3 - 8).
    expect($stock->reserved_quantity)->toBeLessThanOrEqual(3);
    expect($stock->available_quantity)->toBeGreaterThanOrEqual(0);
});

it('reverts stock when an adjustment is deleted', function () {
    $this->world->stockAt()->update(['quantity' => 5]);

    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.adjustments.store'), [
            'campus_id' => $this->world->school->campus->id,
            'inventory_item_id' => $this->world->item->id,
            'type' => 'add',
            'quantity' => 10,
            'reason' => 'Stock take correction',
        ]);

    expect($this->world->stockAt()->fresh()->quantity)->toBe(15);

    $adjustment = InventoryAdjustment::first();

    $this->actingAs($this->world->school->actor)
        ->delete(route('inventory.adjustments.destroy', $adjustment))
        ->assertRedirect();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(5);
});
