<?php

/**
 * `ExamTypeController` had no authorization at all, and its soft-delete
 * convention (`onlyTrashed()`/`restore()`/`forceDelete()`, mirroring every
 * sibling Settings controller) was wired onto an `ExamType` model that had
 * neither the `SoftDeletes` trait nor a `deleted_at` column — so permanently
 * deleting an exam type (the only delete action the UI actually calls, from
 * `ExamTypesTable.vue`'s "Inactive Exam Types" view) crashed with a
 * `BadMethodCallException` on every attempt.
 */

use App\Models\Exam\ExamType;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->outsider = $this->world->staff('driver');
});

it('turns an unpermissioned account away from every exam type action', function () {
    $this->actingAs($this->outsider)->get(route('exam-types.index'))->assertForbidden();

    $this->actingAs($this->outsider)->post(route('exam-types.store'), [
        'name' => 'Second Term',
    ])->assertForbidden();

    $this->actingAs($this->outsider)->patch(route('exam-types.inactivate', $this->world->examType))
        ->assertForbidden();

    $this->actingAs($this->outsider)->delete(route('exam-types.destroy', $this->world->examType))
        ->assertForbidden();
});

it('lets the actor with exam settings abilities through', function () {
    $this->actingAs($this->world->school->actor)
        ->getJson(route('exam-types.all'))
        ->assertOk();
});

it('soft deletes, restores and permanently deletes an exam type without crashing', function () {
    $examType = ExamType::create(['name' => 'Second Term', 'short_name' => 'ST', 'is_active' => true]);
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->delete(route('exam-types.destroy', $examType))->assertRedirect();
    expect(ExamType::find($examType->id))->toBeNull();
    expect(ExamType::onlyTrashed()->find($examType->id))->not->toBeNull();

    $this->actingAs($actor)->patch(route('exam-types.restore', $examType->id))->assertRedirect();
    expect(ExamType::find($examType->id))->not->toBeNull();

    $this->actingAs($actor)->delete(route('exam-types.destroy', $examType))->assertRedirect();
    $this->actingAs($actor)->delete(route('exam-types.force-delete', $examType->id))->assertRedirect();
    expect(ExamType::withTrashed()->find($examType->id))->toBeNull();
});

it('refuses to permanently delete an exam type that is in use by an exam', function () {
    $actor = $this->world->school->actor;

    // Soft delete succeeds — it is only an UPDATE, so the `exam_type_id`
    // restrict constraint on `exams` does not see it.
    $this->actingAs($actor)->delete(route('exam-types.destroy', $this->world->examType))
        ->assertRedirect();
    expect(ExamType::onlyTrashed()->find($this->world->examType->id))->not->toBeNull();

    // Permanently deleting it is a real DELETE, which the exam it types still
    // points at, so it must be refused rather than crash.
    $response = $this->actingAs($actor)
        ->delete(route('exam-types.force-delete', $this->world->examType->id));

    $response->assertRedirect();
    expect(ExamType::withTrashed()->find($this->world->examType->id))->not->toBeNull();
    expect(session('error'))->not->toBeNull();
});
