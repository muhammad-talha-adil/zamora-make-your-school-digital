<?php

/**
 * Case 17 — marking another student on a register that already holds saved rows.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
});

it('adds a second student to the existing register, one header', function () {
    $first = test()->world->payload('P', '2026-04-06', [test()->students[0]]);
    test()->post(route('attendance.store'), $first)->assertSessionHasNoErrors();

    $second = test()->world->payload('P', '2026-04-06', [test()->students[1]]);
    test()->post(route('attendance.store'), $second)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(Attendance::count())->toBe(1)
        ->and(AttendanceStudent::count())->toBe(2);
});

it('accepts preloaded H:i:s times and serves saved times as H:i', function () {
    $payload = test()->world->payload('P', '2026-04-06', [test()->students[0]]);
    $payload['attendances'][0]['check_in'] = '08:00:00';

    test()->post(route('attendance.store'), $payload)->assertSessionHasNoErrors();

    $row = AttendanceStudent::first();
    expect(substr((string) $row->check_in, 0, 5))->toBe('08:00');

    $school = test()->world->school;
    $json = test()->getJson(route('attendance.api.students', [
        'class_id' => $school->class->id,
        'section_id' => $school->section->id,
        'session_id' => $school->session->id,
        'campus_id' => $school->campus->id,
        'date' => '2026-04-06',
    ]));

    $saved = collect($json->json('students'))->firstWhere('id', test()->students[0]->id);
    expect($saved['existing_attendance']['check_in'])->toBe('08:00');
});
