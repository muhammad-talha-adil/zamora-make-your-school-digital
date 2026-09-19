<?php

/**
 * Case 08 — the Top 5 Toppers widget needs a scope to mean anything.
 *
 * Ranking every result on an exam together mixes children from different
 * classes and campuses, so "top 5" without a class selected compared apples
 * to oranges and showed a ranking nobody asked for. `results.index` now only
 * returns toppers once a class has been selected to scope the comparison.
 */

use App\Models\Exam\ExamResultHeader;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(3);
    $this->world->paper('Mathematics', total: 100, passing: 40);

    foreach ($this->students as $i => $student) {
        ExamResultHeader::create([
            'exam_id' => $this->world->exam->id,
            'student_id' => $student->id,
            'campus_id' => $this->world->school->campus->id,
            'class_id' => $this->world->school->class->id,
            'section_id' => $this->world->school->section->id,
            'status' => ExamResultHeader::STATUS_DRAFT,
            'total_obtained_cache' => 90 - ($i * 10),
            'overall_percentage_cache' => 90 - ($i * 10),
            'result_status' => ExamResultHeader::RESULT_PASS,
            'failed_subject_count' => 0,
        ]);
    }
});

it('shows no toppers when no class is selected', function () {
    $response = $this->getJson(route('exam.results.index', ['exam_id' => $this->world->exam->id]))
        ->assertSuccessful();

    expect($response->json('toppers'))->toBe([]);
});

it('shows toppers once a class is selected to scope the comparison', function () {
    $response = $this->getJson(route('exam.results.index', [
        'exam_id' => $this->world->exam->id,
        'class_id' => $this->world->school->class->id,
    ]))->assertSuccessful();

    expect($response->json('toppers'))->toHaveCount(3);
});
