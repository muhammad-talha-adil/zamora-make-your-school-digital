<?php

/**
 * Case 04 — what saving a whole class costs.
 *
 * The bulk screen called the single-child routine once per child, so a class of
 * forty opened forty transactions and rebuilt forty headers — each one going
 * back to the database for its own lines, and, before the grade bands were held
 * in memory, once more for every mark on every paper.
 *
 * The lines are read once for the batch now. These tests hold that in place:
 * they are about the number of queries, so they will fail loudly if somebody
 * puts the per-child loop back.
 */

use App\Models\Exam\ExamResultHeader;
use Illuminate\Support\Facades\DB;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->enrol(8);
    $this->world->paper('Mathematics');
    $this->world->paper('English');
});

/** The queries a bulk save of the whole class runs. */
function queriesForBulkSave(ExamWorld $world): array
{
    $rows = [];

    foreach ($world->students as $index => $student) {
        $rows[] = [
            'student_id' => $student->id,
            'marks' => [
                $world->papers['Mathematics']->id => ['obtained' => 50 + $index],
                $world->papers['English']->id => ['obtained' => 40 + $index],
            ],
        ];
    }

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    test()->postJson(route('exam.marking.save-bulk'), [
        'exam_id' => $world->exam->id,
        'students' => $rows,
    ])->assertSuccessful();

    return $queries;
}

it('reads the result lines once for the whole batch', function () {
    $reads = collect(queriesForBulkSave($this->world))
        ->filter(fn (string $sql) => str_contains($sql, 'select * from "exam_result_lines"')
            && str_contains($sql, 'result_header_id" in'));

    // One read for eight children, not eight.
    expect($reads)->toHaveCount(1);
});

it('reads the grading scale once, not once per mark', function () {
    $reads = collect(queriesForBulkSave($this->world))
        ->filter(fn (string $sql) => str_contains($sql, 'from "grade_system_items"'));

    // Sixteen marks were sixteen lookups.
    expect($reads->count())->toBeLessThanOrEqual(1);
});

it('reads the papers once, not once per child', function () {
    $reads = collect(queriesForBulkSave($this->world))
        ->filter(fn (string $sql) => str_contains($sql, 'from "exam_papers"')
            && str_contains($sql, 'exam_id'));

    expect($reads->count())->toBeLessThanOrEqual(2);
});

it('still gives every child the right figures', function () {
    queriesForBulkSave($this->world);

    $headers = ExamResultHeader::where('exam_id', $this->world->exam->id)
        ->orderBy('student_id')->get();

    expect($headers)->toHaveCount(8)
        // First child: 50 + 40 out of 200.
        ->and((float) $headers->first()->overall_percentage_cache)->toBe(45.0)
        // Last: 57 + 47 out of 200.
        ->and((float) $headers->last()->overall_percentage_cache)->toBe(52.0);
});
