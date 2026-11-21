<?php

use App\Models\StaffProfile;
use App\Models\Timetable\TimetableEntry;
use App\Models\Timetable\TimetablePeriod;
use Tests\Support\TimetableWorld;

beforeEach(function () {
    $this->world = TimetableWorld::make();
});

it('lets a teacher view but not change the timetable', function () {
    [$user, $profile] = $this->world->teacher();
    $entry = $this->world->entry($profile);

    $this->actingAs($user)->get(route('timetable.index'))->assertOk();
    $this->actingAs($user)->post(route('timetable.entries.store'), $this->world->payload($profile, ['weekday' => 3]))->assertForbidden();
    $this->actingAs($user)->delete(route('timetable.entries.destroy', $entry))->assertForbidden();
    $this->actingAs($user)->get(route('timetable.periods.index'))->assertForbidden();
});

it('shows a teacher only the sections they have been given', function () {
    [$user, $profile] = $this->world->teacher();
    $this->world->entry($profile);
    [, $colleague] = $this->world->teacher(false);
    $this->world->entry($colleague, ['section_id' => $this->world->school->otherSection->id]);

    $this->actingAs($user)->get(route('timetable.index'))->assertInertia(fn ($page) => $page
        ->component('Timetable/Index')
        ->has('sectionOptions', 1)
        ->where('sectionOptions.0.section_id', $this->world->school->section->id)
        ->has('entries', 1)
        ->where('canManage', false)
    );

    // Asking for the other section by id still returns only what is theirs.
    $this->actingAs($user)->get(route('timetable.index', [
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->otherSection->id,
    ]))->assertInertia(fn ($page) => $page->where('filters.section_id', $this->world->school->section->id));
});

it('shows a teacher their own week across sections', function () {
    [$user, $profile] = $this->world->teacher();
    $this->world->entry($profile);
    $this->world->entry($profile, ['section_id' => $this->world->school->otherSection->id, 'weekday' => 2]);
    [, $colleague] = $this->world->teacher(false);
    $this->world->entry($colleague, ['weekday' => 4]);

    $this->actingAs($user)->get(route('timetable.mine'))->assertInertia(fn ($page) => $page
        ->component('Timetable/Mine')
        ->has('entries', 2)
        ->has('periods', 3)
    );
});

it('lets a campus admin manage their own campus only', function () {
    $admin = $this->world->admin();
    [, $profile] = $this->world->teacher(false);
    StaffProfile::create([
        'user_id' => $admin->id,
        'employee_no' => 'EMP-ADM',
        'campus_id' => $this->world->school->otherCampus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'is_active' => true,
    ]);

    $this->actingAs($admin->fresh())
        ->post(route('timetable.entries.store'), $this->world->payload($profile))
        ->assertForbidden();

    expect(TimetableEntry::count())->toBe(0);
});

it('refuses to remove a period that has lessons, and removes an empty one', function () {
    $admin = $this->world->admin();
    [, $profile] = $this->world->teacher(false);
    $this->world->entry($profile);

    $this->actingAs($admin)->delete(route('timetable.periods.destroy', $this->world->first))
        ->assertSessionHasErrors('period');
    expect(TimetablePeriod::find($this->world->first->id))->not->toBeNull();

    $this->actingAs($admin)->delete(route('timetable.periods.destroy', $this->world->second))
        ->assertSessionHasNoErrors();
    expect(TimetablePeriod::find($this->world->second->id))->toBeNull();
});

it('creates and updates periods, validating the times', function () {
    $admin = $this->world->admin();
    $school = $this->world->school;

    $this->actingAs($admin)->post(route('timetable.periods.store'), [
        'campus_id' => $school->campus->id,
        'session_id' => $school->session->id,
        'name' => 'Period 3',
        'start_time' => '10:00',
        'end_time' => '09:00',
    ])->assertSessionHasErrors('end_time');

    $this->actingAs($admin)->post(route('timetable.periods.store'), [
        'campus_id' => $school->campus->id,
        'session_id' => $school->session->id,
        'name' => 'Period 3',
        'start_time' => '10:00',
        'end_time' => '10:40',
    ])->assertSessionHasNoErrors();

    $period = TimetablePeriod::where('name', 'Period 3')->firstOrFail();

    $this->actingAs($admin)->put(route('timetable.periods.update', $period), [
        'session_id' => $school->session->id,
        'name' => 'Period Three',
        'start_time' => '10:00',
        'end_time' => '10:45',
    ])->assertSessionHasNoErrors();

    expect($period->fresh()->name)->toBe('Period Three');
});

it('does not let a period with lessons become a break', function () {
    $admin = $this->world->admin();
    [, $profile] = $this->world->teacher(false);
    $this->world->entry($profile);

    $this->actingAs($admin)->put(route('timetable.periods.update', $this->world->first), [
        'session_id' => $this->world->school->session->id,
        'name' => 'Period 1',
        'start_time' => '08:00',
        'end_time' => '08:40',
        'is_break' => true,
    ])->assertSessionHasErrors('is_break');
});
