<?php

/**
 * Browser QA 01w: payroll history on the profile, staff "not marked" counts
 * only real working days, dashboard figures follow the attendance records,
 * and two holidays on one day both show.
 */

use App\Models\AttendanceStatus;
use App\Models\Holiday;
use App\Models\Month;
use App\Models\Staff\StaffAttendance;
use App\Services\AttendanceService;
use App\Services\Staff\StaffAttendanceService;
use Database\Seeders\AttendanceStatusesSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->owner = $this->world->school->actor;
    $this->actingAs($this->owner);
    (new AttendanceStatusesSeeder)->run();
});

it('lists past payroll on the staff profile', function () {
    $staff = $this->world->person('Hina Payroll', salary: 30000);
    $april = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);

    $this->postJson(route('staff.payroll.generate'), ['payroll_month_id' => $april->id, 'payroll_year' => 2026])
        ->assertSuccessful();

    $this->get(route('staff.people.show', $staff))
        ->assertInertia(fn ($page) => $page
            ->has('payrollHistory', 1)
            ->where('payrollHistory.0.period', 'April 2026'));
});

it('counts only past working days with no record as not marked', function () {
    $staff = $this->world->person('Sana Register');
    $service = app(StaffAttendanceService::class);

    $summary = $service->monthlySummary($staff, 1, 1, 2020);

    // January 2020: 31 days, 4 Sundays; default policy is Mon-Sat.
    expect($summary['unmarked_days'])->toBe(27)
        ->and($summary['expected_days'])->toBe(27);

    StaffAttendance::create([
        'staff_profile_id' => $staff->id,
        'attendance_date' => '2020-01-05',
        'attendance_status_id' => AttendanceStatus::where('code', 'P')->value('id'),
    ]);

    expect($service->monthlySummary($staff, 1, 1, 2020)['unmarked_days'])->toBe(27);
});

it('joins every holiday name that falls on one date', function () {
    foreach (['Eid Day', 'Founders Day'] as $title) {
        Holiday::create([
            'title' => $title,
            'start_date' => '2026-11-26',
            'end_date' => '2026-11-26',
            'is_national' => true,
            'is_attendance_allowed' => false,
        ]);
    }

    expect(app(AttendanceService::class)->holidayTitles('2026-11-26'))->toBe('Eid Day, Founders Day');
});

it('counts leave on the staff dashboard from attendance records too', function () {
    $staff = $this->world->person('Ali Dash');

    StaffAttendance::create([
        'staff_profile_id' => $staff->id,
        'attendance_date' => now()->toDateString(),
        'attendance_status_id' => AttendanceStatus::where('code', 'L')->value('id'),
    ]);

    $this->get('/staff/dashboard')->assertInertia(fn ($page) => $page
        ->where('summary.on_leave_today', 1)
        ->where('summary.present_today', 0));
});
