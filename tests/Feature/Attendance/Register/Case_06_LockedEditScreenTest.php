<?php

/**
 * Case 06 — clicking edit on a locked register.
 *
 * The guard was written and the message was written, but the method declared
 * it returned an Inertia response and the guard returned a redirect, so the
 * click threw a TypeError instead of showing the message.
 */

use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(1);

    // A teacher: `Gate::before` lets a developer past the policy entirely.
    // They are given the section too — since A13 a teacher reaches only the
    // classes they are responsible for.
    $this->teacher = $this->world->staffUser('teacher', 'teacher.edit@test.local');
    $this->world->assignTeacher($this->teacher, $this->world->school->section->id);

    $this->actingAs($this->teacher);

    $this->register = $this->world->register();
    $this->world->mark($this->register, $this->students[0], 'P');
});

it('opens the edit screen for an unlocked register', function () {
    $this->get(route('attendance.edit', $this->register))->assertSuccessful();
});

it('refuses a teacher the edit screen for a locked register', function () {
    $this->register->lock();

    // The policy denies before the guard is reached; the teacher is stopped.
    $this->get(route('attendance.edit', $this->register))->assertForbidden();
});

it('sends a developer back with a message instead of throwing', function () {
    $this->register->lock();

    // `Gate::before` lets a developer past the policy, so this is the one path
    // that reaches the guard — and it threw a TypeError, because the method
    // declared it returned an Inertia response and the guard returns a redirect.
    $this->actingAs($this->world->school->actor)
        ->get(route('attendance.edit', $this->register))
        ->assertRedirect(route('attendance.index'))
        ->assertSessionHas('error');
});

it('opens the show screen for a locked register', function () {
    $this->register->lock();

    // Reading a signed-off register is not editing it.
    $this->get(route('attendance.show', $this->register))->assertSuccessful();
});
