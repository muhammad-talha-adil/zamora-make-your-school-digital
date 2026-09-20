<?php

/**
 * Case 12 — nobody may record a check-out before the shift's own off time has
 * actually arrived, on the day being marked (#105). Only meaningful for
 * today: a back-dated register already knows the whole day happened, and a
 * future date cannot be marked at all.
 */

use App\Models\AttendanceTiming;
use Illuminate\Support\Carbon;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->policy([1, 2, 3, 4, 5, 6]);

    AttendanceTiming::create([
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'name' => 'General',
        'starts_on' => '2020-01-01',
        'ends_on' => '2030-12-31',
        'day_starts_at' => '09:00',
        'late_after' => '09:15',
        'day_ends_at' => '13:00',
        'is_active' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('refuses a check-out before the shift ends today', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 10:00:00'));
    $today = now()->toDateString();

    $payload = $this->world->payload('P', $today);
    $payload['attendances'][0]['check_out'] = '10:30';
    $payload['attendances'][0]['check_in'] = '09:00';

    $this->post(route('attendance.store'), $payload)
        ->assertSessionHasErrors(['attendances.0.check_out']);
});

it('allows a check-out once the shift has ended today', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-06 13:30:00'));
    $today = now()->toDateString();

    $payload = $this->world->payload('P', $today);
    $payload['attendances'][0]['check_out'] = '13:05';
    $payload['attendances'][0]['check_in'] = '09:00';

    $this->post(route('attendance.store'), $payload)
        ->assertSessionDoesntHaveErrors();
});

it('does not restrict check-out for a back-dated register', function () {
    Carbon::setTestNow(Carbon::parse('10:00'));

    $payload = $this->world->payload('P', '2026-04-06');
    $payload['attendances'][0]['check_out'] = '10:30';
    $payload['attendances'][0]['check_in'] = '09:00';

    $this->post(route('attendance.store'), $payload)
        ->assertSessionDoesntHaveErrors();
});
