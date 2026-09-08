<?php

/**
 * Case 01 — asking for a paper to be rechecked.
 *
 * Three tables and a controller existed and none of it worked. The one action
 * that wrote anything wrote `exam_id`, `student_id`, `exam_paper_id` and
 * `expected_marks` — four columns the table does not have — so every submission
 * threw. `index()` filtered on a fifth. Approve, reject and apply returned a
 * sentence of English and changed nothing at all.
 *
 * What a school here actually does: the result comes out, a parent applies for
 * a recheck of one paper, somebody looks at the script, and the mark either
 * stands or is corrected — with the old mark kept, because that is the first
 * thing the parent asks about.
 */

use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Models\Exam\ExamRevaluationRequest;
use App\Services\Exam\ExamLifecycleService;
use App\Services\Exam\ExamRevaluationService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actor = $this->world->school->withFullRoles()->actor;
    $this->actingAs($this->actor);
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics');
    $this->world->paper('English');

    $this->postJson(
        route('exam.marking.save-row'),
        $this->world->marksPayload($this->students[0], [
            'Mathematics' => ['obtained' => 55],
            'English' => ['obtained' => 65],
        ])
    )->assertSuccessful();

    $this->header = ExamResultHeader::where('exam_id', $this->world->exam->id)->firstOrFail();
    $this->maths = ExamResultLine::where('result_header_id', $this->header->id)
        ->where('exam_paper_id', $this->world->papers['Mathematics']->id)
        ->firstOrFail();
});

/** Publishes the exam, so a result exists to disagree with. */
function publishExam(ExamWorld $world): void
{
    app(ExamLifecycleService::class)->publish($world->exam, null, force: true);
}

/** Applies for a recheck of the maths paper. */
function applyForRecheck(int $lineId, string $reason = 'The total on the front page does not add up.')
{
    return test()->postJson(route('exam.revaluations.request'), [
        'exam_result_line_id' => $lineId,
        'reason' => $reason,
    ]);
}

it('refuses a recheck before the result is out', function () {
    applyForRecheck($this->maths->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors('exam_result_line_id');
});

it('accepts a recheck once the result is published', function () {
    publishExam($this->world);

    applyForRecheck($this->maths->id)->assertCreated();

    expect(ExamRevaluationRequest::where('exam_result_line_id', $this->maths->id)->exists())
        ->toBeTrue();
});

it('accepts the child and the paper instead of the line', function () {
    publishExam($this->world);

    // What the screen actually posts.
    $this->postJson(route('exam.revaluations.request'), [
        'exam_id' => $this->world->exam->id,
        'student_id' => $this->students[0]->id,
        'exam_paper_id' => $this->world->papers['Mathematics']->id,
        'reason' => 'Question 4 looks unmarked.',
    ])->assertCreated();

    expect(ExamRevaluationRequest::first()->exam_result_line_id)->toBe($this->maths->id);
});

it('refuses a recheck of a paper the child has no mark for', function () {
    publishExam($this->world);

    $otherPaper = $this->world->paper('Physics');

    $this->postJson(route('exam.revaluations.request'), [
        'exam_id' => $this->world->exam->id,
        'student_id' => $this->students[0]->id,
        'exam_paper_id' => $otherPaper->id,
    ])->assertStatus(422);
});

it('refuses a second application while the first is open', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id)->assertCreated();

    applyForRecheck($this->maths->id)->assertStatus(422);
});

it('refuses a recheck on a locked exam', function () {
    publishExam($this->world);
    $this->world->exam->update(['is_locked' => true]);

    applyForRecheck($this->maths->id)->assertStatus(422);
});

it('records who applied and why', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id, 'Question 4 looks unmarked.');

    $request = ExamRevaluationRequest::firstOrFail();

    expect($request->requested_by)->toBe($this->actor->id)
        ->and($request->reason)->toBe('Question 4 looks unmarked.')
        ->and($request->status)->toBe(ExamRevaluationService::STATUS_PENDING);
});

it('lists the rechecks for an exam', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);

    // `exam_id` is not on this table; it used to be queried as though it were.
    $this->getJson(route('exam.revaluations.index', ['exam_id' => $this->world->exam->id]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('leaves the mark alone when the recheck is rejected', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.reject', $request->id), [
        'note' => 'Totalled correctly. The mark stands.',
    ])->assertSuccessful();

    expect($request->fresh()->status)->toBe(ExamRevaluationService::STATUS_REJECTED)
        ->and((float) $this->maths->fresh()->obtained_marks)->toBe(55.0);
});

it('keeps a record that the script was looked at even when nothing changed', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.reject', $request->id), ['note' => 'The mark stands.']);

    $this->getJson(route('exam.revaluations.history', $request->id))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.note', 'The mark stands.');
});

it('does not change the mark on approval alone', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 72])
        ->assertSuccessful();

    // Approved on Tuesday, result cards reprinted on Thursday.
    expect($request->fresh()->status)->toBe(ExamRevaluationService::STATUS_APPROVED)
        ->and((float) $this->maths->fresh()->obtained_marks)->toBe(55.0);
});

it('refuses a corrected mark above the paper total', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 150])
        ->assertStatus(422)
        ->assertJsonValidationErrors('new_marks');
});

it('writes the corrected mark when the change is applied', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 72]);
    $this->patchJson(route('exam.revaluations.apply-change', $request->id))->assertSuccessful();

    expect((float) $this->maths->fresh()->obtained_marks)->toBe(72.0)
        ->and($request->fresh()->status)->toBe(ExamRevaluationService::STATUS_APPLIED);
});

it('rebuilds the result the corrected mark belongs to', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 72]);
    $this->patchJson(route('exam.revaluations.apply-change', $request->id));

    // 72 + 65 out of 200, and the grade with it.
    expect((float) $this->header->fresh()->overall_percentage_cache)->toBe(68.5);
});

it('does not knock a published result back to draft when a mark is corrected', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 72]);
    $this->patchJson(route('exam.revaluations.apply-change', $request->id));

    expect($this->header->fresh()->status)->toBe(ExamResultHeader::STATUS_PUBLISHED);
});

it('keeps what the mark was before', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 72]);
    $this->patchJson(route('exam.revaluations.apply-change', $request->id));

    $history = $this->getJson(route('exam.revaluations.history', $request->id))->json('data');

    // The first question a parent asks is what it was before.
    expect((float) $history[0]['old_marks'])->toBe(55.0)
        ->and((float) $history[0]['new_marks'])->toBe(72.0)
        ->and($history)->toHaveCount(2);
});

it('refuses to apply a recheck nobody approved', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.apply-change', $request->id))
        ->assertStatus(422);
});

it('refuses to approve a recheck that is already settled', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.reject', $request->id));

    $this->patchJson(route('exam.revaluations.approve', $request->id), ['new_marks' => 72])
        ->assertStatus(422);
});

it('lets a parent apply again after a rejection', function () {
    publishExam($this->world);
    applyForRecheck($this->maths->id);
    $request = ExamRevaluationRequest::firstOrFail();

    $this->patchJson(route('exam.revaluations.reject', $request->id));

    // One row per paper by design, so the same row reopens — and the history
    // of the first application is still there.
    applyForRecheck($this->maths->id, 'Applying again with the answer sheet attached.')
        ->assertCreated();

    expect(ExamRevaluationRequest::count())->toBe(1)
        ->and($request->fresh()->status)->toBe(ExamRevaluationService::STATUS_PENDING)
        ->and($this->getJson(route('exam.revaluations.history', $request->id))->json('data'))
        ->toHaveCount(1);
});
