<?php

use App\Models\PurchaseReturn;
use Tests\Support\InventoryWorld;

/**
 * A purchase return sends stock back to the supplier, so it must reverse
 * exactly what a purchase would have added — and refuse to send back more
 * than is actually on the shelf.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->world->stockAt()->update(['quantity' => 20]);
});

it('reduces stock by the returned quantity', function () {
    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchase-returns.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 6, 'unit_price' => 50],
            ],
        ])
        ->assertSuccessful();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(14);
});

it('refuses to return more than is currently in stock', function () {
    $response = $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchase-returns.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 999, 'unit_price' => 50],
            ],
        ]);

    // The controller catches the guard's exception and reports failure
    // rather than crashing with an unhandled 500 or a false "success".
    $response->assertStatus(500);
    expect($response->json('success'))->toBeFalse();

    // Stock must be untouched, not silently clamped to zero.
    expect($this->world->stockAt()->fresh()->quantity)->toBe(20);
});

it('reverts the stock deduction when a purchase return is deleted', function () {
    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchase-returns.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'return_date' => now()->toDateString(),
            'items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 8, 'unit_price' => 50],
            ],
        ])
        ->assertSuccessful();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(12);

    $return = PurchaseReturn::first();

    $this->actingAs($this->world->school->actor)
        ->delete(route('inventory.purchase-returns.destroy', $return))
        ->assertRedirect();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(20);
});
