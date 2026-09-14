<?php

use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use App\Models\InventoryType;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\ReturnModel;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentInventory;
use App\Models\Supplier;
use Tests\Support\InventoryWorld;

/**
 * Single-record `show`/`edit`/`update`/`destroy` endpoints used to scope
 * campus access via an independent, optional `campus_id` request parameter
 * rather than authorizing the resolved model instance — a campus-restricted
 * user could omit it, or spoof another campus's id, and still reach a record
 * that belongs to another campus. Policies built on `ChecksSchoolReach`
 * (mirroring `TransportVehiclePolicy`) now authorize the instance itself via
 * `Gate::authorize()`, so a mismatch 403s instead of silently no-opping or
 * (worse) succeeding because no `campus_id` was supplied at all.
 */
beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->keeper = $this->world->restrictedActor($this->world->school->campus);
});

it("forbids a campus-restricted user from updating another campus's inventory item", function () {
    $otherItem = $this->world->itemAtOtherCampus();

    $this->actingAs($this->keeper)
        ->putJson(route('inventory.items.update', $otherItem), [
            'name' => 'Hijacked',
            'campus_id' => $this->world->school->otherCampus->id,
            'inventory_type_id' => $otherItem->inventory_type_id,
        ])
        ->assertForbidden();

    expect($otherItem->fresh()->name)->not->toBe('Hijacked');
});

it("allows a campus-restricted user to update their own campus's inventory item", function () {
    $this->actingAs($this->keeper)
        ->put(route('inventory.items.update', $this->world->item), [
            'name' => 'Renamed Notebook',
            'campus_id' => $this->world->school->campus->id,
            'inventory_type_id' => $this->world->item->inventory_type_id,
        ])
        ->assertRedirect();

    expect($this->world->item->fresh()->name)->toBe('Renamed Notebook');
});

it("forbids a campus-restricted user from deleting another campus's inventory item", function () {
    $otherItem = $this->world->itemAtOtherCampus();

    $this->actingAs($this->keeper)
        ->deleteJson(route('inventory.items.destroy', $otherItem))
        ->assertForbidden();

    expect(InventoryItem::find($otherItem->id))->not->toBeNull();
});

it("forbids a campus-restricted user from updating another campus's inventory type", function () {
    $otherType = InventoryType::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'name' => 'Other Campus Type',
        'is_active' => true,
    ]);

    $this->actingAs($this->keeper)
        ->putJson(route('inventory.types.update', $otherType), [
            'name' => 'Hijacked Type',
            'campus_id' => $this->world->school->otherCampus->id,
        ])
        ->assertForbidden();

    expect($otherType->fresh()->name)->not->toBe('Hijacked Type');
});

it("forbids a campus-restricted user from viewing another campus's supplier", function () {
    $otherSupplier = Supplier::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'name' => 'Other Campus Supplier',
        'is_active' => true,
    ]);

    $this->actingAs($this->keeper)
        ->get(route('inventory.suppliers.show', $otherSupplier))
        ->assertForbidden();
});

it("forbids a campus-restricted user from updating another campus's supplier", function () {
    $otherSupplier = Supplier::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'name' => 'Other Campus Supplier',
        'is_active' => true,
    ]);

    $this->actingAs($this->keeper)
        ->putJson(route('inventory.suppliers.update', $otherSupplier), [
            'name' => 'Hijacked Supplier',
        ])
        ->assertForbidden();

    expect($otherSupplier->fresh()->name)->not->toBe('Hijacked Supplier');
});

it("forbids a campus-restricted user from viewing another campus's purchase", function () {
    $otherPurchase = Purchase::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'total_amount' => 100,
    ]);

    $this->actingAs($this->keeper)
        ->get(route('inventory.purchases.details', $otherPurchase))
        ->assertForbidden();
});

it("forbids a campus-restricted user from deleting another campus's purchase", function () {
    $otherPurchase = Purchase::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'total_amount' => 100,
    ]);

    $this->actingAs($this->keeper)
        ->deleteJson(route('inventory.purchases.destroy', $otherPurchase))
        ->assertForbidden();

    expect(Purchase::find($otherPurchase->id))->not->toBeNull();
});

