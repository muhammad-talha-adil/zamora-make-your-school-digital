<?php

use App\Models\AttendanceStatus;
use App\Models\Staff\StaffAttendance;
use Database\Seeders\AttendanceStatusesSeeder;
use Tests\Support\StaffWorld;

/**
 * The bulk "Mark Staff Attendance" page: `StaffAttendanceController::page()`
 * and the `staff.attendance.bulk` endpoint it posts to.
 */
beforeEach(function () {
    $this->world = StaffWorld::make();
    (new AttendanceStatusesSeeder)->run();
});

it('renders the page for someone who may mark attendance', function () {
    $viewer = $this->world->person('Head Office', ['staff.attendance.mark']);

    $this->actingAs($viewer->user)->get(route('staff.attendance.page'))->assertOk();
});

it('a person without staff.attendance.mark may not open the page', function () {
    $viewer = $this->world->person('No Rights', ['staff.view']);

    $this->actingAs($viewer->user)->get(route('staff.attendance.page'))->assertForbidden();
});

it('bulk marking creates one attendance row per person for the day', function () {
    $one = $this->world->person('Zainab Ali');
    $two = $this->world->person('Bilal Khan');
    $present = AttendanceStatus::where('code', 'P')->firstOrFail();

    $response = $this->actingAs($this->world->school->actor)->postJson(route('staff.attendance.bulk'), [
        'attendance_date' => '2026-05-10',
        'rows' => [
            ['staff_profile_id' => $one->id, 'attendance_status_id' => $present->id],
            ['staff_profile_id' => $two->id, 'attendance_status_id' => $present->id],
        ],
    ]);

    $response->assertSuccessful();
    expect(StaffAttendance::whereDate('attendance_date', '2026-05-10')->count())->toBe(2);
    expect(StaffAttendance::where('staff_profile_id', $one->id)->first()->status->code)->toBe('P');
    expect(StaffAttendance::where('staff_profile_id', $two->id)->first()->status->code)->toBe('P');
});

it('the page only lists staff visible to the viewer\'s campus', function () {
    $home = $this->world->person('Zainab Ali');
    $elsewhere = $this->world->person('Away Person', campus: $this->world->school->otherCampus);

    $viewer = $this->world->person('Campus Admin', ['staff.attendance.mark', 'staff.view']);

    $response = $this->actingAs($viewer->user)->get(route('staff.attendance.page'));

    $response->assertOk();
    $staffIds = collect($response->viewData('page')['props']['staff'])->pluck('id');

    expect($staffIds)->toContain($home->id, $viewer->id);
    expect($staffIds)->not->toContain($elsewhere->id);
});

it('a non-permitted user may not bulk-mark attendance', function () {
    $staff = $this->world->person('Zainab Ali');
    $outsider = $this->world->person('No Rights', ['staff.view']);
    $present = AttendanceStatus::where('code', 'P')->firstOrFail();

    $this->actingAs($outsider->user)->postJson(route('staff.attendance.bulk'), [
        'attendance_date' => '2026-05-10',
        'rows' => [
            ['staff_profile_id' => $staff->id, 'attendance_status_id' => $present->id],
        ],
    ])->assertForbidden();
});
