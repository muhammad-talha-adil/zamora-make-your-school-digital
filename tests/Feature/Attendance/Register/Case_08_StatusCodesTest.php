<?php

/**
 * Case 08 — the status codes, stated once.
 *
 * The four codes were declared as string constants on two models at once and
 * compared as bare strings in a dozen places. What matters most about the new
 * enum is what it deliberately is not: a cast on `attendance_statuses.code`.
 * That column stays a plain string so a school can add its own status, and
 * everything that counts reads the weight off the row rather than testing the
 * code.
 */

use App\Enums\AttendanceStatusCode;
use App\Models\AttendanceStatus;
use App\Models\AttendanceSummary;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
});

it('names every seeded status', function () {
    foreach (AttendanceStatusCode::cases() as $code) {
        expect(AttendanceStatus::where('code', $code->value)->exists())->toBeTrue();
    }
});

it('matches a status row to its code', function () {
    expect($this->world->status('P')->hasCode(AttendanceStatusCode::PRESENT))->toBeTrue()
        ->and($this->world->status('P')->hasCode(AttendanceStatusCode::ABSENT))->toBeFalse();
});

it('counts a late arrival as a full day', function () {
    expect(AttendanceStatusCode::LATE->defaultWeight())->toBe(1.0)
        ->and(AttendanceStatusCode::HALF_DAY->defaultWeight())->toBe(0.5)
        ->and(AttendanceStatusCode::ABSENT->defaultWeight())->toBe(0.0);
});

it('alerts a guardian only about an absence', function () {
    expect(AttendanceStatusCode::ABSENT->warrantsAlert())->toBeTrue()
        ->and(AttendanceStatusCode::LEAVE->warrantsAlert())->toBeFalse()
        ->and(AttendanceStatusCode::LATE->warrantsAlert())->toBeFalse();
});

it('lets a school add a status of its own', function () {
    // The point of not casting the column: this row would throw a ValueError
    // the moment it was read if `code` were an enum.
    $shortLeave = AttendanceStatus::create([
        'name' => 'Short Leave',
        'code' => 'SL',
        'description' => 'Left for an hour and returned',
        'weight' => 0.5,
        'counts_as_expected' => true,
        'is_active' => true,
    ]);

    expect($shortLeave->fresh()->code)->toBe('SL')
        ->and($shortLeave->knownCode())->toBeNull();
});

it('counts a school\'s own status by its weight', function () {
    $shortLeave = AttendanceStatus::create([
        'name' => 'Short Leave',
        'code' => 'SL',
        'weight' => 0.5,
        'counts_as_expected' => true,
        'is_active' => true,
    ]);

    $this->post(route('attendance.store'), [
        'attendance_date' => '2026-04-06',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'attendances' => [[
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $shortLeave->id,
        ]],
    ])->assertRedirect(route('attendance.index'));

    $summary = AttendanceSummary::where('student_id', $this->students[0]->id)->firstOrFail();

    // No column of its own, but the weighted total is right — which is what
    // the percentage is built from.
    expect((float) $summary->present_equivalent)->toBe(0.5)
        ->and($summary->total_days)->toBe(1);
});

it('leaves a retired status out of the active list', function () {
    $this->world->status('HD')->update(['is_active' => false]);

    expect(AttendanceStatus::active()->pluck('code')->all())->not->toContain('HD');
});

it('orders the statuses the way a register reads', function () {
    expect(AttendanceStatus::ordered()->pluck('code')->take(3)->all())
        ->toBe(['P', 'A', 'L']);
});
