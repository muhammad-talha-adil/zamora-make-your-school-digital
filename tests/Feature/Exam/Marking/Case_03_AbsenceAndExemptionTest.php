<?php

/**
 * Case 03 — what an absence costs, and what an exemption does not.
 *
 * The old arithmetic took a missed paper out of **both** sides of the sum:
 *
 * ```php
 * $counted = $lines->where('is_absent', false)->where('is_exempt', false);
 * ```
 *
 * So a child who sat one paper out of eight and scored 45 of 50 was reported at
 * **90%** and graded A. That is not how it works here. Missing a paper costs you
 * its marks — which is the whole reason a child drags themselves in with a
 * fever.
 *
 * *Exempt* is the statement that was being confused with absence: a paper the
 * child was never required to sit cannot count against them, and it stays out
 * of both sides.
 */

use App\Models\Exam\ExamResultHeader;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics');
    $this->world->paper('English');
});

/** Posts one child's marks and returns their rebuilt header. */
function saveAndRead(ExamWorld $world, array $marks)
{
    test()->postJson(route('exam.marking.save-row'), $world->marksPayload($world->students[0], $marks))
        ->assertSuccessful();

    return ExamResultHeader::where('exam_id', $world->exam->id)
        ->where('student_id', $world->students[0]->id)
        ->firstOrFail();
}

it('counts a missed paper as zero, not as though it never existed', function () {
    $header = saveAndRead($this->world, [
        'Mathematics' => ['obtained' => 90],
        'English' => ['is_absent' => true],
    ]);

    // 90 out of 200, not 90 out of 100.
    expect((float) $header->total_obtained_cache)->toBe(90.0)
        ->and((float) $header->overall_percentage_cache)->toBe(45.0);
});

it('grades the child on what the absence left them with', function () {
    $header = saveAndRead($this->world, [
        'Mathematics' => ['obtained' => 90],
        'English' => ['is_absent' => true],
    ]);

    $letter = $this->world->gradeSystem->gradeSystemItems()
        ->find($header->overall_grade_item_id_cache)?->grade_letter;

    // 45% is a D here. It used to read 90% and print A+.
    expect($letter)->toBe('D');
});

it('leaves an exempt paper out of both sides', function () {
    $header = saveAndRead($this->world, [
        'Mathematics' => ['obtained' => 90],
        'English' => ['is_exempt' => true],
    ]);

    // 90 out of 100 — the paper they were never required to sit is not counted
    // against them.
    expect((float) $header->overall_percentage_cache)->toBe(90.0);
});

it('reports nothing when every paper was exempt', function () {
    $header = saveAndRead($this->world, [
        'Mathematics' => ['is_exempt' => true],
        'English' => ['is_exempt' => true],
    ]);

    expect($header->overall_percentage_cache)->toBeNull();
});

it('reports zero when the child sat nothing', function () {
    $header = saveAndRead($this->world, [
        'Mathematics' => ['is_absent' => true],
        'English' => ['is_absent' => true],
    ]);

    expect((float) $header->overall_percentage_cache)->toBe(0.0);
});

it('recovers the percentage when the absence is corrected', function () {
    saveAndRead($this->world, [
        'Mathematics' => ['obtained' => 90],
        'English' => ['is_absent' => true],
    ]);

    // The child had a medical note; English is re-marked.
    $header = saveAndRead($this->world, ['English' => ['obtained' => 70]]);

    expect((float) $header->overall_percentage_cache)->toBe(80.0);
});

it('still totals the marks actually obtained', function () {
    $header = saveAndRead($this->world, [
        'Mathematics' => ['obtained' => 55],
        'English' => ['is_absent' => true],
    ]);

    // The total is what they scored; the percentage is what it is out of.
    expect((float) $header->total_obtained_cache)->toBe(55.0);
});
