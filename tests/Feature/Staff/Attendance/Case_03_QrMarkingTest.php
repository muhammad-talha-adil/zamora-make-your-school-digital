<?php

/**
 * The unauthenticated staff side of QR attendance — the parallel to
 * `tests/Feature/Attendance/Case_QR_01_StudentQrMarkingTest.php`, but for a
 * member of staff scanning their own ID card instead of a class register.
 */

use App\Models\AttendancePolicy;
use App\Models\Staff\StaffAttendance;
use Database\Seeders\AttendanceStatusesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new AttendanceStatusesSeeder)->run();

    AttendancePolicy::create([
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'working_days' => [1, 2, 3, 4, 5, 6],
        'absence_alert_enabled' => false,
        'is_active' => true,
    ]);

    Carbon::setTestNow('2026-09-21 08:00:00'); // a Monday, a working day
});

afterEach(function () {
    Carbon::setTestNow();
});

it('marks a staff member present on a validly signed link, with no login', function () {
    $staff = $this->world->person('Zainab Ali');

    $signed = URL::signedRoute('attendance.qr.staff', $staff);

    $response = $this->get($signed);

    $response->assertOk();
    expect(StaffAttendance::where('staff_profile_id', $staff->id)->count())->toBe(1);
});

it('refuses an unsigned request', function () {
    $staff = $this->world->person('Zainab Ali');

    $response = $this->get(route('attendance.qr.staff', $staff));

    $response->assertForbidden();
});

it('refuses a link tampered to point at a different staff member', function () {
    $staffA = $this->world->person('Zainab Ali');
    $staffB = $this->world->person('Bilal Khan');

    $signed = URL::signedRoute('attendance.qr.staff', $staffA);
    $tampered = str_replace((string) $staffA->id, (string) $staffB->id, $signed);

    $response = $this->get($tampered);

    $response->assertForbidden();
});

it('does not duplicate the record when scanned twice the same day', function () {
    $staff = $this->world->person('Zainab Ali');

    $signed = URL::signedRoute('attendance.qr.staff', $staff);

    $this->get($signed)->assertOk();
    $this->get($signed)->assertOk();

    expect(StaffAttendance::where('staff_profile_id', $staff->id)->count())->toBe(1);
});
