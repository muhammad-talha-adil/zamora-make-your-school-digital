<?php

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryType;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\StudentInventoryItem;
use Tests\Support\InventoryWorld;

beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->student = Student::create([
        'user_id' => $this->world->school->actor->id,
        'registration_no' => 'REG-INV-10',
        'student_code' => 'STU-INV-10',
        'admission_no' => 'ADM-INV-10',
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $this->world->school->maleGender->id,
        'student_status_id' => $this->world->school->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);
});

it('deletes an item that was never used', function () {
    $this->deleteJson(route('inventory.items.destroy', $this->world->item->id))
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    expect(InventoryItem::withoutGlobalScopes()->find($this->world->item->id)->trashed())->toBeTrue();
});

it('refuses to delete an item already issued to a student, with a friendly message', function () {
    $record = StudentInventory::create([
        'student_inventory_id' => 'SI-TEST-1',
        'campus_id' => $this->world->school->campus->id,
        'student_id' => $this->student->id,
        'assigned_date' => now()->toDateString(),
    ]);
    StudentInventoryItem::create([
        'student_inventory_record_id' => $record->id,
        'campus_id' => $this->world->school->campus->id,
        'student_id' => $this->student->id,
        'inventory_item_id' => $this->world->item->id,
        'quantity' => 1,
        'unit_price_snapshot' => 10,
        'item_name_snapshot' => 'Notebook',
        'status' => 'assigned',
        'assigned_date' => now()->toDateString(),
    ]);

    $this->deleteJson(route('inventory.items.destroy', $this->world->item->id))
        ->assertUnprocessable()
        ->assertJsonPath('message', fn (string $message) => str_contains($message, 'cannot be deleted'));
});

it('refuses to delete a type that still has items', function () {
    $this->deleteJson(route('inventory.types.destroy', $this->world->type->id))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect(InventoryType::find($this->world->type->id))->not->toBeNull();
});

it('rejects a negative purchase quantity instead of saving it as positive', function () {
    $this->postJson(route('inventory.purchases.store'), [
        'campus_id' => $this->world->school->campus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'purchase_items' => [
            ['inventory_item_id' => $this->world->item->id, 'quantity' => -5, 'purchase_rate' => 50, 'sale_rate' => 80],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['purchase_items.0.quantity' => 'Quantity must be at least 1.']);

    expect($this->world->stockAt()->fresh()->quantity)->toBe(0);
});

it('rejects a negative add or subtract adjustment with the same message', function (string $type) {
    $this->world->stockAt()->update(['quantity' => 5]);

    $this->postJson(route('inventory.adjustments.store'), [
        'campus_id' => $this->world->school->campus->id,
        'inventory_item_id' => $this->world->item->id,
        'type' => $type,
        'quantity' => -3,
        'reason' => 'Typo',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.quantity.0', 'Quantity must be at least 1.');

    expect($this->world->stockAt()->fresh()->quantity)->toBe(5);
})->with(['add', 'subtract']);

it('rejects a negative student assignment quantity', function () {
    $this->world->stockAt()->update(['quantity' => 5]);

    $this->postJson('/inventory/student-inventory/assign', [
        'campus_id' => $this->world->school->campus->id,
        'student_id' => $this->student->id,
        'inventory_item_id' => $this->world->item->id,
        'quantity' => -2,
        'assigned_date' => now()->toDateString(),
    ])->assertUnprocessable();
});

it('keeps dashboard counts, low stock list and breakdowns from one source', function () {
    InventoryType::create(['campus_id' => null, 'name' => 'Shared type', 'is_active' => true]);
    InventoryStock::create([
        'campus_id' => $this->world->school->campus->id,
        'inventory_item_id' => $this->world->item->id,
        'quantity' => 2,
        'reserved_quantity' => 0,
    ]);

    $data = $this->getJson(route('inventory.dashboard-data'))->assertSuccessful()->json();

    expect($data['stats']['types'])->toBe(2)
        ->and($data['stats']['lowStockItems'])->toBe(1)
        ->and($data['low_stock_items'])->toHaveCount(1)
        ->and(array_is_list($data['low_stock_items']))->toBeTrue()
        ->and($data['by_type'])->toHaveCount(2)
        ->and($data['stock_status'])->toBe(['healthy' => 0, 'low' => 1, 'out' => 0]);
});

it('lists every active item on the purchase item lookup, including zero stock and unpurchased from a supplier', function () {
    $this->getJson(route('inventory.items.all', ['campus_id' => $this->world->school->campus->id]))
        ->assertSuccessful()
        ->assertJsonFragment(['id' => $this->world->item->id]);
});

it('shows item names and the adjuster on the purchase and adjustment lists', function () {
    $this->postJson(route('inventory.purchases.store'), [
        'campus_id' => $this->world->school->campus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'purchase_items' => [
            ['inventory_item_id' => $this->world->item->id, 'quantity' => 4, 'purchase_rate' => 10, 'sale_rate' => 12],
        ],
    ])->assertSuccessful();
    $this->postJson(route('inventory.adjustments.store'), [
        'campus_id' => $this->world->school->campus->id,
        'inventory_item_id' => $this->world->item->id,
        'type' => 'add',
        'quantity' => 1,
        'reason' => 'Found one',
    ])->assertSuccessful();

    $this->getJson(route('inventory.purchases.all', ['campus_id' => $this->world->school->campus->id]))
        ->assertJsonPath('data.0.item_names', 'Notebook');
    $this->getJson(route('inventory.adjustments.all', ['campus_id' => $this->world->school->campus->id]))
        ->assertJsonPath('0.user_name', $this->world->school->actor->name);
});
