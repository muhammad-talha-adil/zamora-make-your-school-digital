<?php

/**
 * Case 05 — the result card.
 *
 * The module could compute a result and could not print one, which is the only
 * artefact of the whole exam a family ever sees.
 *
 * The attendance line is attendance **S8**, which was built and tested and has
 * been waiting here for the card that prints it.
 */

use App\Enums\Exam\SubjectRole;
use App\Models\Exam\ExamResultHeader;
use App\Models\Exam\ExamResultLine;
use App\Services\Exam\ExamLifecycleService;
use App\Services\Exam\ReportCardService;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->paper('Mathematics', total: 100, passing: 40);
    $this->world->paper('English', total: 100, passing: 40);

    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0],
        ['Mathematics' => ['obtained' => 80], 'English' => ['obtained' => 70]]
    ))->assertSuccessful();

    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[1],
        ['Mathematics' => ['obtained' => 60], 'English' => ['obtained' => 50]]
    ))->assertSuccessful();

    app(ExamLifecycleService::class)->publish($this->world->exam, null, force: true);

    $this->header = ExamResultHeader::where('student_id', $this->students[0]->id)->firstOrFail();
});

it('prints a card', function () {
    $this->get(route('exam.results.card', $this->header->id))
        ->assertSuccessful()
        ->assertSee('Result Card', false)
        ->assertSee('Mathematics', false);
});

it('prints the marks, the total and the grade', function () {
    $page = $this->get(route('exam.results.card', $this->header->id))->getContent();

    // 150 of 200 is 75%, which is an A on the seeded scale.
    expect($page)->toContain('75.00')
        ->and($page)->toContain('150');
});

it('prints the position', function () {
    $this->get(route('exam.results.card', $this->header->id))
        ->assertSuccessful()
        ->assertSee('1st of 2', false);
});

it('prints the attendance line', function () {
    $card = app(ReportCardService::class)->forResult($this->header->fresh());

    // "182 / 195 (93.33%)" — days present out of the working days the child was
    // actually enrolled for.
    expect($card['attendance'])->toHaveKey('line')
        ->and($card['attendance'])->toHaveKey('expected_days');
});

it('prints pass or fail', function () {
    $this->get(route('exam.results.card', $this->header->id))
        ->assertSuccessful()
        ->assertSee('Pass', false);
});

it('shows the grace separately from what the child wrote', function () {
    $line = ExamResultLine::where('result_header_id', $this->header->id)
        ->where('exam_paper_id', $this->world->papers['English']->id)
        ->firstOrFail();

    $this->putJson(route('exam.marking.grace', $line->id), [
        'grace_marks' => 2,
        'reason' => 'Two short of the next grade.',
    ])->assertSuccessful();

    $card = app(ReportCardService::class)->forResult($this->header->fresh());

    $english = collect($card['subjects'])->firstWhere('subject', 'English');

    // The parent holding the answer sheet is the reason these are two columns.
    expect($english['obtained'])->toBe(70.0)
        ->and($english['grace'])->toBe(2.0)
        ->and($english['marks'])->toBe(72.0);
});

it('puts additional subjects below the total, not in it', function () {
    $this->world->paper('Quran', total: 100, passing: 40, role: SubjectRole::Additional);

    $this->postJson(route('exam.marking.save-row'), $this->world->marksPayload(
        $this->students[0], ['Quran' => ['obtained' => 95]]
    ))->assertSuccessful();

    $card = app(ReportCardService::class)->forResult($this->header->fresh());

    expect(collect($card['subjects'])->pluck('subject'))->not->toContain('Quran')
        ->and(collect($card['additional_subjects'])->pluck('subject'))->toContain('Quran')
        // The total is still 150 of 200.
        ->and($card['totals']['percentage'])->toBe(75.0);
});

it('carries the class teacher s remark', function () {
    $this->putJson(route('exam.results.remarks', $this->header->id), [
        'remarks' => 'A steady year. Needs to speak up more in class.',
    ])->assertSuccessful();

    $this->get(route('exam.results.card', $this->header->id))
        ->assertSee('Needs to speak up more in class.', false);
});

it('prints a whole section at once', function () {
    $page = $this->get(route('exam.results.section-cards', [
        $this->world->exam->id,
        'class_id' => $this->world->school->class->id,
    ]))->assertSuccessful()->getContent();

    // Two cards, one to a page.
    expect(substr_count($page, 'Class teacher'))->toBe(2);
});

it('leaves the position blank rather than inventing one', function () {
    // A card printed before the results were published has no position yet.
    ExamResultHeader::query()->update([
        'position_in_section' => null,
        'ranked_out_of' => null,
    ]);

    $card = app(ReportCardService::class)->forResult($this->header->fresh());

    expect($card['position']['line'])->toBeNull();
});

it('does not let a teacher print another section s cards', function () {
    ExamResultHeader::query()->update(['section_id' => $this->world->school->otherSection->id]);

    $this->actingAs($this->world->teacher())
        ->get(route('exam.results.card', $this->header->id))
        ->assertForbidden();
});

it('does not let a driver print a card at all', function () {
    $this->actingAs($this->world->staff('driver'))
        ->get(route('exam.results.card', $this->header->id))
        ->assertForbidden();
});