it("forbids a campus-restricted user from viewing another campus's purchase return", function () {
    $otherReturn = PurchaseReturn::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'user_id' => $this->world->school->actor->id,
        'return_number' => 'PR-OTHER-1',
        'return_date' => now()->toDateString(),
        'total_amount' => 0,
    ]);

    $this->actingAs($this->keeper)
        ->get(route('inventory.purchase-returns.show', $otherReturn))
        ->assertForbidden();
});

it("forbids a campus-restricted user from viewing another campus's stock adjustment", function () {
    $otherItem = $this->world->itemAtOtherCampus();
    $otherAdjustment = InventoryAdjustment::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'inventory_item_id' => $otherItem->id,
        'type' => 'add',
        'quantity' => 5,
        'previous_quantity' => 0,
        'new_quantity' => 5,
        'reason' => 'Other campus stock take',
    ]);

    $this->actingAs($this->keeper)
        ->get(route('inventory.adjustments.show', $otherAdjustment))
        ->assertForbidden();
});

it("forbids a campus-restricted user from deleting another campus's stock adjustment", function () {
    $otherItem = $this->world->itemAtOtherCampus();
    $otherAdjustment = InventoryAdjustment::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'inventory_item_id' => $otherItem->id,
        'type' => 'add',
        'quantity' => 5,
        'previous_quantity' => 0,
        'new_quantity' => 5,
        'reason' => 'Other campus stock take',
    ]);

    $this->actingAs($this->keeper)
        ->delete(route('inventory.adjustments.destroy', $otherAdjustment))
        ->assertForbidden();

    expect(InventoryAdjustment::find($otherAdjustment->id))->not->toBeNull();
});

it("forbids a campus-restricted user from viewing another campus's student-to-school return", function () {
    $otherStudent = Student::create([
        'user_id' => $this->world->school->actor->id,
        'registration_no' => 'REG-OTHER-1',
        'student_code' => 'STU-OTHER-1',
        'admission_no' => 'ADM-OTHER-1',
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $this->world->school->maleGender->id,
        'student_status_id' => $this->world->school->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);

    $otherStudentInventory = StudentInventory::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'student_id' => $otherStudent->id,
        'total_amount' => 0,
        'total_discount' => 0,
        'final_amount' => 0,
        'assigned_date' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    $otherReturn = ReturnModel::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'student_inventory_id' => $otherStudentInventory->id,
        'quantity' => 1,
        'return_date' => now()->toDateString(),
    ]);

    $this->actingAs($this->keeper)
        ->get(route('inventory.returns.show', $otherReturn))
        ->assertForbidden();
});

it("forbids a campus-restricted user from viewing another campus's student inventory assignment", function () {
    $otherStudent = Student::create([
        'user_id' => $this->world->school->actor->id,
        'registration_no' => 'REG-OTHER-2',
        'student_code' => 'STU-OTHER-2',
        'admission_no' => 'ADM-OTHER-2',
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $this->world->school->maleGender->id,
        'student_status_id' => $this->world->school->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $otherStudent->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'campus_id' => $this->world->school->otherCampus->id,
        'admission_date' => now()->toDateString(),
        'student_status_id' => $this->world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    $otherStudentInventory = StudentInventory::create([
        'campus_id' => $this->world->school->otherCampus->id,
        'student_id' => $otherStudent->id,
        'total_amount' => 0,
        'total_discount' => 0,
        'final_amount' => 0,
        'assigned_date' => now()->toDateString(),
        'status' => 'assigned',
    ]);

    $this->actingAs($this->keeper)
        ->get(route('inventory.student-inventory.show', $otherStudentInventory))
        ->assertForbidden();
});

it('lets a school-wide actor reach every campus through the same single-record endpoints', function () {
    $otherItem = $this->world->itemAtOtherCampus();

    $this->actingAs($this->world->school->actor)
        ->put(route('inventory.items.update', $otherItem), [
            'name' => 'Renamed By Admin',
            'campus_id' => $this->world->school->otherCampus->id,
            'inventory_type_id' => $otherItem->inventory_type_id,
        ])
        ->assertRedirect();

    expect($otherItem->fresh()->name)->toBe('Renamed By Admin');
});
