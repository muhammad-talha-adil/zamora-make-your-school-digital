<?php

use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentInventory;
use Tests\Support\InventoryWorld;

/**
 * Issuing inventory to a student must move quantity out of the campus's
 * available stock, and a return must bring it back — without ever letting a
 * student receive more than the campus has on hand.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();

    $this->student = Student::create([
        'user_id' => $this->world->school->actor->id,
        'registration_no' => 'REG-INV-1',
        'student_code' => 'STU-INV-1',
        'admission_no' => 'ADM-INV-1',
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $this->world->school->maleGender->id,
        'student_status_id' => $this->world->school->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $this->student->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'campus_id' => $this->world->school->campus->id,
        'admission_date' => now()->toDateString(),
        'student_status_id' => $this->world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    // A stock row created directly (not through a purchase) so sale_rate on
    // the item stays 0 — assign() bills at the last purchase's sale_rate, and
    // keeping it 0 keeps the assignment out of the accounting/COA setup a
    // priced assignment would otherwise need.
    $this->world->stockAt()->update(['quantity' => 10]);
});

function inventoryAssignPayload(InventoryWorld $world, Student $student, int $quantity): array
{
    return [
        'campus_id' => $world->school->campus->id,
        'student_id' => $student->id,
        'items' => [
            ['inventory_item_id' => $world->item->id, 'quantity' => $quantity],
        ],
        'assigned_date' => now()->toDateString(),
    ];
}

it('deducts available stock when inventory is assigned to a student', function () {
    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.student-inventory.assign'), inventoryAssignPayload($this->world, $this->student, 4))
        ->assertRedirect();

    $stock = $this->world->stockAt()->fresh();

    expect($stock->quantity)->toBe(6);
    expect($stock->reserved_quantity)->toBe(4);
    // available_quantity is quantity - reserved_quantity: 6 - 4.
    expect($stock->available_quantity)->toBe(2);
    expect(StudentInventory::count())->toBe(1);
});

it('refuses to assign more than the campus has available', function () {
    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.student-inventory.assign'), inventoryAssignPayload($this->world, $this->student, 999))
        ->assertRedirect();

    // The transaction throws and rolls back — no record created, stock untouched.
    expect(StudentInventory::count())->toBe(0);
    expect($this->world->stockAt()->fresh()->quantity)->toBe(10);
});

it('restores stock when an assignment is returned', function () {
    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.student-inventory.assign'), inventoryAssignPayload($this->world, $this->student, 4));

    $record = StudentInventory::firstOrFail();
    $item = $record->items->first();

    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.student-inventory.return.process'), [
            'student_inventory_record_id' => $record->id,
            'campus_id' => $this->world->school->campus->id,
            'items' => [
                ['student_inventory_item_id' => $item->id, 'quantity' => 4],
            ],
            'return_date' => now()->toDateString(),
        ])
        ->assertSuccessful();

    $stock = $this->world->stockAt()->fresh();
    expect($stock->quantity)->toBe(10);
    expect($stock->reserved_quantity)->toBe(0);
    expect($record->fresh()->status)->toBe('returned');
});

it('refuses to return more than remains assigned', function () {
    $this->actingAs($this->world->school->actor)
        ->post(route('inventory.student-inventory.assign'), inventoryAssignPayload($this->world, $this->student, 4));

    $record = StudentInventory::firstOrFail();
    $item = $record->items->first();

    $this->actingAs($this->world->school->actor)
        ->postJson(route('inventory.student-inventory.return.process'), [
            'student_inventory_record_id' => $record->id,
            'campus_id' => $this->world->school->campus->id,
            'items' => [
                ['student_inventory_item_id' => $item->id, 'quantity' => 10],
            ],
            'return_date' => now()->toDateString(),
        ])
        ->assertUnprocessable();

    expect($this->world->stockAt()->fresh()->reserved_quantity)->toBe(4);
});
