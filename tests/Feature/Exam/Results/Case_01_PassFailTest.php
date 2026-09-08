<?php

/**
 * Case 01 — did the child pass.
 *
 * `passing_marks_snapshot` was stored on every result line from the beginning
 * and compared to nothing, so the one question the whole exam exists to answer
 * had no answer anywhere in the database.
 *
 * The rule has two halves: every counted subject on its own passing marks, and
 * the school's aggregate where it has set one.
 */

use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics', total: 100, passing: 40);
    $this->world->paper('English', total: 100, passing: 40);
});

/** Marks the child and returns their rebuilt result. */
function resultAfter(ExamWorld $world, array $marks): ExamResultHeader
{
    test()->postJson(route('exam.marking.save-row'), $world->marksPayload($world->students[0], $marks))
        ->assertSuccessful();

    return ExamResultHeader::where('exam_id', $world->exam->id)->firstOrFail();
}

/** One subject's line. */
function line(ExamWorld $world, string $subject): ExamResultLine
{
    return ExamResultLine::where('exam_paper_id', $world->papers[$subject]->id)->firstOrFail();
}

it('says the child passed', function () {
    $header = resultAfter($this->world, [
        'Mathematics' => ['obtained' => 70],
        'English' => ['obtained' => 65],
    ]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_PASS)
        ->and($header->failed_subject_count)->toBe(0);
});

it('marks each subject pass or fail on its own passing marks', function () {
    resultAfter($this->world, [
        'Mathematics' => ['obtained' => 70],
        'English' => ['obtained' => 35],
    ]);

    expect(line($this->world, 'Mathematics')->is_pass)->toBeTrue()
        ->and(line($this->world, 'English')->is_pass)->toBeFalse();
});

it('fails a child who failed one subject', function () {
    // 105 of 200 is 52.5% overall, and still a fail: one subject is below 40.
    $header = resultAfter($this->world, [
        'Mathematics' => ['obtained' => 70],
        'English' => ['obtained' => 35],
    ]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_FAIL)
        ->and($header->failed_subject_count)->toBe(1);
});

it('counts a paper the child was absent for as a failed subject', function () {
    $header = resultAfter($this->world, [
        'Mathematics' => ['obtained' => 90],
        'English' => ['is_absent' => true],
    ]);

    expect(line($this->world, 'English')->is_pass)->toBeFalse()
        ->and($header->result_status)->toBe(ExamResultHeader::RESULT_FAIL);
});

it('does not call an unfinished result a failure', function () {
    // Maths marked, English started and left blank.
    resultAfter($this->world, ['Mathematics' => ['obtained' => 70]]);
    $header = resultAfter($this->world, ['English' => ['obtained' => null]]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_PENDING);
});

it('holds a child to the school s aggregate as well', function () {
    $this->world->exam->update(['aggregate_pass_percentage' => 60]);

    // Both subjects passed, 55% overall.
    $header = resultAfter($this->world, [
        'Mathematics' => ['obtained' => 60],
        'English' => ['obtained' => 50],
    ]);

    expect($header->overall_percentage_cache)->toEqual(55.0)
        ->and($header->result_status)->toBe(ExamResultHeader::RESULT_FAIL)
        // Both subjects passed; it was the aggregate that failed them.
        ->and($header->failed_subject_count)->toBe(0);
});

it('does not invent an aggregate for a school that set none', function () {
    $header = resultAfter($this->world, [
        'Mathematics' => ['obtained' => 41],
        'English' => ['obtained' => 41],
    ]);

    // 41% would fail a 33% rule nowhere and a 50% rule everywhere. The school
    // said nothing, so the subjects decide.
    expect($header->result_status)->toBe(ExamResultHeader::RESULT_PASS);
});

it('does not judge an exempt paper at all', function () {
    resultAfter($this->world, [
        'Mathematics' => ['obtained' => 70],
        'English' => ['is_exempt' => true],
    ]);

    // Not "did not pass" — the paper was never being counted.
    expect(line($this->world, 'English')->is_pass)->toBeNull();
});

it('passes a child whose only failure was an exempt paper', function () {
    $header = resultAfter($this->world, [
        'Mathematics' => ['obtained' => 70],
        'English' => ['is_exempt' => true],
    ]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_PASS)
        ->and($header->failed_subject_count)->toBe(0);
});

it('changes its mind when a mark is corrected', function () {
    resultAfter($this->world, [
        'Mathematics' => ['obtained' => 70],
        'English' => ['obtained' => 35],
    ]);

    // The re-mark takes them over.
    $header = resultAfter($this->world, ['English' => ['obtained' => 45]]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_PASS)
        ->and($header->failed_subject_count)->toBe(0);
});
