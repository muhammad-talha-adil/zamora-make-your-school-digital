<?php

/**
 * Case 01 — who may read and change an exam.
 *
 * There was no policy in this module and not one `authorize()` call in any of
 * the eight controllers. Every route sat on `['web', 'auth']` and nothing else,
 * so **any signed-in user** — a student, a guardian, a driver, a maid — could
 * read every child's marks in every class of every campus, enter and change
 * them, publish results, and lock or unlock an exam.
 *
 * These tests are the fence. They use the real roles and the real permission
 * tables, because a fence tested against a stub is not a fence.
 */

use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\User;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->students = $this->world->enrol(1);
    $this->world->paper('Mathematics');
});

/** A signed-in nobody: a role with no business in this module. */
function outsider(ExamWorld $world): User
{
    return $world->staff('driver');
}

/** One child's marks, posted the way the grid posts them. */
function markAs(ExamWorld $world, User $user, float $marks)
{
    return test()->actingAs($user)->postJson(
        route('exam.marking.save-row'),
        $world->marksPayload($world->students[0], ['Mathematics' => ['obtained' => $marks]])
    );
}

it('turns a driver away from the exam list', function () {
    $this->actingAs(outsider($this->world))
        ->get(route('exam.index-page'))
        ->assertForbidden();
});

it('turns a driver away from the marking grid', function () {
    $this->actingAs(outsider($this->world))
        ->getJson(route('exam.marking.grid-data', ['exam_id' => $this->world->exam->id]))
        ->assertForbidden();
});

it('turns a driver away from entering marks', function () {
    markAs($this->world, outsider($this->world), 90)->assertForbidden();

    expect(ExamResultHeader::count())->toBe(0);
});

it('turns a driver away from publishing results', function () {
    $this->actingAs(outsider($this->world))
        ->patchJson(route('exam.publish', $this->world->exam->id))
        ->assertForbidden();

    expect($this->world->exam->fresh()->published_at)->toBeNull();
});

it('turns a driver away from locking an exam', function () {
    $this->actingAs(outsider($this->world))
        ->patchJson(route('exam.lock', $this->world->exam->id))
        ->assertForbidden();

    expect($this->world->exam->fresh()->is_locked)->toBeFalse();
});

it('turns a driver away from the grading scale', function () {
    $this->actingAs(outsider($this->world))
        ->postJson(route('exam.grade-scales.items.store', $this->world->gradeSystem->id), [
            'grade_letter' => 'Z',
            'min_percentage' => 0,
            'max_percentage' => 100,
        ])
        ->assertForbidden();
});

it('lets a teacher mark the section they were given', function () {
    markAs($this->world, $this->world->teacher(assigned: true), 90)->assertSuccessful();
});

it('does not let a teacher mark a section they were not given', function () {
    markAs($this->world, $this->world->teacher(assigned: false), 90)->assertForbidden();

    expect(ExamResultHeader::count())->toBe(0);
});

it('does not let a teacher publish the results they marked', function () {
    // Marking a paper and putting the result in front of parents are two
    // different decisions, and two different abilities.
    $this->actingAs($this->world->teacher())
        ->patchJson(route('exam.publish', $this->world->exam->id))
        ->assertForbidden();
});

it('does not let a teacher reopen a locked exam', function () {
    $this->world->exam->update(['is_locked' => true]);

    $this->actingAs($this->world->teacher())
        ->patchJson(route('exam.unlock', $this->world->exam->id))
        ->assertForbidden();

    expect($this->world->exam->fresh()->is_locked)->toBeTrue();
});

it('does not let a teacher change the grading scale', function () {
    $this->actingAs($this->world->teacher())
        ->postJson(route('exam.grade-scales.items.store', $this->world->gradeSystem->id), [
            'grade_letter' => 'Z',
            'min_percentage' => 0,
            'max_percentage' => 100,
        ])
        ->assertForbidden();
});

it('shows a teacher only the results of their own sections', function () {
    // A result in the other section, which this teacher has nothing to do with.
    ExamResultHeader::create([
        'exam_id' => $this->world->exam->id,
        'student_id' => $this->students[0]->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->otherSection->id,
        'status' => ExamResultHeader::STATUS_SUBMITTED,
        'total_obtained_cache' => 90,
        'overall_percentage_cache' => 90,
    ]);

    $rows = $this->actingAs($this->world->teacher())
        ->getJson(route('exam.results.index', ['exam_id' => $this->world->exam->id]))
        ->assertSuccessful()
        ->json('data');

    // The policy guards one record and the scope filters the list. They have to
    // agree, or the list screen leaks what the record screen refuses.
    expect($rows)->toBeEmpty();
});

it('shows a campus admin their own campus and no other', function () {
    // Posted to the other campus.
    $admin = $this->world->staff('campus_admin', $this->world->school->otherCampus->id);

    ExamResultHeader::create([
        'exam_id' => $this->world->exam->id,
        'student_id' => $this->students[0]->id,
        'campus_id' => $this->world->school->campus->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'status' => ExamResultHeader::STATUS_SUBMITTED,
        'total_obtained_cache' => 90,
        'overall_percentage_cache' => 90,
    ]);

    $rows = $this->actingAs($admin)
        ->getJson(route('exam.results.index', ['exam_id' => $this->world->exam->id]))
        ->assertSuccessful()
        ->json('data');

    expect($rows)->toBeEmpty();
});

it('refuses to edit a result that has been published', function () {
    markAs($this->world, $this->world->school->actor, 90)->assertSuccessful();

    ExamResultHeader::query()->update(['status' => ExamResultHeader::STATUS_PUBLISHED]);

    // Published results are reopened deliberately, not edited in place.
    markAs($this->world, $this->world->teacher(), 40)->assertForbidden();

    expect((float) ExamResultHeader::first()->total_obtained_cache)->toBe(90.0);
});

it('lets the office do the whole thing', function () {
    // Nothing above should have narrowed the roles that are meant to work.
    $owner = $this->world->staff('owner');

    markAs($this->world, $owner, 90)->assertSuccessful();

    $this->actingAs($owner)
        ->patchJson(route('exam.publish', $this->world->exam->id), ['force' => true])
        ->assertSuccessful();

    expect($this->world->exam->fresh()->status)->toBe(Exam::STATUS_PUBLISHED);
});
