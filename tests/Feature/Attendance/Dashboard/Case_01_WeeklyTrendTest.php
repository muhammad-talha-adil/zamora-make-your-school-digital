<?php

/**
 * The attendance dashboard showed only today's number with no way to see
 * whether it was trending up or down over the week. `dashboard()` now also
 * returns a 7-day `weeklyTrend`, built from the same `calculateDashboardStats`
 * routine as the today/yesterday cards, campus-scoped the same way the rest
 * of the dashboard already is.
 */

use App\Models\Attendance;
use App\Models\AttendanceStudent;
use App\Models\Campus;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(2);
});

it('reports today\'s attendance percentage in the trend for the viewer\'s own campus', function () {
    $today = now()->toDateString();

    $register = Attendance::create([
        'attendance_date' => $today,
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'taken_by' => $this->world->school->actor->id,
        'is_locked' => false,
    ]);

    $this->world->mark($register, $this->students[0], 'P');
    $this->world->mark($register, $this->students[1], 'A');

    $response = $this->actingAs($this->world->school->actor)->get(route('attendance.dashboard'));

    $response->assertOk();

    $trend = $response->viewData('page')['props']['weeklyTrend'];

    expect($trend)->toHaveCount(7);

    $todayEntry = collect($trend)->firstWhere('date', $today);

    expect($todayEntry)->not->toBeNull()
        ->and($todayEntry['attendance_percentage'])->toBe(50.0);
});

it('excludes another campus\'s attendance from the trend', function () {
    $today = now()->toDateString();

    $otherCampus = Campus::create([
        'name' => 'Other Campus',
        'campus_type_id' => $this->world->school->campus->campus_type_id,
        'is_active' => true,
    ]);

    $viewer = $this->world->staffUser('campus_admin', 'trend.viewer@school.test', $this->world->school->campus->id);

    // Attendance recorded at a campus the viewer does not belong to.
    $foreignRegister = Attendance::create([
        'attendance_date' => $today,
        'campus_id' => $otherCampus->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'taken_by' => $this->world->school->actor->id,
        'is_locked' => false,
    ]);

    AttendanceStudent::create([
        'attendance_id' => $foreignRegister->id,
        'student_id' => $this->students[0]->id,
        'attendance_status_id' => $this->world->status('P')->id,
    ]);

    $response = $this->actingAs($viewer)->get(route('attendance.dashboard'));

    $response->assertOk();

    $todayEntry = collect($response->viewData('page')['props']['weeklyTrend'])->firstWhere('date', $today);

    expect($todayEntry['attendance_percentage'])->toBe(0);
});
