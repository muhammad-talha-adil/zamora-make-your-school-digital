<?php

/**
 * Case 01 — exams follow the campus in the header.
 *
 * An exam used to belong to the school, so the owner looking at one campus
 * still saw every other campus's exams, and every screen asked for a campus.
 */

use App\Models\Exam\Exam;
use App\Models\Exam\ExamType;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->gulberg = $this->world->school->campus;
    $this->johar = $this->world->school->otherCampus;

    $this->joharExam = Exam::create([
        'campus_id' => $this->johar->id,
        'session_id' => $this->world->school->session->id,
        'exam_type_id' => $this->world->examType->id,
        'name' => 'Johar Mid Term',
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-10',
        'status' => 'scheduled',
    ]);
});

it('lists only the exams of the header campus', function () {
    $this->withSession(['viewing_campus_id' => $this->johar->id])
        ->getJson(route('exam.index'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Johar Mid Term');

    $this->withSession(['viewing_campus_id' => $this->gulberg->id])
        ->getJson(route('exam.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'First Term 2026');
});

it('files a new exam under the header campus whatever the form says', function () {
    $this->withSession(['viewing_campus_id' => $this->johar->id])
        ->post(route('exam.store'), [
            'name' => 'Quiz One',
            'exam_type_id' => $this->world->examType->id,
            'session_id' => $this->world->school->session->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'campus_id' => $this->gulberg->id,
        ])
        ->assertRedirect();

    expect(Exam::where('name', 'Quiz One')->value('campus_id'))->toBe($this->johar->id);
});

it('shows only the header campus exams on the papers and marking pages', function () {
    $this->withSession(['viewing_campus_id' => $this->johar->id])
        ->get(route('exam.papers.create-page'))
        ->assertInertia(fn ($page) => $page->has('exams', 1)->where('exams.0.name', 'Johar Mid Term'));

    $this->withSession(['viewing_campus_id' => $this->johar->id])
        ->get(route('exam.marking.select-page'))
        ->assertInertia(fn ($page) => $page->has('exams', 1));
});

it('does not open an exam of another campus', function () {
    $this->withSession(['viewing_campus_id' => $this->gulberg->id])
        ->get(route('exam.show-page', $this->joharExam->id))
        ->assertNotFound();
});

it('hides papers of another campus from the owner', function () {
    $this->world->paper('Mathematics');

    $this->withSession(['viewing_campus_id' => $this->johar->id])
        ->getJson(route('exam.papers.index'))
        ->assertJsonCount(0, 'data');

    $this->withSession(['viewing_campus_id' => $this->gulberg->id])
        ->getJson(route('exam.papers.index'))
        ->assertJsonCount(1, 'data');
});

it('offers the usual exam types and can be run twice', function () {
    $this->artisan('exams:seed-types')->assertSuccessful();
    $count = ExamType::count();
    $this->artisan('exams:seed-types')->assertSuccessful();

    expect(ExamType::pluck('name')->all())->toContain('Mid Term', 'Final', 'Monthly Test', 'Quiz')
        ->and(ExamType::count())->toBe($count);
});
