<?php

use App\Models\Section;
use App\Models\Timetable\TimetableEntry;
use Tests\Support\TimetableWorld;

beforeEach(function () {
    $this->world = TimetableWorld::make();
    $this->admin = $this->world->admin();
    [, $this->teacher] = $this->world->teacher(false);
});

it('places a lesson', function () {
    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, ['room' => '12']))
        ->assertSessionHasNoErrors();

    $entry = TimetableEntry::firstOrFail();
    expect($entry->campus_id)->toBe($this->world->school->campus->id)
        ->and($entry->session_id)->toBe($this->world->school->session->id)
        ->and($entry->room)->toBe('12');
});

it('refuses a teacher who is double-booked', function () {
    $this->world->entry($this->teacher);

    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, [
            'section_id' => $this->world->school->otherSection->id,
        ]))
        ->assertSessionHasErrors('staff_profile_id');

    expect(TimetableEntry::count())->toBe(1);
});

it('refuses a section that is double-booked', function () {
    $this->world->entry($this->teacher);
    [, $other] = $this->world->teacher(false);

    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($other))
        ->assertSessionHasErrors('period_id');

    expect(TimetableEntry::count())->toBe(1);
});

it('refuses a clash between periods that overlap on the clock', function () {
    $overlap = $this->world->period('Overlap', '08:20', '09:00', 9);
    $this->world->entry($this->teacher);

    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, [
            'section_id' => $this->world->school->otherSection->id,
            'period_id' => $overlap->id,
        ]))
        ->assertSessionHasErrors('staff_profile_id');
});

it('allows the same teacher on another day, period or section slot', function () {
    $this->world->entry($this->teacher);

    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, ['weekday' => 2]))
        ->assertSessionHasNoErrors();
    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, ['period_id' => $this->world->second->id]))
        ->assertSessionHasNoErrors();

    expect(TimetableEntry::count())->toBe(3);
});

it('does not clash a lesson with itself when it is edited', function () {
    $entry = $this->world->entry($this->teacher);

    $this->actingAs($this->admin)
        ->put(route('timetable.entries.update', $entry), $this->world->payload($this->teacher, ['room' => 'Lab']))
        ->assertSessionHasNoErrors();

    expect($entry->fresh()->room)->toBe('Lab');
});

it('refuses to schedule into a break', function () {
    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, ['period_id' => $this->world->break->id]))
        ->assertSessionHasErrors('period_id');
});

it('refuses a section that belongs to another class', function () {
    $foreign = Section::create([
        'name' => 'Z',
        'code' => 'C9-Z',
        'class_id' => $this->world->school->classWithoutSections->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('timetable.entries.store'), $this->world->payload($this->teacher, ['section_id' => $foreign->id]))
        ->assertSessionHasErrors('section_id');
});
