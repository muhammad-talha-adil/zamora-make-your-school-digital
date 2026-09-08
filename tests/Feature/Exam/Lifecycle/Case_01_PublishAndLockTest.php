<?php

/**
 * Case 01 — publishing an exam, and closing it.
 *
 * `publish()` was `$exam->update(['published_at' => now()])`. The status still
 * said `marking`, so a screen filtering on status and a screen reading the
 * timestamp gave different answers about the same exam. And an exam with no
 * marks in it at all published without a murmur.
 *
 * `lock()` wrote the time and never the person, on a column that exists and has
 * a foreign key to `users` — so "who closed this exam" could not be answered.
 * `unlock()` left the time behind, so a reopened exam still carried the moment
 * it was shut.
 */

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Services\Exam\ExamRegistrationService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actor = $this->world->school->withFullRoles()->actor;
    $this->actingAs($this->actor);
    $this->students = $this->world->enrol(2);
    $this->world->paper('Mathematics');
});

/** Registers the class and marks every child, so the exam is ready. */
function markEveryone(ExamWorld $world): void
{
    app(ExamRegistrationService::class)->generateFromEnrollments(
        $world->exam->id,
        $world->school->class->id,
        null
    );

    foreach ($world->students as $student) {
        test()->postJson(
            route('exam.marking.save-row'),
            $world->marksPayload($student, ['Mathematics' => ['obtained' => 70]])
        )->assertSuccessful();
    }
}

it('moves the status with the timestamp', function () {
    markEveryone($this->world);

    $this->patchJson(route('exam.publish', $this->world->exam->id))->assertSuccessful();

    $exam = $this->world->exam->fresh();

    expect($exam->status)->toBe(Exam::STATUS_PUBLISHED)
        ->and($exam->published_at)->not->toBeNull();
});

it('refuses to publish an exam with no marks in it', function () {
    $this->patchJson(route('exam.publish', $this->world->exam->id))
        ->assertStatus(422);

    expect($this->world->exam->fresh()->published_at)->toBeNull();
});

it('says what is missing rather than only refusing', function () {
    $problems = $this->patchJson(route('exam.publish', $this->world->exam->id))
        ->assertStatus(422)
        ->json('errors.exam');

    // A school told only "not ready" will publish it anyway.
    expect($problems)->toContain('No children are registered for this exam.')
        ->and($problems)->toContain('No marks have been entered for this exam.');
});

it('refuses an exam with a child still unmarked', function () {
    app(ExamRegistrationService::class)->generateFromEnrollments(
        $this->world->exam->id, $this->world->school->class->id, null
    );

    // Only one of the two is marked.
    $this->postJson(
        route('exam.marking.save-row'),
        $this->world->marksPayload($this->students[0], ['Mathematics' => ['obtained' => 70]])
    );

    $this->patchJson(route('exam.publish', $this->world->exam->id))
        ->assertStatus(422)
        ->assertJsonPath('errors.exam.0', fn ($problem) => str_contains($problem, 'no marks at all'));
});

it('publishes anyway when the school says so', function () {
    $this->patchJson(route('exam.publish', $this->world->exam->id), ['force' => true])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_PUBLISHED);
});

it('publishes the results with the exam', function () {
    markEveryone($this->world);

    $this->patchJson(route('exam.publish', $this->world->exam->id))->assertSuccessful();

    expect(ExamResultHeader::where('exam_id', $this->world->exam->id)
        ->where('status', ExamResultHeader::STATUS_PUBLISHED)->count())->toBe(2);
});

it('reports readiness before anybody presses publish', function () {
    markEveryone($this->world);

    $this->getJson(route('exam.readiness', $this->world->exam->id))
        ->assertSuccessful()
        ->assertJsonPath('data.is_ready', true);
});

it('takes a published result back off the board', function () {
    markEveryone($this->world);
    $this->patchJson(route('exam.publish', $this->world->exam->id));

    $this->patchJson(route('exam.unpublish', $this->world->exam->id))->assertSuccessful();

    $exam = $this->world->exam->fresh();

    expect($exam->status)->toBe(Exam::STATUS_MARKING)
        ->and($exam->published_at)->toBeNull()
        ->and(ExamResultHeader::where('exam_id', $exam->id)
            ->where('status', ExamResultHeader::STATUS_PUBLISHED)->count())->toBe(0);
});

it('does not unpublish an exam because somebody edited its name', function () {
    markEveryone($this->world);
    $this->patchJson(route('exam.publish', $this->world->exam->id));

    // The edit screen posts through the Inertia route, which redirects back.
    $this->put(route('exam.update', $this->world->exam->id), [
        'session_id' => $this->world->school->session->id,
        'exam_type_id' => $this->world->examType->id,
        'name' => 'First Term 2026 (revised)',
        'start_date' => '2026-10-05',
        'end_date' => '2026-10-15',
    ])->assertRedirect();

    // The dates decide the status only while nobody has decided it deliberately.
    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_PUBLISHED);
});

it('records who closed the exam', function () {
    $this->patchJson(route('exam.lock', $this->world->exam->id))->assertSuccessful();

    $exam = $this->world->exam->fresh();

    expect($exam->is_locked)->toBeTrue()
        ->and($exam->locked_by)->toBe($this->actor->id)
        ->and($exam->locked_at)->not->toBeNull();
});

it('clears the record of closing when the exam is reopened', function () {
    $this->patchJson(route('exam.lock', $this->world->exam->id));
    $this->patchJson(route('exam.unlock', $this->world->exam->id))->assertSuccessful();

    $exam = $this->world->exam->fresh();

    expect($exam->is_locked)->toBeFalse()
        ->and($exam->locked_at)->toBeNull()
        ->and($exam->locked_by)->toBeNull();
});
