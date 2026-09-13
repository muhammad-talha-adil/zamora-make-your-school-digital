<?php

use App\Models\AttendanceStatus;
use App\Models\Staff\StaffAttendance;
use Database\Seeders\AttendanceStatusesSeeder;
use Illuminate\Testing\TestResponse;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new AttendanceStatusesSeeder)->run();
});

function markStaffDay(StaffWorld $world, $staff, string $date, string $code = 'P'): TestResponse
{
    $status = AttendanceStatus::where('code', $code)->firstOrFail();

    return test()->actingAs($world->school->actor)->postJson(
        route('staff.attendance.store', $staff),
        ['attendance_date' => $date, 'attendance_status_id' => $status->id]
    );
}

it('marks a day present', function () {
    $staff = $this->world->person('Zainab Ali');

    $response = markStaffDay($this->world, $staff, '2026-04-06');

    $response->assertSuccessful();
    expect(StaffAttendance::where('staff_profile_id', $staff->id)->count())->toBe(1);
});

it('re-marking the same day updates it instead of duplicating', function () {
    $staff = $this->world->person('Zainab Ali');

    markStaffDay($this->world, $staff, '2026-04-06', 'P')->assertSuccessful();
    markStaffDay($this->world, $staff, '2026-04-06', 'A')->assertSuccessful();

    expect(StaffAttendance::where('staff_profile_id', $staff->id)->count())->toBe(1);
    expect(StaffAttendance::where('staff_profile_id', $staff->id)->first()->status->code)->toBe('A');
});

it('refuses to re-mark a locked day', function () {
    $staff = $this->world->person('Zainab Ali');
    markStaffDay($this->world, $staff, '2026-04-06')->assertSuccessful();

    $this->actingAs($this->world->school->actor)->postJson(route('staff.attendance.lock'), [
        'attendance_date' => '2026-04-06',
    ])->assertSuccessful();

    markStaffDay($this->world, $staff, '2026-04-06', 'A')->assertUnprocessable();
});

it('marks a whole campus register in one call', function () {
    $one = $this->world->person('Zainab Ali');
    $two = $this->world->person('Bilal Khan');
    $present = AttendanceStatus::where('code', 'P')->firstOrFail();

    $response = $this->actingAs($this->world->school->actor)->postJson(route('staff.attendance.bulk'), [
        'attendance_date' => '2026-04-06',
        'rows' => [
            ['staff_profile_id' => $one->id, 'attendance_status_id' => $present->id],
            ['staff_profile_id' => $two->id, 'attendance_status_id' => $present->id],
        ],
    ]);

    $response->assertSuccessful();
    expect(StaffAttendance::count())->toBe(2);
});

it('a monthly summary counts expected, marked and unmarked days', function () {
    $staff = $this->world->person('Zainab Ali');

    markStaffDay($this->world, $staff, '2026-04-06', 'P')->assertSuccessful();
    markStaffDay($this->world, $staff, '2026-04-07', 'A')->assertSuccessful();

    $response = $this->actingAs($this->world->school->actor)->getJson(
        route('staff.attendance.summary', $staff).'?from_month=4&to_month=4&year=2026'
    );

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data['present'])->toBe(1);
    expect($data['absent'])->toBe(1);
    expect($data['marked_days'])->toBe(2);
    expect($data['expected_days'])->toBeGreaterThan(2);
});

it('a person without staff.attendance.mark may not mark attendance', function () {
    $staff = $this->world->person('Zainab Ali');
    $outsider = $this->world->person('No Rights', abilities: ['staff.view']);
    $status = AttendanceStatus::where('code', 'P')->firstOrFail();

    $this->actingAs($outsider->user)->postJson(route('staff.attendance.store', $staff), [
        'attendance_date' => '2026-04-06',
        'attendance_status_id' => $status->id,
    ])->assertForbidden();
});
