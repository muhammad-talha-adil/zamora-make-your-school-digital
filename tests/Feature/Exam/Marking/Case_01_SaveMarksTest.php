<?php

/**
 * Case 01 — writing marks.
 *
 * There were three routines doing this and they disagreed. The service — which
 * was injected into the controller and never once called — wrote the per-line
 * percentage and grade; the two inline copies in the controller did not, and
 * added the header up by two different pieces of arithmetic. Which screen the
 * teacher had used decided what was in the row, so a result card showed a
 * figure for one class and a blank for the next.
 *
 * Everything comes through the service now.
 */

use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->paper('Mathematics');
    $this->world->paper('English');
});

/** Posts one child's marks through the grid. */
function saveRow(ExamWorld $world, $student, array $marks)
{
    return test()->postJson(
        route('exam.marking.save-row'),
        $world->marksPayload($student, $marks)
    );
}

/** That child's result line for a subject. */
function lineFor(ExamWorld $world, $student, string $subject): ?ExamResultLine
{
    $header = ExamResultHeader::where('exam_id', $world->exam->id)
        ->where('student_id', $student->id)
        ->first();

    return $header
        ? ExamResultLine::where('result_header_id', $header->id)
            ->where('exam_paper_id', $world->papers[$subject]->id)
            ->first()
        : null;
}

/** That child's header. */
function headerFor(ExamWorld $world, $student): ?ExamResultHeader
{
    return ExamResultHeader::where('exam_id', $world->exam->id)
        ->where('student_id', $student->id)
        ->first();
}

it('saves a mark', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 85],
    ])->assertSuccessful();

    expect((float) lineFor($this->world, $this->students[0], 'Mathematics')->obtained_marks)
        ->toBe(85.0);
});

it('writes the per-line percentage the grid used to leave empty', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 85],
    ]);

    expect((float) lineFor($this->world, $this->students[0], 'Mathematics')->percentage_cache)
        ->toBe(85.0);
});

it('writes the per-line grade the grid used to leave empty', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 85],
    ]);

    $line = lineFor($this->world, $this->students[0], 'Mathematics');

    expect($line->grade_item_id_cache)->not->toBeNull()
        ->and($line->gradeItem?->grade_letter ?? $this->world->gradeSystem
            ->gradeSystemItems()->find($line->grade_item_id_cache)->grade_letter)
        ->toBe('A+');
});

it('snapshots the paper totals onto the line', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 85],
    ]);

    $line = lineFor($this->world, $this->students[0], 'Mathematics');

    // A paper edited later must not restate a result already given out.
    expect((float) $line->total_marks_snapshot)->toBe(100.0)
        ->and((float) $line->passing_marks_snapshot)->toBe(40.0);
});

it('adds the header up across papers', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 80],
        'English' => ['obtained' => 60],
    ]);

    $header = headerFor($this->world, $this->students[0]);

    expect((float) $header->total_obtained_cache)->toBe(140.0)
        ->and((float) $header->overall_percentage_cache)->toBe(70.0);
});

it('recomputes rather than adds when a mark is corrected', function () {
    saveRow($this->world, $this->students[0], ['Mathematics' => ['obtained' => 80]]);
    saveRow($this->world, $this->students[0], ['Mathematics' => ['obtained' => 50]]);

    $header = headerFor($this->world, $this->students[0]);

    expect((float) $header->total_obtained_cache)->toBe(50.0)
        ->and(ExamResultLine::where('result_header_id', $header->id)->count())->toBe(1);
});

it('leaves the mark empty for a child marked absent', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 85, 'is_absent' => true],
    ])->assertStatus(422);

    // Marks and an absence together are a contradiction, refused on the way in.
    expect(lineFor($this->world, $this->students[0], 'Mathematics'))->toBeNull();
});

it('records an absence with no marks', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['is_absent' => true],
    ])->assertSuccessful();

    $line = lineFor($this->world, $this->students[0], 'Mathematics');

    expect($line->is_absent)->toBeTrue()
        ->and($line->obtained_marks)->toBeNull();
});

it('holds the header at draft while a paper is unmarked', function () {
    saveRow($this->world, $this->students[0], ['Mathematics' => ['obtained' => 80]]);

    // English has a line only once it is marked, so nothing is outstanding yet
    // on the lines that exist.
    saveRow($this->world, $this->students[0], ['English' => ['obtained' => null]]);

    expect(headerFor($this->world, $this->students[0])->status)
        ->toBe(ExamResultHeader::STATUS_DRAFT);
});

it('moves the header to submitted once every line is marked', function () {
    saveRow($this->world, $this->students[0], [
        'Mathematics' => ['obtained' => 80],
        'English' => ['obtained' => 60],
    ]);

    expect(headerFor($this->world, $this->students[0])->status)
        ->toBe(ExamResultHeader::STATUS_SUBMITTED);
});

it('keeps each child to their own header', function () {
    saveRow($this->world, $this->students[0], ['Mathematics' => ['obtained' => 80]]);
    saveRow($this->world, $this->students[1], ['Mathematics' => ['obtained' => 40]]);

    expect(ExamResultHeader::where('exam_id', $this->world->exam->id)->count())->toBe(2)
        ->and((float) headerFor($this->world, $this->students[1])->total_obtained_cache)->toBe(40.0);
});

it('refuses to write to a locked exam', function () {
    $this->world->exam->update(['is_locked' => true]);

    saveRow($this->world, $this->students[0], ['Mathematics' => ['obtained' => 80]])
        ->assertStatus(403);

    expect(ExamResultHeader::count())->toBe(0);
});

it('saves a whole class in one request', function () {
    $this->postJson(route('exam.marking.save-bulk'), [
        'exam_id' => $this->world->exam->id,
        'students' => [
            [
                'student_id' => $this->students[0]->id,
                'marks' => [$this->world->papers['Mathematics']->id => ['obtained' => 90]],
            ],
            [
                'student_id' => $this->students[1]->id,
                'marks' => [$this->world->papers['Mathematics']->id => ['obtained' => 30]],
            ],
        ],
    ])->assertSuccessful();

    expect((float) headerFor($this->world, $this->students[0])->overall_percentage_cache)->toBe(90.0)
        ->and((float) headerFor($this->world, $this->students[1])->overall_percentage_cache)->toBe(30.0);
});

it('gives the bulk path the same figures as the single one', function () {
    saveRow($this->world, $this->students[0], ['Mathematics' => ['obtained' => 75]]);

    $this->postJson(route('exam.marking.save-bulk'), [
        'exam_id' => $this->world->exam->id,
        'students' => [[
            'student_id' => $this->students[1]->id,
            'marks' => [$this->world->papers['Mathematics']->id => ['obtained' => 75]],
        ]],
    ])->assertSuccessful();

    $one = lineFor($this->world, $this->students[0], 'Mathematics');
    $other = lineFor($this->world, $this->students[1], 'Mathematics');

    // The whole point of there being one routine.
    expect((float) $one->percentage_cache)->toBe((float) $other->percentage_cache)
        ->and($one->grade_item_id_cache)->toBe($other->grade_item_id_cache);
});
