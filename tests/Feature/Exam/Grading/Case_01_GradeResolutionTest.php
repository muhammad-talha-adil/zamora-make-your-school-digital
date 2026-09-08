<?php

/**
 * Case 01 — which grading scale a result is marked against.
 *
 * It was `GradeSystem::where('is_active', true)->first()`. The table has always
 * carried `campus_id` and `session_id`, and neither was ever read: a
 * multi-campus school got whichever row the database happened to return first,
 * and last year's scale was applied to this year's results — or the reverse.
 *
 * Resolution is now the same shape as the fee policy and the attendance policy:
 * **campus, then session, narrowest wins.**
 */

use App\Models\Exam\GradeSystem;
use App\Services\Exam\GradeResolver;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->campus = $this->world->school->campus;
    $this->session = $this->world->school->session;
    $this->resolver = new GradeResolver;
});

/** A scale, with whatever scoping the test is about. */
function scale(string $name, ?int $campusId, ?int $sessionId, bool $default = false): GradeSystem
{
    return GradeSystem::create([
        'name' => $name,
        'campus_id' => $campusId,
        'session_id' => $sessionId,
        'is_active' => true,
        'is_default' => $default,
    ]);
}

it('takes the scale naming both the campus and the session', function () {
    // ExamWorld's own scale names both.
    expect($this->resolver->systemFor($this->campus->id, $this->session->id)->id)
        ->toBe($this->world->gradeSystem->id);
});

it('prefers the campus scale over the school-wide one', function () {
    $this->world->gradeSystem->update(['session_id' => null]);
    $schoolWide = scale('School wide', null, null);

    expect($this->resolver->systemFor($this->campus->id, $this->session->id)->id)
        ->toBe($this->world->gradeSystem->id)
        ->and($this->resolver->systemFor($this->campus->id, $this->session->id)->id)
        ->not->toBe($schoolWide->id);
});

it('does not hand one campus another campus scale', function () {
    $other = $this->world->school->otherCampus;
    $schoolWide = scale('School wide', null, null);

    // The only scale naming this campus belongs to the other one, so the
    // school-wide scale is the right answer.
    expect($this->resolver->systemFor($other->id, $this->session->id)->id)
        ->toBe($schoolWide->id);
});

it('does not apply last year s scale to this year s results', function () {
    $this->world->gradeSystem->update(['campus_id' => null]);
    $lastYear = scale('Last year', null, $this->world->school->otherSession->id);

    expect($this->resolver->systemFor($this->campus->id, $this->session->id)->id)
        ->toBe($this->world->gradeSystem->id)
        ->and($this->resolver->systemFor($this->campus->id, $this->session->id)->id)
        ->not->toBe($lastYear->id);
});

it('falls back to the default scale when nothing matches', function () {
    $this->world->gradeSystem->update(['is_active' => false, 'is_default' => false]);
    $fallback = scale('Fallback', $this->world->school->otherCampus->id, null, default: true);
    $fallback->update(['is_active' => false]);

    expect($this->resolver->systemFor($this->campus->id, $this->session->id)->id)
        ->toBe($fallback->id);
});

it('still grades a school that has set none of this up', function () {
    // No campus, no session, not default — just switched on.
    $this->world->gradeSystem->update(['is_default' => false]);

    expect($this->resolver->systemFor(null, null)->id)
        ->toBe($this->world->gradeSystem->id);
});

it('gives the same answer twice', function () {
    $first = $this->resolver->systemFor($this->campus->id, $this->session->id);
    $second = $this->resolver->systemFor($this->campus->id, $this->session->id);

    expect($second->id)->toBe($first->id);
});

it('reads the band a percentage falls in', function () {
    expect($this->resolver->itemFor(85.0, $this->campus->id, $this->session->id)->grade_letter)
        ->toBe('A+')
        ->and($this->resolver->itemFor(45.0, $this->campus->id, $this->session->id)->grade_letter)
        ->toBe('D')
        ->and($this->resolver->itemFor(0.0, $this->campus->id, $this->session->id)->grade_letter)
        ->toBe('F');
});

it('has no grade for a child with no percentage', function () {
    expect($this->resolver->idFor(null, $this->campus->id, $this->session->id))->toBeNull();
});

it('grades against the campus scale, not the neighbour s', function () {
    $other = $this->world->school->otherCampus;
    $otherScale = scale('Other campus', $other->id, null);

    $otherScale->gradeSystemItems()->create([
        'grade_letter' => 'DISTINCTION',
        'min_percentage' => 0,
        'max_percentage' => 100,
        'grade_point' => 4,
        'sort_order' => 0,
    ]);

    expect($this->resolver->itemFor(85.0, $other->id, $this->session->id)->grade_letter)
        ->toBe('DISTINCTION')
        ->and($this->resolver->itemFor(85.0, $this->campus->id, $this->session->id)->grade_letter)
        ->toBe('A+');
});
