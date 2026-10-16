<?php

/**
 * Case 07 — the student admission form's fee-structure lookup
 * (`FeeStructureController::getByScope`, behind `fee.structures.by-scope`,
 * the endpoint `useFeeStructure.ts` calls once Branch/Session/Class/Section
 * are picked on the admission form).
 */

use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->structure = $this->world->structureWithAllFrequencies();
});

it('finds the class-level structure for the matching campus/session/class', function () {
    $response = $this->getJson(route('fee.structures.by-scope', [
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
    ]));

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('data.id', $this->structure->id);
});

it('finds nothing for a class with no matching fee structure', function () {
    $response = $this->getJson(route('fee.structures.by-scope', [
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id + 999,
    ]));

    // The endpoint reports "not found" via `success: false` in a 200 body
    // (the admission form's composable reads response.data.success, not
    // the HTTP status), not a 404.
    $response->assertOk();
    $response->assertJsonPath('success', false);
});

it('requires session and campus', function () {
    $this->getJson(route('fee.structures.by-scope', [
        'class_id' => $this->world->school->class->id,
    ]))->assertStatus(400);
});
