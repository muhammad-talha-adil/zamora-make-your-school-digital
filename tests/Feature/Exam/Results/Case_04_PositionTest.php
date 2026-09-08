<?php

/**
 * Case 04 — "Position: 3rd of 42".
 *
 * It is on every result card printed in this country and nothing computed it.
 *
 * The sort was the easy half. These tests are the other half — the rules a
 * school will be asked about by a parent: who is ranked, who is not, and what
 * happens when two children score the same.
 */

use App\Models\Exam\ExamResultHeader;
use App\Services\Exam\ExamLifecycleService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->paper('Mathematics', total: 100, passing: 40);
});

/** Marks a child and returns their header. */
function scoreOf(ExamWorld $world, $student, array $mark): ExamResultHeader
{
    test()->postJson(route('exam.marking.save-row'), $world->marksPayload($student, ['Mathematics' => $mark]))
        ->assertSuccessful();

    return ExamResultHeader::where('student_id', $student->id)->firstOrFail();
}

/** Publishes, which is when positions are worked out. */
function publishAndRank(ExamWorld $world): void
{
    app(ExamLifecycleService::class)->publish($world->exam, null, force: true);
}

it('ranks a section by total marks', function () {
    $students = $this->world->enrol(3);

    scoreOf($this->world, $students[0], ['obtained' => 60]);
    scoreOf($this->world, $students[1], ['obtained' => 90]);
    scoreOf($this->world, $students[2], ['obtained' => 75]);

    publishAndRank($this->world);

    expect(ExamResultHeader::where('student_id', $students[1]->id)->first()->position_in_section)->toBe(1)
        ->and(ExamResultHeader::where('student_id', $students[2]->id)->first()->position_in_section)->toBe(2)
        ->and(ExamResultHeader::where('student_id', $students[0]->id)->first()->position_in_section)->toBe(3);
});

it('says what the position is out of', function () {
    $students = $this->world->enrol(3);

    foreach ($students as $index => $student) {
        scoreOf($this->world, $student, ['obtained' => 50 + $index]);
    }

    publishAndRank($this->world);

    expect(ExamResultHeader::first()->ranked_out_of)->toBe(3);
});

it('gives two children on the same total the same position', function () {
    $students = $this->world->enrol(3);

    scoreOf($this->world, $students[0], ['obtained' => 90]);
    scoreOf($this->world, $students[1], ['obtained' => 80]);
    scoreOf($this->world, $students[2], ['obtained' => 80]);

    publishAndRank($this->world);

    expect(ExamResultHeader::where('student_id', $students[1]->id)->first()->position_in_section)->toBe(2)
        ->and(ExamResultHeader::where('student_id', $students[2]->id)->first()->position_in_section)->toBe(2);
});

it('skips the position a tie used up', function () {
    $students = $this->world->enrol(4);

    scoreOf($this->world, $students[0], ['obtained' => 90]);
    scoreOf($this->world, $students[1], ['obtained' => 80]);
    scoreOf($this->world, $students[2], ['obtained' => 80]);
    scoreOf($this->world, $students[3], ['obtained' => 70]);

    publishAndRank($this->world);

    // 1, 2, 2, 4 — what a school here prints, and the only tie rule that does
    // not need somebody to break the tie by hand.
    expect(ExamResultHeader::where('student_id', $students[3]->id)->first()->position_in_section)->toBe(4);
});

it('still ranks a child who failed', function () {
    $students = $this->world->enrol(2);

    scoreOf($this->world, $students[0], ['obtained' => 90]);
    scoreOf($this->world, $students[1], ['obtained' => 20]);

    publishAndRank($this->world);

    $failed = ExamResultHeader::where('student_id', $students[1]->id)->first();

    // They sat the same paper. Leaving them out silently improves everybody
    // below them.
    expect($failed->result_status)->toBe(ExamResultHeader::RESULT_FAIL)
        ->and($failed->position_in_section)->toBe(2);
});

