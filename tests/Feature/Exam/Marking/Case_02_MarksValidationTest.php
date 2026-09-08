<?php

/**
 * Case 02 — what the marking screen accepts, and what it turns away.
 *
 * The rule used to be `'marks' => 'required|array'` and nothing else. What was
 * inside was never looked at: text reached a decimal column, negatives were
 * accepted, and **150 out of 100** went in and came back out as a percentage of
 * 150 on a result card.
 *
 * And the paper id was an array key from the request that nobody checked, so
 * another exam's paper could be posted into this one's result — the third time
 * that fault has appeared in this codebase.
 */

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->maths = $this->world->paper('Mathematics', total: 100, passing: 40);
});

/** Posts a mark straight against a paper id. */
function postMark(ExamWorld $world, int $paperId, array $mark)
{
    return test()->postJson(route('exam.marking.save-row'), [
        'exam_id' => $world->exam->id,
        'student_id' => $world->students[0]->id,
        'marks' => [$paperId => $mark],
    ]);
}

it('accepts a mark within the paper total', function () {
    postMark($this->world, $this->maths->id, ['obtained' => 85])->assertSuccessful();
});

it('refuses more marks than the paper carries', function () {
    // 150 out of 100 used to be accepted, and read as 150% on the result card.
    postMark($this->world, $this->maths->id, ['obtained' => 150])
        ->assertStatus(422)
        ->assertJsonValidationErrors("marks.{$this->maths->id}");
});

it('accepts full marks', function () {
    postMark($this->world, $this->maths->id, ['obtained' => 100])->assertSuccessful();
});

it('refuses negative marks', function () {
    postMark($this->world, $this->maths->id, ['obtained' => -5])->assertStatus(422);
});

it('refuses marks that are not a number', function () {
    postMark($this->world, $this->maths->id, ['obtained' => 'absent'])->assertStatus(422);
});

it('accepts an empty mark for a paper not yet marked', function () {
    postMark($this->world, $this->maths->id, ['obtained' => null])->assertSuccessful();
});

it('refuses marks against another exam s paper', function () {
    $otherExam = Exam::create([
        'session_id' => $this->world->school->session->id,
        'exam_type_id' => $this->world->examType->id,
        'name' => 'Mid Term 2026',
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-10',
        'status' => 'scheduled',
    ]);

    $otherPaper = $this->world->paper('Physics', exam: $otherExam);

    // Exam in the URL, another exam's paper in the body.
    postMark($this->world, $otherPaper->id, ['obtained' => 90])
        ->assertStatus(422)
        ->assertJsonValidationErrors("marks.{$otherPaper->id}");
});

it('writes nothing when another exam s paper is posted', function () {
    $otherExam = Exam::create([
        'session_id' => $this->world->school->session->id,
        'exam_type_id' => $this->world->examType->id,
        'name' => 'Mid Term 2026',
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-10',
        'status' => 'scheduled',
    ]);

    postMark($this->world, $this->world->paper('Physics', exam: $otherExam)->id, ['obtained' => 90]);

    expect(ExamResultLine::count())->toBe(0)
        ->and(ExamResultHeader::count())->toBe(0);
});

it('refuses a paper that does not exist', function () {
    postMark($this->world, 999999, ['obtained' => 50])->assertStatus(422);
});

it('refuses marks alongside an absence', function () {
    postMark($this->world, $this->maths->id, ['obtained' => 60, 'is_absent' => true])
        ->assertStatus(422);
});

it('refuses an empty marks list', function () {
    $this->postJson(route('exam.marking.save-row'), [
        'exam_id' => $this->world->exam->id,
        'student_id' => $this->students[0]->id,
        'marks' => [],
    ])->assertStatus(422);
});

it('refuses a student who does not exist', function () {
    $this->postJson(route('exam.marking.save-row'), [
        'exam_id' => $this->world->exam->id,
        'student_id' => 999999,
        'marks' => [$this->maths->id => ['obtained' => 50]],
    ])->assertStatus(422);
});

it('refuses the whole batch when one row is out of bounds', function () {
    $this->world->enrol(1);

    $this->postJson(route('exam.marking.save-bulk'), [
        'exam_id' => $this->world->exam->id,
        'students' => [
            [
                'student_id' => $this->students[0]->id,
                'marks' => [$this->maths->id => ['obtained' => 80]],
            ],
            [
                'student_id' => $this->world->students[1]->id,
                'marks' => [$this->maths->id => ['obtained' => 500]],
            ],
        ],
    ])->assertStatus(422);

    // Checked for the whole batch before any of it is written, so one bad row
    // does not leave the class half marked.
    expect(ExamResultLine::count())->toBe(0);
});
