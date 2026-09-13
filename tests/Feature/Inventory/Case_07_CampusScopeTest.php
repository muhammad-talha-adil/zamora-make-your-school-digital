<?php

use App\Models\InventoryStock;
use Tests\Support\InventoryWorld;

/**
 * `campus_id` used to be read straight off the query string and trusted —
 * a campus-restricted user (a store keeper with a staff record, not a
 * school-wide role) could pass any other campus's id and see or reserve its
 * stock. `resolveCampusId()` now makes a restricted user's own campus win
 * over whatever the request asked for.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->otherItem = $this->world->itemAtOtherCampus();

    InventoryStock::create([
        'campus_id' => $this->world->school->campus->id,
        'inventory_item_id' => $this->world->item->id,
        'quantity' => 7,
        'reserved_quantity' => 0,
    ]);

    InventoryStock::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'inventory_item_id' => $this->otherItem->id,
        'quantity' => 40,
        'reserved_quantity' => 0,
    ]);

    $this->keeper = $this->world->restrictedActor($this->world->school->campus);
});

it("only lists the restricted user's own campus stock, even when another campus id is requested", function () {
    $response = $this->actingAs($this->keeper)
        ->getJson(route('inventory.stocks.all', ['campus_id' => $this->world->school->otherCampus->id]));

    $response->assertSuccessful();

    $campusIds = collect($response->json('data'))->pluck('campus_id')->unique()->all();

    expect($campusIds)->not->toContain($this->world->school->otherCampus->id);
});

it('lets a school-wide actor filter by whichever campus it asks for', function () {
    $response = $this->actingAs($this->world->school->actor)
        ->getJson(route('inventory.stocks.all', ['campus_id' => $this->world->school->otherCampus->id]));

    $response->assertSuccessful();

    $campusIds = collect($response->json('data'))->pluck('campus_id')->unique()->all();

    expect($campusIds)->toEqual([$this->world->school->otherCampus->id]);
});

it('refuses to reserve another campus\'s stock for a restricted user', function () {
    $response = $this->actingAs($this->keeper)
        ->postJson(route('inventory.stocks.reserve'), [
            'campus_id' => $this->world->school->otherCampus->id,
            'item_id' => $this->otherItem->id,
            'quantity' => 1,
        ]);

    // Scoped to the keeper's own campus, this item/stock pair does not
    // exist there, so the lookup 404s rather than touching the other
    // campus's stock.
    $response->assertNotFound();

    expect(InventoryStock::where('campus_id', $this->world->school->otherCampus->id)
        ->where('inventory_item_id', $this->otherItem->id)
        ->first()
        ->reserved_quantity)->toBe(0);
});
