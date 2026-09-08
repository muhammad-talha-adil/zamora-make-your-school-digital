<?php

/**
 * Case 02 — a grading scale that cannot be read two ways.
 *
 * The unique index on `(grade_system_id, min_percentage, max_percentage)` stops
 * the identical band being entered twice, and does nothing at all about 80–90
 * sitting alongside 90–100. Both match 90, and which one a child gets is then
 * decided by row order — a coin toss dressed up as a rule.
 *
 * The scale is refused at the point it is saved instead, so the lookup never
 * has to arbitrate.
 */

use App\Models\Exam\GradeSystem;
use App\Services\Exam\GradeResolver;
use Tests\Support\ExamWorld;

beforeEach(function () {
    $this->world = ExamWorld::make();
    $this->actingAs($this->world->school->withFullRoles()->actor);
    $this->scale = $this->world->gradeSystem;
});

/** Adds a band to the shared scale. */
function addBand(GradeSystem $scale, string $letter, float $min, float $max)
{
    return test()->postJson(route('exam.grade-scales.items.store', $scale->id), [
        'grade_letter' => $letter,
        'min_percentage' => $min,
        'max_percentage' => $max,
        'grade_point' => 1,
    ]);
}

it('accepts a band in a gap the scale leaves', function () {
    // The seeded scale runs 0–39.99, and nothing sits below 0.
    $this->scale->gradeSystemItems()->where('grade_letter', 'F')->delete();

    addBand($this->scale, 'F', 0, 39.99)->assertCreated();
});

it('refuses a band overlapping one already saved', function () {
    // 'A' is 70–79.99 in the seeded scale.
    addBand($this->scale, 'A-', 75, 85)
        ->assertStatus(422)
        ->assertJsonPath('errors.bands.0', fn ($message) => str_contains($message, 'overlap'));
});

it('refuses the 80-90 alongside 90-100 case exactly', function () {
    $scale = GradeSystem::create(['name' => 'Fresh', 'is_active' => true]);

    addBand($scale, 'A', 80, 90)->assertCreated();
    addBand($scale, 'A+', 90, 100)->assertStatus(422);
});

it('refuses a band that starts above where it ends', function () {
    $scale = GradeSystem::create(['name' => 'Fresh', 'is_active' => true]);

    addBand($scale, 'X', 90, 10)->assertStatus(422);
});

it('writes nothing when a band is refused', function () {
    addBand($this->scale, 'A-', 75, 85);

    expect($this->scale->gradeSystemItems()->where('grade_letter', 'A-')->exists())
        ->toBeFalse();
});

it('lets a band be edited without finding it overlaps itself', function () {
    $band = $this->scale->gradeSystemItems()->where('grade_letter', 'C')->firstOrFail();

    // 50–59.99 stays where it is; only the grade point changes.
    $this->putJson(route('exam.grade-scales.items.update', [$this->scale->id, $band->id]), [
        'grade_letter' => 'C',
        'min_percentage' => 50,
        'max_percentage' => 59.99,
        'grade_point' => 2.75,
    ])->assertSuccessful();

    expect((float) $band->fresh()->grade_point)->toBe(2.75);
});

it('refuses an edit that walks a band into its neighbour', function () {
    $band = $this->scale->gradeSystemItems()->where('grade_letter', 'C')->firstOrFail();

    // 'B' is 60–69.99. Stretching C to 65 collides with it.
    $this->putJson(route('exam.grade-scales.items.update', [$this->scale->id, $band->id]), [
        'grade_letter' => 'C',
        'min_percentage' => 50,
        'max_percentage' => 65,
        'grade_point' => 2.5,
    ])->assertStatus(422);

    expect((float) $band->fresh()->max_percentage)->toBe(59.99);
});

it('reports what is wrong in words a school can act on', function () {
    $resolver = new GradeResolver;

    $problems = $resolver->problemsWith([
        ['min_percentage' => 80, 'max_percentage' => 90, 'grade_letter' => 'A'],
        ['min_percentage' => 90, 'max_percentage' => 100, 'grade_letter' => 'A+'],
    ]);

    expect($problems)->toHaveCount(1)
        ->and($problems[0])->toContain('80')
        ->and($problems[0])->toContain('overlap');
});

it('is happy with a scale that has no problems', function () {
    $resolver = new GradeResolver;

    expect($resolver->problemsWith($resolver->bandsOf($this->scale)))->toBe([]);
});
