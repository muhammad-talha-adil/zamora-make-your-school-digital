<?php

/**
 * Student Document Types had a working backend (store/update) with no Vue
 * form anywhere to reach it — found during a full-app create/edit parity
 * audit. This covers the management UI added to students/Index.vue.
 */

use App\Models\StudentDocumentType;
use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

it('creates a document type through the real route', function () {
    $response = $this->postJson(route('students.document-types.store'), [
        'name' => 'Birth Certificate',
        'is_required' => true,
    ]);

    $response->assertSuccessful();

    $type = StudentDocumentType::where('name', 'Birth Certificate')->firstOrFail();
    expect($type->is_required)->toBeTrue()
        ->and($type->is_active)->toBeTrue();
});

it('updates a document type through the real route', function () {
    $type = StudentDocumentType::create(['name' => 'CNIC Copy', 'is_required' => false, 'is_active' => true]);

    $response = $this->putJson(route('students.document-types.update', $type), [
        'name' => 'CNIC Copy (Both Sides)',
        'is_required' => true,
        'is_active' => true,
    ]);

    $response->assertSuccessful();

    $fresh = $type->fresh();
    expect($fresh->name)->toBe('CNIC Copy (Both Sides)')
        ->and($fresh->is_required)->toBeTrue();
});

it('lists document types for the picker', function () {
    StudentDocumentType::create(['name' => 'Report Card', 'is_required' => false, 'is_active' => true]);

    $response = $this->getJson(route('students.document-types.index'));

    $response->assertSuccessful();
    expect(collect($response->json('data'))->pluck('name'))->toContain('Report Card');
});

it('rejects a duplicate name', function () {
    StudentDocumentType::create(['name' => 'Passport Photo', 'is_required' => false, 'is_active' => true]);

    $this->postJson(route('students.document-types.store'), [
        'name' => 'Passport Photo',
    ])->assertUnprocessable()->assertJsonValidationErrors('name');
});
