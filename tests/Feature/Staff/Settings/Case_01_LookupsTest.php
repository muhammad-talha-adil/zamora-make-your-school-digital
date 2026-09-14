<?php

use App\Models\Staff\StaffDocumentType;
use App\Models\StaffDepartment;
use Tests\Support\StaffWorld;

/**
 * The staff settings screen: departments and designations (moved from the
 * dialog on `Staff/People/Index.vue`, backend untouched) alongside the new
 * document types lookup.
 */
beforeEach(function () {
    $this->world = StaffWorld::make();
});

it('renders the settings page for someone who may manage the structure', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $this->actingAs($viewer->user)->get(route('staff.settings.page'))->assertOk();
});

it('a person without staff.department.manage may not open the settings page', function () {
    $viewer = $this->world->person('No Rights', ['staff.view']);

    $this->actingAs($viewer->user)->get(route('staff.settings.page'))->assertForbidden();
});

it('creates a department from the settings page, exactly as the old dialog did', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $response = $this->actingAs($viewer->user)->postJson(route('staff.departments.store'), [
        'name' => 'Facilities',
        'description' => 'Buildings and grounds',
    ]);

    $response->assertSuccessful();
    expect(StaffDepartment::where('name', 'Facilities')->exists())->toBeTrue();
});

it('updates a designation from the settings page', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $response = $this->actingAs($viewer->user)->putJson(
        route('staff.designations.update', $this->world->teacherPost),
        ['name' => 'Senior Teacher', 'description' => 'Updated', 'is_active' => true]
    );

    $response->assertSuccessful();
    expect($this->world->teacherPost->fresh()->name)->toBe('Senior Teacher');
});

it('a person without staff.department.manage may not manage departments or designations', function () {
    $outsider = $this->world->person('No Rights', ['staff.view']);

    $this->actingAs($outsider->user)->postJson(route('staff.departments.store'), ['name' => 'Nope'])
        ->assertForbidden();

    $this->actingAs($outsider->user)->putJson(route('staff.designations.update', $this->world->teacherPost), ['name' => 'Nope'])
        ->assertForbidden();
});

it('creates a document type', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);

    $response = $this->actingAs($viewer->user)->postJson(route('staff.document-types.store'), [
        'name' => 'Experience Letter',
    ]);

    $response->assertSuccessful();
    expect(StaffDocumentType::where('name', 'Experience Letter')->where('is_active', true)->exists())->toBeTrue();
});

it('refuses a duplicate document type name', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);
    StaffDocumentType::create(['name' => 'CNIC', 'is_active' => true]);

    $this->actingAs($viewer->user)->postJson(route('staff.document-types.store'), ['name' => 'CNIC'])
        ->assertUnprocessable();
});

it('updates a document type', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);
    $type = StaffDocumentType::create(['name' => 'Medical', 'is_active' => true]);

    $response = $this->actingAs($viewer->user)->putJson(route('staff.document-types.update', $type), [
        'name' => 'Medical Certificate',
        'is_active' => true,
    ]);

    $response->assertSuccessful();
    expect($type->fresh()->name)->toBe('Medical Certificate');
});

it('soft-deletes a document type without touching documents already filed under it', function () {
    $viewer = $this->world->person('Head Office', ['staff.department.manage']);
    $staff = $this->world->person('Zainab Ali');
    $type = StaffDocumentType::create(['name' => 'Contract', 'is_active' => true]);

    $document = $staff->documents()->create([
        'kind' => $type->name,
        'title' => 'Employment contract',
    ]);

    $this->actingAs($viewer->user)->deleteJson(route('staff.document-types.destroy', $type))
        ->assertSuccessful();

    expect(StaffDocumentType::find($type->id))->toBeNull();
    expect(StaffDocumentType::withTrashed()->find($type->id))->not->toBeNull();
    expect($document->fresh()->kind)->toBe('Contract');
});

it('a person without staff.department.manage may not manage document types', function () {
    $outsider = $this->world->person('No Rights', ['staff.view']);
    $type = StaffDocumentType::create(['name' => 'Medical', 'is_active' => true]);

    $this->actingAs($outsider->user)->postJson(route('staff.document-types.store'), ['name' => 'Nope'])
        ->assertForbidden();

    $this->actingAs($outsider->user)->putJson(route('staff.document-types.update', $type), ['name' => 'Nope'])
        ->assertForbidden();

    $this->actingAs($outsider->user)->deleteJson(route('staff.document-types.destroy', $type))
        ->assertForbidden();
});

it('anybody who may view staff can list document types for the document-add dropdown', function () {
    $viewer = $this->world->person('Regular Viewer', ['staff.view']);
    StaffDocumentType::create(['name' => 'Degree', 'is_active' => true]);

    $this->actingAs($viewer->user)->getJson(route('staff.document-types.index'))
        ->assertSuccessful()
        ->assertJsonFragment(['name' => 'Degree']);
});
