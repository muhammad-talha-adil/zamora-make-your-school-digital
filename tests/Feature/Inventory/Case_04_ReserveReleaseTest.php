<?php

use Tests\Support\InventoryWorld;

/**
 * `reserve`/`release` back a checkout-style flow (hold stock before it is
 * actually taken). Before this pass both endpoints ignored the boolean the
 * model methods return, so over-reserving — or releasing more than was ever
 * reserved — silently did nothing while still answering `success: true`.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->world->stockAt()->update(['quantity' => 10, 'reserved_quantity' => 0]);
});

it('reserves stock and lowers what is available', function () {
    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.stocks.reserve'), [
            'campus_id' => $this->world->school->campus->id,
            'item_id' => $this->world->item->id,
            'quantity' => 4,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $stock = $this->world->stockAt()->fresh();
    expect($stock->reserved_quantity)->toBe(4);
    expect($stock->available_quantity)->toBe(6);
});

it('refuses to reserve more than is available and reports failure', function () {
    $response = $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.stocks.reserve'), [
            'campus_id' => $this->world->school->campus->id,
            'item_id' => $this->world->item->id,
            'quantity' => 999,
        ]);

    $response->assertStatus(422);
    expect($response->json('success'))->toBeFalse();
    expect($this->world->stockAt()->fresh()->reserved_quantity)->toBe(0);
});

it('releases reserved stock back to available', function () {
    $this->world->stockAt()->update(['reserved_quantity' => 5]);

    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.stocks.release'), [
            'campus_id' => $this->world->school->campus->id,
            'item_id' => $this->world->item->id,
            'quantity' => 5,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($this->world->stockAt()->fresh()->reserved_quantity)->toBe(0);
});

it('clamps a release to what is actually reserved, rather than going negative', function () {
    $this->world->stockAt()->update(['reserved_quantity' => 2]);

    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.stocks.release'), [
            'campus_id' => $this->world->school->campus->id,
            'item_id' => $this->world->item->id,
            'quantity' => 5,
        ])
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($this->world->stockAt()->fresh()->reserved_quantity)->toBe(0);
});

it('reports failure releasing stock that has nothing reserved', function () {
    $response = $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.stocks.release'), [
            'campus_id' => $this->world->school->campus->id,
            'item_id' => $this->world->item->id,
            'quantity' => 5,
        ]);

    $response->assertStatus(422);
    expect($response->json('success'))->toBeFalse();
});
