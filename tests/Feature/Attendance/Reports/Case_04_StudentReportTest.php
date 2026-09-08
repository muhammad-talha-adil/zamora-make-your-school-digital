<?php

/**
 * Case 04 — one child's month.
 *
 * The route is `/student/{student}/report` and binds the child; the method
 * ignored the binding and demanded a `student_id` in the query string instead,
 * so calling the route as it is named failed validation before it did anything.
 */

use Inertia\Testing\AssertableInertia;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
});

/** Opens a child's report and hands back its props. */
function reportProps(AttendanceWorld $world, int $studentId, array $query = []): array
{
    $props = [];

    test()->get(route('attendance.student-report', array_merge(['student' => $studentId], $query)))
        ->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use (&$props) {
            $props = $page->toArray()['props'];
        });

    return $props;
}

it('opens for the child named in the route', function () {
    $props = reportProps($this->world, $this->students[0]->id, ['month' => 4, 'year' => 2026]);

    expect($props['student']['id'])->toBe($this->students[0]->id);
});

it('defaults to the current month when none is asked for', function () {
    $props = reportProps($this->world, $this->students[0]->id);

    // Opened from a menu, with no period: a validation error helps nobody.
    expect($props['month'])->toBe(now()->month)
        ->and($props['year'])->toBe(now()->year);
});

it('counts only that child s marks', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $props = reportProps($this->world, $this->students[0]->id, ['month' => 4, 'year' => 2026]);

    expect($props['stats']['present'])->toBe(1)
        ->and($props['stats']['total'])->toBe(1);
});

it('counts only the month asked for', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-05-06'));

    $props = reportProps($this->world, $this->students[0]->id, ['month' => 4, 'year' => 2026]);

    expect($props['stats']['total'])->toBe(1)
        ->and($props['stats']['present'])->toBe(1);
});

it('measures the percentage against the days expected', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $props = reportProps($this->world, $this->students[0]->id, ['month' => 4, 'year' => 2026]);

    // One present day out of twenty-six, not out of one.
    expect($props['expectedDays'])->toBe(26)
        ->and($props['unmarkedDays'])->toBe(25)
        ->and($props['percentage'])->toBeLessThan(5.0);
});

it('agrees with the class report', function () {
    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    $student = reportProps($this->world, $this->students[0]->id, ['month' => 4, 'year' => 2026]);

    $classSummary = [];
    $this->get(route('attendance.class-report', [
        'class_id' => $this->world->school->class->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$classSummary) {
        $classSummary = collect($page->toArray()['props']['summary'])
            ->firstWhere('registration_no', test()->students[0]->registration_no);
    });

    expect($student['expectedDays'])->toBe($classSummary['expected_days'])
        ->and($student['percentage'])->toBe($classSummary['percentage']);
});

it('rejects a month that is not a month', function () {
    $this->get(route('attendance.student-report', [
        'student' => $this->students[0]->id,
        'month' => 13,
        'year' => 2026,
    ]))->assertSessionHasErrors('month');
});

it('404s for a child who does not exist', function () {
    $this->get(route('attendance.student-report', ['student' => 999999]))->assertNotFound();
});
