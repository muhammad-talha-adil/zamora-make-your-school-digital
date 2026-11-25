<?php

/**
 * Case 09 — position before publishing, the grid's grade, and the admit card.
 */

use App\Models\Exam\ExamPaper;
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

it('prints an admit card for every enrolled child even before registration or marks', function () {
    $this->world->enrol(3);

    $html = $this->get(route('exam.admit-cards', $this->world->exam->id))
        ->assertSuccessful()
        ->getContent();

    expect(substr_count($html, 'Admit Card &mdash;') + substr_count($html, 'Admit Card —'))->toBe(3);
});

it('shows paper times without seconds on the admit card and date sheet', function () {
    $this->world->enrol(1);

    $html = $this->get(route('exam.admit-cards', $this->world->exam->id))->getContent();

    expect($html)->not->toContain(':00:00')
        ->and($html)->toMatch('/\d{2}:\d{2} (AM|PM)/');
});

it('shows dashboard paper times as 12-hour and dates as 02 Dec 2026', function () {
    $this->travelTo('2026-12-01 08:00:00');
    ExamPaper::query()->update(['paper_date' => '2026-12-02', 'start_time' => '09:00:00', 'end_time' => '12:00:00']);

    $response = $this->getJson(route('exam.dashboard.stats'))->assertSuccessful();

    expect($response->getContent())->toContain('02 Dec 2026')->toContain('09:00 AM')->not->toContain('09:00:00');
});
