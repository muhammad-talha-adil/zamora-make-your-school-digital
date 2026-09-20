<?php

/**
 * Case 04 — a timing that names a group of classes rather than the whole
 * campus (#103): "5 junior classes finish at 12, everyone else at 1" needs a
 * timing that knows which classes it covers, and a class-specific timing
 * should win over a general one even when the general one has a narrower
 * date span.
 */

use App\Models\AttendanceTiming;
use App\Models\SchoolClass;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->campus = $this->world->school->campus;
    $this->session = $this->world->school->session;
    $this->class = $this->world->school->class;
});

it('applies a timing with no class_ids to every class', function () {
    AttendanceTiming::create([
        'campus_id' => $this->campus->id,
        'session_id' => $this->session->id,
        'name' => 'General',
        'starts_on' => '2026-04-01',
        'ends_on' => '2027-03-31',
        'day_starts_at' => '09:00',
        'late_after' => '09:15',
        'day_ends_at' => '13:00',
        'is_active' => true,
    ]);

    $found = AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id, $this->class->id);

    expect($found?->name)->toBe('General');
});

it('only applies a class-scoped timing to the classes it names', function () {
    $otherClass = SchoolClass::create(['name' => 'Other Class']);

    AttendanceTiming::create([
        'campus_id' => $this->campus->id,
        'session_id' => $this->session->id,
        'class_ids' => [$this->class->id],
        'name' => 'Junior',
        'starts_on' => '2026-04-01',
        'ends_on' => '2027-03-31',
        'day_starts_at' => '09:00',
        'late_after' => '09:15',
        'day_ends_at' => '12:00',
        'is_active' => true,
    ]);

    expect(AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id, $this->class->id)?->name)
        ->toBe('Junior')
        ->and(AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id, $otherClass->id))
        ->toBeNull();
});

it('prefers the class-specific timing over a general one, whatever their spans', function () {
    AttendanceTiming::create([
        'campus_id' => $this->campus->id,
        'session_id' => $this->session->id,
        'name' => 'General',
        'starts_on' => '2026-04-01',
        'ends_on' => '2026-04-10',
        'day_starts_at' => '09:00',
        'late_after' => '09:15',
        'day_ends_at' => '13:00',
        'is_active' => true,
    ]);

    AttendanceTiming::create([
        'campus_id' => $this->campus->id,
        'session_id' => $this->session->id,
        'class_ids' => [$this->class->id],
        'name' => 'Junior',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-12-31',
        'day_starts_at' => '09:00',
        'late_after' => '09:15',
        'day_ends_at' => '12:00',
        'is_active' => true,
    ]);

    // The general timing has the narrower span, but the class-specific one
    // still wins for the class it names.
    expect(AttendanceTiming::inForce($this->campus->id, '2026-04-06', $this->session->id, $this->class->id)?->name)
        ->toBe('Junior');
});

it('keeps a break window on the timing', function () {
    $timing = AttendanceTiming::create([
        'campus_id' => $this->campus->id,
        'session_id' => $this->session->id,
        'name' => 'General',
        'starts_on' => '2026-04-01',
        'ends_on' => '2027-03-31',
        'day_starts_at' => '09:00',
        'late_after' => '09:15',
        'break_starts_at' => '11:00',
        'break_ends_at' => '11:20',
        'day_ends_at' => '13:00',
        'is_active' => true,
    ]);

    expect($timing->fresh()->break_starts_at)->toContain('11:00')
        ->and($timing->fresh()->break_ends_at)->toContain('11:20');
});
