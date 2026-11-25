<?php

/**
 * Case 09 — position before publishing, the grid's grade, and the admit card.
 */

use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->paper('Mathematics', total: 100, passing: 40);
});

it('shows position 1, 2 on the results list before anything is published', function () {
    $students = $this->world->enrol(2);

    foreach ([60, 90] as $index => $obtained) {
        $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload($students[$index], ['Mathematics' => ['obtained' => $obtained]]))
            ->assertSuccessful();
    }

    $rows = $this->getJson(route('exam.results.index', ['exam_id' => $this->world->exam->id]))
        ->assertSuccessful()
        ->json('data');

    expect(collect($rows)->pluck('position_in_section')->sort()->values()->all())->toBe([1, 2]);
});

it('gives the grid the same grade the results page reads', function () {
    $students = $this->world->enrol(1);

    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload($students[0], ['Mathematics' => ['obtained' => 30]]))
        ->assertSuccessful();

    $grid = $this->getJson(route('exam.marking.grid-data', [
        'exam_id' => $this->world->exam->id,
        'class_id' => $this->world->school->class->id,
    ]))->assertSuccessful();

    expect($grid->json('students.0.grade'))->toBe('F')
        ->and($grid->json('grade_bands'))->not->toBeEmpty()
        ->and($grid->json('students.0.student'))->not->toHaveKey('campus');
});

it('prints an admit card for each registered child', function () {
    $this->world->enrol(1);

    $this->postJson(route('exam.registrations.generate'), ['exam_id' => $this->world->exam->id])->assertSuccessful();

    $this->get(route('exam.admit-cards', $this->world->exam->id))
        ->assertSuccessful()
        ->assertSee('Admit Card', false)
        ->assertSee('Mathematics', false);
});
