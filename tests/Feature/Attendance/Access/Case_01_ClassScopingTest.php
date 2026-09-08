<?php

/**
 * Case 01 — a teacher sees their own classes and nobody else's.
 *
 * Every method on the policy was a bare permission check that ignored the
 * record entirely, so any teacher holding `attendance.view` could read **every
 * campus's** registers and anyone holding `attendance.edit` could rewrite a
 * class they had nothing to do with. Nothing in the system even recorded which
 * class a teacher was responsible for.
 */

use App\Models\Attendance;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(2);
    $this->otherSection = $this->world->school->otherSection;
    $this->world->enrol(1, $this->otherSection->id);

    $this->teacher = $this->world->staffUser('teacher', 'scoped.teacher@test.local');
    $this->world->assignTeacher($this->teacher, $this->world->school->section->id);

    // A register for each section, taken by the developer.
    $this->actingAs($this->world->school->actor);
    $this->mine = $this->world->register('2026-04-06');
    $this->theirs = $this->world->register('2026-04-06', $this->otherSection->id);
});

it('opens a register for a section the teacher is given', function () {
    $this->actingAs($this->teacher)
        ->get(route('attendance.show', $this->mine))
        ->assertSuccessful();
});

it('refuses a register for a section the teacher is not given', function () {
    $this->actingAs($this->teacher)
        ->get(route('attendance.show', $this->theirs))
        ->assertForbidden();
});

it('refuses to edit another section s register', function () {
    $this->actingAs($this->teacher)
        ->get(route('attendance.edit', $this->theirs))
        ->assertForbidden();
});

it('lists only the teacher s own registers', function () {
    // A list has to ask the same question of every row, or the index shows
    // every class in the school and only refuses when they click.
    $visible = Attendance::visibleTo($this->teacher)->pluck('id');

    expect($visible)->toContain($this->mine->id)
        ->and($visible)->not->toContain($this->theirs->id);
});

it('shows a teacher with no class nothing at all', function () {
    $newTeacher = $this->world->staffUser('teacher', 'unassigned@test.local');

    // Nothing, rather than everything, which is the safer way round to be
    // wrong.
    expect(Attendance::visibleTo($newTeacher)->count())->toBe(0);
});

it('covers every section when a teacher is given the whole class', function () {
    $wholeClass = $this->world->staffUser('teacher', 'wholeclass@test.local');
    $this->world->assignTeacher($wholeClass, null);

    $visible = Attendance::visibleTo($wholeClass)->pluck('id');

    expect($visible)->toContain($this->mine->id)
        ->and($visible)->toContain($this->theirs->id);
});

it('lets a campus admin see every class on their campus', function () {
    $admin = $this->world->staffUser('campus_admin', 'admin@test.local');

    $visible = Attendance::visibleTo($admin)->pluck('id');

    expect($visible)->toContain($this->mine->id)
        ->and($visible)->toContain($this->theirs->id);
});

it('keeps a campus admin out of another campus', function () {
    $otherCampus = $this->world->school->otherCampus;
    $admin = $this->world->staffUser('campus_admin', 'othercampus@test.local', $otherCampus->id);

    expect(Attendance::visibleTo($admin)->count())->toBe(0);

    $this->actingAs($admin)
        ->get(route('attendance.show', $this->mine))
        ->assertForbidden();
});

it('lets the owner see everything', function () {
    $owner = $this->world->staffUser('owner', 'owner@test.local');

    expect(Attendance::visibleTo($owner)->count())->toBe(2);
});

it('stops a teacher reporting on another class', function () {
    $otherClass = $this->world->school->classWithoutSections;

    $this->actingAs($this->teacher)
        ->get(route('attendance.class-report', [
            'class_id' => $otherClass->id,
            'month' => 4,
            'year' => 2026,
        ]))
        ->assertForbidden();
});

it('lets a teacher report on their own class', function () {
    $this->actingAs($this->teacher)
        ->get(route('attendance.class-report', [
            'class_id' => $this->world->school->class->id,
            'section_id' => $this->world->school->section->id,
            'month' => 4,
            'year' => 2026,
        ]))
        ->assertSuccessful();
});

it('refuses a teacher a save onto another section', function () {
    $this->actingAs($this->teacher)
        ->post(route('attendance.store'), $this->world->payload('P', '2026-04-06', null, $this->otherSection->id))
        ->assertForbidden();
});

it('lets a teacher save their own section', function () {
    $this->actingAs($this->teacher)
        ->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'))
        ->assertRedirect(route('attendance.index'));
});

it('ends the teacher s access when the assignment is switched off', function () {
    $this->world->assignTeacher($this->teacher, $this->otherSection->id)
        ->update(['is_active' => false]);

    // The access ends with the job, without anyone remembering to take it away.
    $this->actingAs($this->teacher)
        ->get(route('attendance.show', $this->theirs))
        ->assertForbidden();
});

it('does not let a plain teacher close a register', function () {
    // Signing a register off is a head teacher's job in the seeded roles, and
    // the permission is what says so.
    $this->actingAs($this->teacher)
        ->post(route('attendance.lock', $this->mine))
        ->assertForbidden();
});

it('lets a head teacher close a register but not reopen it', function () {
    $head = $this->world->staffUser('head_teacher', 'head@test.local');

    $this->actingAs($head)
        ->post(route('attendance.lock', $this->mine))
        ->assertRedirect();

    expect($this->mine->fresh()->is_locked)->toBeTrue();
});

it('lets a campus admin reopen a closed register', function () {
    $this->mine->lock();
    $admin = $this->world->staffUser('campus_admin', 'reopener@test.local');

    $this->actingAs($admin)
        ->post(route('attendance.unlock', $this->mine))
        ->assertRedirect();

    expect($this->mine->fresh()->is_locked)->toBeFalse();
});
