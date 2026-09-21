<?php

/**
 * The authenticated camera-scan page (Method 1) — gated exactly like the
 * existing mark-attendance screen (`Attendance::create`, i.e. `attendance.mark`),
 * since it is only a different way to reach the same marking action.
 */

use App\Models\User;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
});

it('opens the scan page for someone who can mark attendance', function () {
    // The seeded `teacher` role already carries `attendance.mark`.
    $teacher = $this->world->staffUser('teacher', 'scan.teacher@school.test');

    $this->actingAs($teacher)
        ->get(route('attendance.scan'))
        ->assertSuccessful();
});

it('refuses someone without attendance.mark', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('attendance.scan'))
        ->assertForbidden();
});
