<?php

use Tests\Support\TimetableWorld;

beforeEach(function () {
    $this->world = TimetableWorld::make();
    [, $this->teacher] = $this->world->teacher(false);
});

it('shows a student their own section timetable and nothing else', function () {
    [$user] = $this->world->portal->portalStudent();
    $this->world->entry($this->teacher);
    [, $colleague] = $this->world->teacher(false);
    $this->world->entry($colleague, ['section_id' => $this->world->school->otherSection->id]);

    $this->actingAs($user)->get(route('portal.timetable.index'))->assertInertia(fn ($page) => $page
        ->component('Portal/Timetable/Index')
        ->has('entries', 1)
        ->where('entries.0.section_id', $this->world->school->section->id)
        ->has('periods', 3)
    );
});

it('shows a guardian the chosen child and refuses someone else\'s child', function () {
    [, $child] = $this->world->portal->portalStudent('a');
    [, $stranger] = $this->world->portal->portalStudent('b');
    $guardian = $this->world->portal->guardianFor([$child]);
    $this->world->entry($this->teacher);

    $this->actingAs($guardian)->get(route('portal.timetable.index', ['student_id' => $child->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('entries', 1));

    $this->actingAs($guardian)->get(route('portal.timetable.index', ['student_id' => $stranger->id]))
        ->assertForbidden();
});

it('sends a student who opens the staff timetable to their own', function () {
    [$user] = $this->world->portal->portalStudent();

    $this->actingAs($user)->get(route('timetable.index'))->assertRedirect(route('portal.timetable.index'));
});

it('keeps a student out of every manage route', function () {
    [$user] = $this->world->portal->portalStudent();
    $entry = $this->world->entry($this->teacher);

    $this->actingAs($user)->post(route('timetable.entries.store'), $this->world->payload($this->teacher, ['weekday' => 2]))->assertForbidden();
    $this->actingAs($user)->delete(route('timetable.entries.destroy', $entry))->assertForbidden();
    $this->actingAs($user)->get(route('timetable.periods.index'))->assertForbidden();
});

it('keeps a teacher out of the portal timetable', function () {
    [$user] = $this->world->teacher();

    $this->actingAs($user)->get(route('portal.timetable.index'))->assertForbidden();
});
