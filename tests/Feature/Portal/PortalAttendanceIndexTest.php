<?php

/**
 * `attendance.view.own` was seeded from the start and nothing consumed it —
 * no route or controller existed for a student/guardian to read their own
 * attendance. `/portal/attendance` is that endpoint.
 */

use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('lets a student see only their own attendance history', function () {
    [$userA, $studentA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $this->world->attendanceFor($studentA, 'P');
    $this->world->attendanceFor($studentB, 'A');

    $response = $this->actingAs($userA)->get(route('portal.attendance.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Attendance/Index')
        ->has('records.data', 1)
        ->where('records.data.0.attendance_status.code', 'P')
    );
});
