<?php

use App\Models\InventoryStock;
use App\Models\Purchase;
use Tests\Support\InventoryWorld;

/**
 * A purchase is the only way new stock enters a campus. Every item on it
 * must raise `inventory_stocks.quantity` by exactly the quantity purchased,
 * and the item must belong to the campus the purchase is for.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
});

it('creates stock for an item purchased for the first time', function () {
    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchases.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => now()->toDateString(),
            'purchase_items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 20, 'purchase_rate' => 50, 'sale_rate' => 80],
            ],
        ])
        ->assertSuccessful();

    $stock = InventoryStock::where('campus_id', $this->world->school->campus->id)
        ->where('inventory_item_id', $this->world->item->id)
        ->first();

    expect($stock)->not->toBeNull();
    expect($stock->quantity)->toBe(20);
    expect($stock->available_quantity)->toBe(20);
});

it('adds to existing stock on a second purchase of the same item', function () {
    $this->world->stockAt()->update(['quantity' => 5]);

    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchases.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => now()->toDateString(),
            'purchase_items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 15, 'purchase_rate' => 50, 'sale_rate' => 80],
            ],
        ])
        ->assertSuccessful();

    expect($this->world->stockAt()->fresh()->quantity)->toBe(20);
});

it('computes the purchase total from quantity times purchase rate', function () {
    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchases.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => now()->toDateString(),
            'purchase_items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 10, 'purchase_rate' => 25, 'sale_rate' => 40],
            ],
        ])
        ->assertSuccessful();

    expect(Purchase::first()->total_amount)->toEqual('250.00');
});

it('rejects a purchase for an item that belongs to a different campus', function () {
    $otherCampusItem = $this->world->itemAtOtherCampus();

    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.purchases.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => now()->toDateString(),
            'purchase_items' => [
                ['inventory_item_id' => $otherCampusItem->id, 'quantity' => 5, 'purchase_rate' => 10],
            ],
        ])
        ->assertInvalid(['purchase_items.0.inventory_item_id']);

    expect(InventoryStock::where('inventory_item_id', $otherCampusItem->id)->exists())->toBeFalse();
});

it('reverses stock for the removed items when a purchase is updated', function () {
    $purchase = Purchase::create([
        'campus_id' => $this->world->school->campus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'total_amount' => 500,
    ]);
    $purchase->purchaseItems()->create([
        'inventory_item_id' => $this->world->item->id,
        'quantity' => 10,
        'purchase_rate' => 50,
        'sale_rate' => 80,
        'total' => 500,
    ]);
    $this->world->stockAt()->update(['quantity' => 10]);

    $this->actingAs($this->world->school->actor)
        ->putJson(route('inventory.purchases.update', $purchase), [
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => now()->toDateString(),
            'purchase_items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 3, 'purchase_rate' => 50, 'sale_rate' => 80],
            ],
        ])
        ->assertSuccessful();

    // 10 reversed, 3 re-added.
    expect($this->world->stockAt()->fresh()->quantity)->toBe(3);
});