it('does not rank a child whose result is not finished', function () {
    $students = $this->world->enrol(2);
    $this->world->paper('English', total: 100, passing: 40);

    scoreOf($this->world, $students[0], ['obtained' => 90]);

    // The second child has Maths marked and English left blank.
    scoreOf($this->world, $students[1], ['obtained' => 80]);
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $students[1], ['English' => ['obtained' => null]]
    ));

    publishAndRank($this->world);

    $unfinished = ExamResultHeader::where('student_id', $students[1]->id)->first();

    expect($unfinished->result_status)->toBe(ExamResultHeader::RESULT_PENDING)
        ->and($unfinished->position_in_section)->toBeNull()
        // And the finished one is first of one, not first of two.
        ->and(ExamResultHeader::where('student_id', $students[0]->id)->first()->ranked_out_of)->toBe(1);
});

it('ranks a child who missed a paper below one who sat it badly', function () {
    $students = $this->world->enrol(2);

    scoreOf($this->world, $students[0], ['is_absent' => true]);
    scoreOf($this->world, $students[1], ['obtained' => 10]);

    publishAndRank($this->world);

    expect(ExamResultHeader::where('student_id', $students[1]->id)->first()->position_in_section)->toBe(1)
        ->and(ExamResultHeader::where('student_id', $students[0]->id)->first()->position_in_section)->toBe(2);
});

it('ranks within the section and within the class', function () {
    $students = $this->world->enrol(3);

    scoreOf($this->world, $students[0], ['obtained' => 90]);
    scoreOf($this->world, $students[1], ['obtained' => 80]);
    scoreOf($this->world, $students[2], ['obtained' => 95]);

    // The top scorer sat in the other section of the same class.
    ExamResultHeader::where('student_id', $students[2]->id)
        ->update(['section_id' => $this->world->school->otherSection->id]);

    publishAndRank($this->world);

    $best = ExamResultHeader::where('student_id', $students[0]->id)->first();

    // First in their own section, second in the class.
    expect($best->position_in_section)->toBe(1)
        ->and($best->position_in_class)->toBe(2);
});

it('works the positions out again when a mark is corrected afterwards', function () {
    $students = $this->world->enrol(2);

    scoreOf($this->world, $students[0], ['obtained' => 90]);
    scoreOf($this->world, $students[1], ['obtained' => 60]);

    publishAndRank($this->world);

    // A revaluation lifts the second child above the first.
    ExamResultHeader::where('student_id', $students[1]->id)
        ->update(['total_obtained_cache' => 95]);

    $this->postJson(route('exam.positions.recompute', $this->world->exam->id))
        ->assertSuccessful();

    expect(ExamResultHeader::where('student_id', $students[1]->id)->first()->position_in_section)->toBe(1);
});

it('takes a stale position away rather than leaving it on the card', function () {
    $students = $this->world->enrol(1);
    $this->world->paper('English', total: 100, passing: 40);

    scoreOf($this->world, $students[0], ['obtained' => 90]);
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $students[0], ['English' => ['obtained' => 80]]
    ));

    publishAndRank($this->world);

    expect(ExamResultHeader::first()->position_in_section)->toBe(1);

    // English is reopened and blanked; the result is unfinished again.
    ExamResultHeader::query()->update(['status' => ExamResultHeader::STATUS_DRAFT]);
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $students[0], ['English' => ['obtained' => null]]
    ));

    $this->postJson(route('exam.positions.recompute', $this->world->exam->id));

    expect(ExamResultHeader::first()->position_in_section)->toBeNull();
});

it('does not let a teacher work the positions out', function () {
    $this->world->enrol(1);

    // Same decision as publishing: the ranking is what goes on the cards.
    $this->actingAs($this->world->teacher())
        ->postJson(route('exam.positions.recompute', $this->world->exam->id))
        ->assertForbidden();
});
