<?php

/**
 * Case 06 — the year, assembled from the terms.
 *
 * "First term 25%, mid 25%, annual 50%" is the normal arrangement here and the
 * module could not do it. `exam_papers.paper_weight` weighted one paper against
 * another inside a single exam; nothing weighted one **exam** against another,
 * so schools were doing the year in a register.
 */

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Services\Exam\AnnualResultService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);

    // The first term is worth a quarter of the year.
    $this->world->exam->update(['result_weight' => 25]);
    $this->world->paper('Mathematics', total: 100, passing: 40);

    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['Mathematics' => ['obtained' => 60]]
    ))->assertSuccessful();
});

/** A second exam in the same session, weighted, with one paper marked. */
function anotherTerm(ExamWorld $world, string $name, float $weight, float $marks): Exam
{
    $exam = Exam::create([
        'session_id' => $world->school->session->id,
        'exam_type_id' => $world->examType->id,
        'name' => $name,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-10',
        'status' => 'scheduled',
        'result_weight' => $weight,
    ]);

    $paper = $world->paper('Mathematics', total: 100, passing: 40, exam: $exam);

    test()->postJson(route('exam.marking.save-row'), [
        'exam_id' => $exam->id,
        'student_id' => $world->students[0]->id,
        'marks' => [$paper->id => ['obtained' => $marks]],
    ])->assertSuccessful();

    return $exam;
}

/** The child's year. */
function annualFor(ExamWorld $world): array
{
    return app(AnnualResultService::class)->forStudent(
        $world->students[0]->id,
        $world->school->session->id,
        $world->school->campus->id
    );
}

it('weights the terms against each other', function () {
    // 60 at a quarter, 80 at three quarters: 75%.
    anotherTerm($this->world, 'Annual 2026', 75, 80);

    expect(annualFor($this->world)['percentage'])->toBe(75.0);
});

it('says which terms went into it', function () {
    anotherTerm($this->world, 'Annual 2026', 75, 80);

    $terms = annualFor($this->world)['terms'];

    expect($terms)->toHaveCount(2)
        ->and($terms[0]['weight'])->toBe(25.0)
        ->and($terms[0]['percentage'])->toBe(60.0);
});

it('leaves out an exam the school did not weight', function () {
    // A class test does not belong in the year's result.
    $test = Exam::create([
        'session_id' => $this->world->school->session->id,
        'exam_type_id' => $this->world->examType->id,
        'name' => 'Class Test',
        'start_date' => '2026-11-01',
        'status' => 'scheduled',
    ]);

    expect(app(AnnualResultService::class)->examsIn($this->world->school->session->id)->pluck('id'))
        ->not->toContain($test->id);
});

it('reports on the terms the child has actually sat', function () {
    anotherTerm($this->world, 'Annual 2026', 75, 80);

    // Their mid-term is still to come, so the weight in use is 100 of 100.
    $annual = annualFor($this->world);

    expect($annual['weight_used'])->toBe(100.0)
        ->and($annual['is_complete'])->toBeTrue();
});

it('says so when the year is not finished', function () {
    // The annual paper is weighted but nobody has marked it.
    Exam::create([
        'session_id' => $this->world->school->session->id,
        'exam_type_id' => $this->world->examType->id,
        'name' => 'Annual 2026',
        'start_date' => '2026-12-01',
        'status' => 'scheduled',
        'result_weight' => 75,
    ]);

    $annual = annualFor($this->world);

    expect($annual['is_complete'])->toBeFalse()
        ->and($annual['result_status'])->toBe(ExamResultHeader::RESULT_PENDING)
        // Still shown against what they have sat, rather than halved and
        // called an annual result.
        ->and($annual['percentage'])->toBe(60.0)
        ->and($annual['weight_used'])->toBe(25.0);
});

it('gives the year a grade', function () {
    anotherTerm($this->world, 'Annual 2026', 75, 80);

    expect(annualFor($this->world)['grade'])->toBe('A');
});

it('breaks the year down subject by subject', function () {
    anotherTerm($this->world, 'Annual 2026', 75, 80);

    $subjects = annualFor($this->world)['subjects'];

    // The figure a parent looks for first: "how has he done in Maths all year".
    expect($subjects)->toHaveCount(1)
        ->and($subjects[0]['subject'])->toBe('Mathematics')
        ->and($subjects[0]['percentage'])->toBe(75.0)
        ->and($subjects[0]['terms'])->toHaveCount(2);
});

it('does not fail the year for one bad term', function () {
    // 20 in the first term, 80 in the annual: 65% for the year.
    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['Mathematics' => ['obtained' => 20]]
    ));

    anotherTerm($this->world, 'Annual 2026', 75, 80);

    // The whole point of weighting is that a bad first term can be recovered.
    expect(annualFor($this->world)['percentage'])->toBe(65.0);
});

it('holds the year to the aggregate the school set on the annual paper', function () {
    $annualExam = anotherTerm($this->world, 'Annual 2026', 75, 45);
    $annualExam->update(['aggregate_pass_percentage' => 60]);

    // 60 at a quarter, 45 at three quarters: 48.75%.
    $annual = annualFor($this->world);

    expect($annual['percentage'])->toBe(48.75)
        ->and($annual['result_status'])->toBe(ExamResultHeader::RESULT_FAIL);
});

it('answers over HTTP', function () {
    anotherTerm($this->world, 'Annual 2026', 75, 80);

    $this->getJson(route('exam.results.annual', [
        $this->students[0]->id,
        'session_id' => $this->world->school->session->id,
    ]))
        ->assertSuccessful()
        // JSON writes a whole float as 75, not 75.0.
        ->assertJsonPath('data.percentage', 75);
});

it('does not let a driver read a child s year', function () {
    $this->actingAs($this->world->staff('driver'))
        ->getJson(route('exam.results.annual', [
            $this->students[0]->id,
            'session_id' => $this->world->school->session->id,
        ]))
        ->assertForbidden();
});
