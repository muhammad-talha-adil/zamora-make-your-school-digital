<?php

/**
 * Case 07 — the datesheet.
 *
 * `exam_papers` has held the date and the times since the beginning, so this is
 * a printable view of what already exists: the sheet pinned to the notice board
 * and sent home three weeks before the exams start.
 */

use App\Services\Exam\DatesheetService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->paper('Mathematics', total: 100, passing: 40);
});

/** The sheet as the service builds it. */
function sheetFor(ExamWorld $world): array
{
    return app(DatesheetService::class)->forExam($world->exam);
}

it('prints a datesheet', function () {
    $this->get(route('exam.datesheet', $this->world->exam->id))
        ->assertSuccessful()
        ->assertSee('Date Sheet', false)
        ->assertSee('Mathematics', false);
});

it('groups the papers by day', function () {
    $english = $this->world->paper('English', total: 100, passing: 40);
    $english->update(['paper_date' => '2026-10-08']);

    $sheet = sheetFor($this->world);

    // How a child reads it: "Monday the sixth, Maths, nine to twelve".
    expect($sheet['days'])->toHaveCount(2)
        ->and($sheet['days'][0]['date'])->toBe('2026-10-06')
        ->and($sheet['days'][1]['date'])->toBe('2026-10-08');
});

it('names the day of the week', function () {
    expect(sheetFor($this->world)['days'][0]['day_name'])->toBe('Tuesday');
});

it('works out how long the paper is', function () {
    // Nine to twelve.
    expect(sheetFor($this->world)['days'][0]['papers'][0]['duration'])->toBe('3 hours');
});

it('puts the papers of a day in time order', function () {
    $afternoon = $this->world->paper('English', total: 100, passing: 40);
    $afternoon->update(['start_time' => '14:00', 'end_time' => '16:00']);

    $papers = sheetFor($this->world)['days'][0]['papers'];

    expect($papers[0]['subject'])->toBe('Mathematics')
        ->and($papers[1]['subject'])->toBe('English');
});

it('shows a cancelled paper rather than dropping it', function () {
    $this->world->papers['Mathematics']->update(['status' => 'cancelled']);

    // A paper that has been called off is exactly what a family needs telling.
    // A sheet that silently loses the row leaves them turning up for it.
    expect(sheetFor($this->world)['days'][0]['papers'][0]['is_cancelled'])->toBeTrue();

    $this->get(route('exam.datesheet', $this->world->exam->id))
        ->assertSee('cancelled', false);
});

it('says the span the exams run over', function () {
    $english = $this->world->paper('English', total: 100, passing: 40);
    $english->update(['paper_date' => '2026-10-12']);

    $sheet = sheetFor($this->world);

    expect($sheet['first_date'])->toBe('2026-10-06')
        ->and($sheet['last_date'])->toBe('2026-10-12')
        ->and($sheet['paper_count'])->toBe(2);
});

it('narrows to one class when asked', function () {
    $this->getJson(route('exam.datesheet', [
        $this->world->exam->id,
        'class_id' => $this->world->school->class->id,
    ]))->assertSuccessful();

    $sheet = app(DatesheetService::class)->forExam(
        $this->world->exam,
        $this->world->school->class->id
    );

    expect($sheet['paper_count'])->toBe(1);
});

it('says so plainly when nothing is timetabled yet', function () {
    $this->world->papers['Mathematics']->delete();

    $this->get(route('exam.datesheet', $this->world->exam->id))
        ->assertSuccessful()
        ->assertSee('No papers have been put on the timetable', false);
});

it('shows a teacher only their own sections', function () {
    $other = $this->world->paper('English', total: 100, passing: 40);
    $other->update(['section_id' => $this->world->school->otherSection->id]);

    $teacher = $this->world->teacher();

    $sheet = app(DatesheetService::class)->forExam(
        $this->world->exam, null, null, null, $teacher
    );

    expect($sheet['paper_count'])->toBe(1)
        ->and($sheet['days'][0]['papers'][0]['subject'])->toBe('Mathematics');
});

it('does not let a driver read the datesheet', function () {
    $this->actingAs($this->world->staff('driver'))
        ->get(route('exam.datesheet', $this->world->exam->id))
        ->assertForbidden();
});
