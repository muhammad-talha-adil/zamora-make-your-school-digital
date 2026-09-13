<?php

use App\Models\Purchase;
use Tests\Support\InventoryWorld;

/**
 * Every inventory route now sits behind `permission:` middleware. Before this
 * pass none of the ten seeded `inventory.*` permissions were checked
 * anywhere — any signed-in, verified account (a driver, a guardian, anyone)
 * could list stock, create purchases, delete suppliers or issue items to a
 * student. `withFullRoles()` seeds the real permission/role tables so a
 * `driver` genuinely holds none of them.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->world->school->withFullRoles();
    $this->outsider = $this->world->outsider();
});

it('denies an outsider the stocks listing', function () {
    $this->actingAs($this->outsider)
        ->get(route('inventory.stocks.index'))
        ->assertForbidden();
});

it('denies an outsider creating a purchase', function () {
    $this->actingAs($this->outsider)
        ->postJson(route('inventory.purchases.store'), [
            'campus_id' => $this->world->school->campus->id,
            'supplier_id' => $this->world->supplier->id,
            'purchase_date' => now()->toDateString(),
            'purchase_items' => [
                ['inventory_item_id' => $this->world->item->id, 'quantity' => 5, 'purchase_rate' => 10],
            ],
        ])
        ->assertForbidden();
});

it('denies an outsider deleting a purchase', function () {
    $purchase = Purchase::create([
        'campus_id' => $this->world->school->campus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'total_amount' => 0,
    ]);

    $this->actingAs($this->outsider)
        ->delete(route('inventory.purchases.destroy', $purchase))
        ->assertForbidden();
});

it('denies an outsider creating a stock adjustment', function () {
    $this->actingAs($this->outsider)
        ->post(route('inventory.adjustments.store'), [
            'campus_id' => $this->world->school->campus->id,
            'inventory_item_id' => $this->world->item->id,
            'type' => 'add',
            'quantity' => 10,
            'reason' => 'Should not be allowed',
        ])
        ->assertForbidden();
});

it('denies an outsider reserving stock', function () {
    $this->actingAs($this->outsider)
        ->postJson(route('inventory.stocks.reserve'), [
            'campus_id' => $this->world->school->campus->id,
            'item_id' => $this->world->item->id,
            'quantity' => 1,
        ])
        ->assertForbidden();
});

it('denies an outsider creating a supplier', function () {
    $this->actingAs($this->outsider)
        ->post(route('inventory.suppliers.store'), [
            'name' => 'Rogue Supplier',
        ])
        ->assertForbidden();
});

it('denies an outsider assigning inventory to a student', function () {
    $this->actingAs($this->outsider)
        ->post(route('inventory.student-inventory.assign'), [
            'campus_id' => $this->world->school->campus->id,
            'student_id' => 1,
            'items' => [['inventory_item_id' => $this->world->item->id, 'quantity' => 1]],
        ])
        ->assertForbidden();
});

it('denies an outsider viewing suppliers', function () {
    $this->actingAs($this->outsider)
        ->get(route('inventory.suppliers.index'))
        ->assertForbidden();
});

it('allows the developer actor through every one of those routes', function () {
    $this->actingAs($this->world->school->actor)
        ->get(route('inventory.stocks.index'))
        ->assertSuccessful();

    $this->actingAs($this->world->school->actor)
        ->get(route('inventory.suppliers.index'))
        ->assertSuccessful();
});
