<?php

/**
 * Case 02 — grace marks.
 *
 * Widely given here: a child failing one subject by two or three marks is
 * lifted to the pass mark rather than held back a year. The module had nowhere
 * to put it, so a school doing it at all was editing the obtained marks — and
 * then could not answer the parent standing at the counter with the answer
 * sheet, because the number on the card no longer matched the number on the
 * paper.
 *
 * So it is recorded **as grace**, in its own column, with a reason and the
 * person who gave it.
 */

use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actor = $this->world->school->actor;
    $this->actingAs($this->actor);
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics', total: 100, passing: 40);
    $this->world->paper('English', total: 100, passing: 40);

    // Two short in Maths, comfortable in English.
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0],
        ['Mathematics' => ['obtained' => 38], 'English' => ['obtained' => 70]]
    ))->assertSuccessful();

    $this->maths = ExamResultLine::where('exam_paper_id', $this->world->papers['Mathematics']->id)->firstOrFail();
});

/** Gives grace on the maths paper. */
function giveGrace(int $lineId, $marks, ?string $reason = 'Two short of the pass mark.')
{
    return test()->putJson(route('exam.marking.grace', $lineId), [
        'grace_marks' => $marks,
        'reason' => $reason,
    ]);
}

it('records grace separately from the obtained marks', function () {
    giveGrace($this->maths->id, 2)->assertSuccessful();

    $line = $this->maths->fresh();

    // What the child wrote is untouched. That is the whole point.
    expect((float) $line->obtained_marks)->toBe(38.0)
        ->and((float) $line->grace_marks)->toBe(2.0)
        ->and($line->effectiveMarks())->toBe(40.0);
});

it('records who gave it and why', function () {
    giveGrace($this->maths->id, 2, 'Two short of the pass mark.');

    $line = $this->maths->fresh();

    expect($line->grace_by)->toBe($this->actor->id)
        ->and($line->grace_reason)->toBe('Two short of the pass mark.')
        ->and($line->grace_at)->not->toBeNull();
});

it('turns the failed subject into a pass', function () {
    expect($this->maths->fresh()->is_pass)->toBeFalse();

    giveGrace($this->maths->id, 2);

    expect($this->maths->fresh()->is_pass)->toBeTrue();
});

it('turns the whole result from a fail into a pass', function () {
    $header = ExamResultHeader::firstOrFail();

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_FAIL);

    giveGrace($this->maths->id, 2);

    expect($header->fresh()->result_status)->toBe(ExamResultHeader::RESULT_PASS)
        ->and($header->fresh()->failed_subject_count)->toBe(0);
});

it('counts grace in the total and the percentage', function () {
    giveGrace($this->maths->id, 2);

    $header = ExamResultHeader::firstOrFail();

    // 40 + 70 out of 200.
    expect((float) $header->total_obtained_cache)->toBe(110.0)
        ->and((float) $header->overall_percentage_cache)->toBe(55.0);
});

it('refuses grace that would take the mark past the paper total', function () {
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['Mathematics' => ['obtained' => 99]]
    ));

    giveGrace($this->maths->id, 5)
        ->assertStatus(422)
        ->assertJsonValidationErrors('grace_marks');
});

it('holds to the limit the school set', function () {
    $this->world->exam->update(['grace_marks_limit' => 3]);

    giveGrace($this->maths->id, 5)
        ->assertStatus(422)
        ->assertJsonValidationErrors('grace_marks');

    giveGrace($this->maths->id, 3)->assertSuccessful();
});

it('refuses grace on a paper the child was absent for', function () {
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['Mathematics' => ['is_absent' => true]]
    ));

    // There is no mark to lift.
    giveGrace($this->maths->id, 2)->assertStatus(422);
});

it('refuses grace on a locked exam', function () {
    $this->world->exam->update(['is_locked' => true]);

    giveGrace($this->maths->id, 2)->assertStatus(422);
});

it('takes grace away again', function () {
    giveGrace($this->maths->id, 2);
    giveGrace($this->maths->id, null)->assertSuccessful();

    $line = $this->maths->fresh();

    expect($line->grace_marks)->toBeNull()
        ->and($line->grace_reason)->toBeNull()
        ->and($line->is_pass)->toBeFalse()
        ->and(ExamResultHeader::first()->result_status)->toBe(ExamResultHeader::RESULT_FAIL);
});

it('does not lose grace when a mark is corrected', function () {
    giveGrace($this->maths->id, 2);

    // The English mark is re-entered; nothing to do with Maths.
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['English' => ['obtained' => 75]]
    ))->assertSuccessful();

    expect((float) $this->maths->fresh()->grace_marks)->toBe(2.0);
});

it('clears grace when the child is marked absent after all', function () {
    giveGrace($this->maths->id, 2);

    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['Mathematics' => ['is_absent' => true]]
    ))->assertSuccessful();

    // An absence carries no grace with it.
    expect($this->maths->fresh()->grace_marks)->toBeNull();
});

it('lists the children who are within a few marks', function () {
    $rows = $this->getJson(route('exam.marking.grace-candidates', [
        'exam_id' => $this->world->exam->id,
        'within' => 3,
    ]))->assertSuccessful()->json('data');

    // The list a school works from: "this child is two short in Maths".
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['subject'])->toBe('Mathematics')
        ->and((float) $rows[0]['short_by'])->toBe(2.0);
});

it('leaves out children who are further away than that', function () {
    $rows = $this->getJson(route('exam.marking.grace-candidates', [
        'exam_id' => $this->world->exam->id,
        'within' => 1,
    ]))->assertSuccessful()->json('data');

    expect($rows)->toBeEmpty();
});

it('does not let the marking teacher give it to themselves', function () {
    // Marking a paper and deciding to lift it are two different jobs.
    $this->actingAs($this->world->teacher())
        ->putJson(route('exam.marking.grace', $this->maths->id), ['grace_marks' => 2])
        ->assertForbidden();

    expect($this->maths->fresh()->grace_marks)->toBeNull();
});
