<?php

/**
 * `AttendanceSettingsController` already called `$this->authorize()` on every
 * action, but against `settings.manage` — a permission that was never seeded
 * anywhere (`PermissionsSeeder` only defines the two settings abilities
 * actually used elsewhere, `attendance.settings` and `exam.settings`). With no
 * such permission to hold, every non-developer account — including a
 * `campus_admin` granted the whole `attendance.*` wildcard — was turned away
 * from every one of these routes. The fix points the checks at
 * `attendance.settings`, the permission seeded for exactly this screen.
 */

use App\Models\Holiday;
use App\Models\LeaveType;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->authorized = $this->world->staffUser('campus_admin', 'campus.admin.'.uniqid().'@school.test');
    $this->outsider = $this->world->staffUser('driver', 'driver.'.uniqid().'@school.test');
});

it('turns an unpermissioned account away from the attendance settings screen', function () {
    $this->actingAs($this->outsider)->get(route('attendance.settings'))->assertForbidden();

    $this->actingAs($this->outsider)->post(route('attendance.settings.leave-types.store'), [
        'name' => 'Sick Leave',
    ])->assertForbidden();
});

it('lets a campus admin, who holds the attendance.* wildcard, reach attendance settings', function () {
    $this->actingAs($this->authorized)->get(route('attendance.settings'))->assertOk();

    $this->actingAs($this->authorized)->post(route('attendance.settings.leave-types.store'), [
        'name' => 'Sick Leave '.uniqid(),
    ])->assertSessionHasNoErrors();

    expect(LeaveType::where('name', 'like', 'Sick Leave%')->exists())->toBeTrue();
});

it('lets a campus admin manage holidays', function () {
    $this->actingAs($this->authorized)->post(route('attendance.settings.holidays.store'), [
        'title' => 'Independence Day',
        'start_date' => '2026-08-14',
        'end_date' => '2026-08-14',
        'is_national' => true,
    ])->assertSessionHasNoErrors();

    expect(Holiday::where('title', 'Independence Day')->exists())->toBeTrue();
});
