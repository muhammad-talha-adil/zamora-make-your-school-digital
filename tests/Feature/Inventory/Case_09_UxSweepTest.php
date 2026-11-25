<?php

use App\Models\InventoryStock;
use Tests\Support\InventoryWorld;

beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->owner = $this->world->school->actor;
});

it('scopes the owner to the header campus even when the request asks for another', function () {
    $other = $this->world->itemAtOtherCampus();
    InventoryStock::create(['campus_id' => $this->world->school->otherCampus->id, 'inventory_item_id' => $other->id, 'quantity' => 5, 'reserved_quantity' => 0]);
    session(['viewing_campus_id' => $this->world->school->campus->id]);

    $ids = collect($this->actingAs($this->owner)
        ->getJson(route('inventory.stocks.all', ['campus_id' => $this->world->school->otherCampus->id]))
        ->assertSuccessful()->json('data'))->pluck('campus_id')->unique()->all();

    expect($ids)->not->toContain($this->world->school->otherCampus->id);
});

it('refuses to delete an item that was already purchased, with a friendly message', function () {
    session(['viewing_campus_id' => $this->world->school->campus->id]);
    $this->actingAs($this->owner)->postJson(route('inventory.purchases.store'), [
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => '2026-12-16',
        'purchase_items' => [['inventory_item_id' => $this->world->item->id, 'quantity' => 2, 'purchase_rate' => 10]],
    ])->assertSuccessful();

    $this->actingAs($this->owner)
        ->deleteJson(route('inventory.items.destroy', $this->world->item))
        ->assertUnprocessable()
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'cannot be deleted'));
});

it('returns friendly per-row messages for an incomplete purchase', function () {
    session(['viewing_campus_id' => $this->world->school->campus->id]);

    $this->actingAs($this->owner)
        ->postJson(route('inventory.purchases.store'), [
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => '2026-12-16',
            'purchase_items' => [['inventory_item_id' => $this->world->item->id]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['purchase_items.0.quantity', 'purchase_items.0.purchase_rate']);
});
