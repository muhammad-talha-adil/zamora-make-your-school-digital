<?php

/**
 * Case 03 — optional and additional subjects.
 *
 * A child taking Computer instead of Biology, or an extra subject that does not
 * count towards the total, is ordinary from Class 9 onwards. `is_exempt` was the
 * nearest thing available and it says something else: exempt means the child was
 * not required to sit the paper, not that the paper does not count.
 *
 * Three roles now. **Core** everybody sits and it counts. **Elective** only the
 * children who chose it sit, and for them it counts. **Additional** is marked
 * and graded and deliberately left out of the total, the percentage and the
 * position.
 */

use App\Enums\Exam\SubjectRole;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics', total: 100, passing: 40);
});

/** Marks the child and returns their rebuilt result. */
function markAndRead(ExamWorld $world, array $marks): ExamResultHeader
{
    test()->postJson(route('exam.marking.save-row'), $world->marksPayload($world->students[0], $marks))
        ->assertSuccessful();

    return ExamResultHeader::where('exam_id', $world->exam->id)->firstOrFail();
}

/** One subject's line. */
function roleLine(ExamWorld $world, string $subject): ExamResultLine
{
    return ExamResultLine::where('exam_paper_id', $world->papers[$subject]->id)->firstOrFail();
}

it('takes the role from the paper', function () {
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Quran' => ['obtained' => 90],
    ]);

    expect(roleLine($this->world, 'Quran')->subject_role)->toBe(SubjectRole::Additional)
        ->and(roleLine($this->world, 'Mathematics')->subject_role)->toBe(SubjectRole::Core);
});

it('leaves an additional subject out of the total', function () {
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    $header = markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Quran' => ['obtained' => 90],
    ]);

    // 80 out of 100 — the extra subject changes neither side.
    expect((float) $header->total_obtained_cache)->toBe(80.0)
        ->and((float) $header->overall_percentage_cache)->toBe(80.0);
});

it('still grades an additional subject', function () {
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Quran' => ['obtained' => 90],
    ]);

    $line = roleLine($this->world, 'Quran');

    // It goes on the card with its own grade; it just does not count.
    expect((float) $line->percentage_cache)->toBe(90.0)
        ->and($line->grade_item_id_cache)->not->toBeNull();
});

it('does not judge an additional subject pass or fail', function () {
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Quran' => ['obtained' => 20],
    ]);

    expect(roleLine($this->world, 'Quran')->is_pass)->toBeNull();
});

it('does not fail a child for an additional subject they did badly in', function () {
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    $header = markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Quran' => ['obtained' => 20],
    ]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_PASS)
        ->and($header->failed_subject_count)->toBe(0);
});

it('counts an elective like any other subject', function () {
    // Computer instead of Biology: chosen, but it counts.
    $this->world->paper('Computer', total: 100, passing: 40, role: SubjectRole::Elective);

    $header = markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Computer' => ['obtained' => 60],
    ]);

    expect((float) $header->total_obtained_cache)->toBe(140.0)
        ->and((float) $header->overall_percentage_cache)->toBe(70.0)
        ->and(roleLine($this->world, 'Computer')->is_pass)->toBeTrue();
});

it('fails a child who failed their elective', function () {
    $this->world->paper('Computer', total: 100, passing: 40, role: SubjectRole::Elective);

    $header = markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Computer' => ['obtained' => 30],
    ]);

    expect($header->result_status)->toBe(ExamResultHeader::RESULT_FAIL)
        ->and($header->failed_subject_count)->toBe(1);
});

it('lets the marking screen say otherwise for one child', function () {
    // The same subject is core for the class and an extra for this child.
    $this->world->paper('Computer', total: 100, passing: 40);

    $header = markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Computer' => ['obtained' => 60, 'subject_role' => 'additional'],
    ]);

    expect(roleLine($this->world, 'Computer')->subject_role)->toBe(SubjectRole::Additional)
        ->and((float) $header->overall_percentage_cache)->toBe(80.0);
});

it('keeps the role when the mark is corrected', function () {
    $this->world->paper('Computer', total: 100, passing: 40);

    markAndRead($this->world, ['Computer' => ['obtained' => 60, 'subject_role' => 'additional']]);
    markAndRead($this->world, ['Computer' => ['obtained' => 75]]);

    expect(roleLine($this->world, 'Computer')->subject_role)->toBe(SubjectRole::Additional);
});

it('treats exempt and additional as two different statements', function () {
    $this->world->paper('Biology', total: 100, passing: 40);
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    markAndRead($this->world, [
        'Mathematics' => ['obtained' => 80],
        'Biology' => ['is_exempt' => true],
        'Quran' => ['obtained' => 90],
    ]);

    $biology = roleLine($this->world, 'Biology');
    $quran = roleLine($this->world, 'Quran');

    // Exempt: not required to sit it, and no mark. Additional: sat it, marked,
    // graded, and not counted.
    expect($biology->is_exempt)->toBeTrue()
        ->and($biology->subject_role)->toBe(SubjectRole::Core)
        ->and($quran->is_exempt)->toBeFalse()
        ->and((float) $quran->obtained_marks)->toBe(90.0);
});
