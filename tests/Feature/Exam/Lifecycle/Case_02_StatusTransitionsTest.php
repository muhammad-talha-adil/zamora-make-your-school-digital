<?php

/**
 * Case 02 — the exam status dropdown.
 *
 * `changeStatus()` accepted any of the six statuses from any other, so a
 * `scheduled` exam could be jumped straight to `published`, or a `completed`
 * exam moved back to `scheduled`, with nothing in the database or the request
 * objecting. `ExamService::VALID_TRANSITIONS` now says which moves are real;
 * everything else is refused with the reason instead of silently applied.
 */

use App\Models\Exam\Exam;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
});

it('moves scheduled to active', function () {
    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'active'])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_ACTIVE);
});

it('moves active to marking', function () {
    $this->world->exam->update(['status' => 'active']);

    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'marking'])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_MARKING);
});

it('moves marking to published', function () {
    $this->world->exam->update(['status' => 'marking']);

    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'published'])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_PUBLISHED);
});

it('moves published to completed', function () {
    $this->world->exam->update(['status' => 'published']);

    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'completed'])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_COMPLETED);
});

it('lets marking go back to active for a correction', function () {
    $this->world->exam->update(['status' => 'marking']);

    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'active'])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_ACTIVE);
});

it('lets any unfinished exam be cancelled', function () {
    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'cancelled'])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_CANCELLED);
});

it('refuses to jump scheduled straight to published', function () {
    $response = $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'published'])
        ->assertStatus(422);

    expect($response->json('message'))->toContain('scheduled')->toContain('published');
    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_SCHEDULED);
});

it('refuses to move a completed exam anywhere', function () {
    $this->world->exam->update(['status' => 'completed']);

    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'active'])
        ->assertStatus(422);

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_COMPLETED);
});

it('refuses to reopen a cancelled exam', function () {
    $this->world->exam->update(['status' => 'cancelled']);

    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'scheduled'])
        ->assertStatus(422);

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_CANCELLED);
});

it('refuses to skip active and go straight from scheduled to marking', function () {
    $this->patchJson(route('exam.status', $this->world->exam->id), ['status' => 'marking'])
        ->assertStatus(422);

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_SCHEDULED);
});
