<?php

/**
 * Case 08 — one active structure per class/section, and the admission lookup
 * never picks one silently when several match.
 */

use Tests\Support\AdmissionWorld;

beforeEach(function () {
    $this->world = AdmissionWorld::make();
    $this->actingAs($this->world->actor);
});

function overlapPayload(AdmissionWorld $world, array $overrides = []): array
{
    return array_merge([
        'title' => 'Monthly Fee',
        'session_id' => $world->session->id,
        'class_id' => $world->class->id,
        'status' => 'active',
        'items' => [['fee_head_id' => $world->monthlyHead->id, 'amount' => 5000]],
    ], $overrides);
}

it('rejects a second active structure for the same class', function () {
    $this->world->feeStructure();

    $this->postJson(route('fee.structures.store'), overlapPayload($this->world))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'A fee structure already exists for Class 5. Edit it instead.');
});

it('names the section when the clash is on a section', function () {
    $this->world->feeStructure(['section_id' => $this->world->section->id, 'title' => 'Class 5 - A Fee']);

    $this->postJson(route('fee.structures.store'), overlapPayload($this->world, ['section_ids' => [$this->world->section->id]]))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'A fee structure already exists for Class 5-A. Edit it instead.');
});

it('allows a draft, and a different section, alongside an active structure', function () {
    $this->world->feeStructure();

    $this->post(route('fee.structures.store'), overlapPayload($this->world, ['status' => 'draft']))
        ->assertRedirect(route('fee.structures.index'));

    $this->post(route('fee.structures.store'), overlapPayload($this->world, ['section_ids' => [$this->world->section->id]]))
        ->assertRedirect(route('fee.structures.index'));
});

it('asks for a choice when several structures match the admission lookup', function () {
    $first = $this->world->feeStructure(['title' => 'Standard']);
    $second = $this->world->feeStructure(['title' => 'Scholarship']);
    $query = [
        'session_id' => $this->world->session->id,
        'campus_id' => $this->world->campus->id,
        'class_id' => $this->world->class->id,
    ];

    $this->getJson(route('fee.structures.by-scope', $query))
        ->assertOk()
        ->assertJsonPath('needs_choice', true)
        ->assertJsonCount(2, 'candidates');

    $this->getJson(route('fee.structures.by-scope', $query + ['fee_structure_id' => $second->id]))
        ->assertOk()
        ->assertJsonPath('data.id', $second->id)
        ->assertJsonCount(2, 'candidates');

    expect($first->id)->not->toBe($second->id);
});
