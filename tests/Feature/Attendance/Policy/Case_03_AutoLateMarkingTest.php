<?php

/**
 * Case 03 — the clock deciding who was late.
 *
 * The timings knew the answer and nothing asked them: a teacher could record a
 * check-in of 08:45 against a deadline of 08:15 and the child stayed marked
 * present. This is the write path that asks.
 *
 * What it does is deliberately narrow — it upgrades *present* to *late* and
 * nothing else. A teacher who marked late meant it; a child may reach the gate
 * on time and the classroom ten minutes later, and the teacher can see that
 * where a clock cannot.
 */

use App\Models\AttendanceStudent;
use App\Models\AttendanceSummary;
use App\Models\AttendanceTiming;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
    $this->world->policy([1, 2, 3, 4, 5, 6]);
});

/** A clock for the campus. */
function schoolClock(AttendanceWorld $world, string $lateAfter = '08:15', string $from = '2026-04-01', string $to = '2027-03-31'): AttendanceTiming
{
    return AttendanceTiming::create([
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
        'name' => 'Regular',
        'starts_on' => $from,
        'ends_on' => $to,
        'day_starts_at' => '08:00',
        'late_after' => $lateAfter,
        'is_active' => true,
    ]);
}

/** Marks the class with a status and a check-in time. */
function markWithArrival(AttendanceWorld $world, string $code, ?string $checkIn, string $date = '2026-04-06')
{
    $payload = $world->payload($code, $date);
    $payload['attendances'][0]['check_in'] = $checkIn;

    return test()->post(route('attendance.store'), $payload);
}

/** The code the register ended up holding. */
function recordedCode(AttendanceWorld $world): ?string
{
    return AttendanceStudent::with('attendanceStatus')->first()?->attendanceStatus?->code;
}

it('marks a late arrival late', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'P', '08:45')->assertRedirect();

    expect(recordedCode($this->world))->toBe('LT');
});

it('leaves an arrival before the deadline present', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'P', '08:05')->assertRedirect();

    expect(recordedCode($this->world))->toBe('P');
});

it('treats the deadline itself as on time', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'P', '08:15')->assertRedirect();

    expect(recordedCode($this->world))->toBe('P');
});

it('does nothing when the campus has set no clock', function () {
    // Every campus, until one is configured.
    markWithArrival($this->world, 'P', '09:30')->assertRedirect();

    expect(recordedCode($this->world))->toBe('P');
});

it('does nothing when no arrival time was recorded', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'P', null)->assertRedirect();

    expect(recordedCode($this->world))->toBe('P');
});

it('leaves an absence alone', function () {
    schoolClock($this->world);

    // A statement about the whole day; an arrival time cannot contradict it.
    markWithArrival($this->world, 'A', '08:45')->assertRedirect();

    expect(recordedCode($this->world))->toBe('A');
});

it('leaves an approved leave alone', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'L', '08:45')->assertRedirect();

    expect(recordedCode($this->world))->toBe('L');
});

it('leaves a half day alone', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'HD', '08:45')->assertRedirect();

    expect(recordedCode($this->world))->toBe('HD');
});

it('does not turn a teacher s late back into present', function () {
    schoolClock($this->world);

    // The teacher may know the child reached the gate on time and the classroom
    // ten minutes later.
    markWithArrival($this->world, 'LT', '08:00')->assertRedirect();

    expect(recordedCode($this->world))->toBe('LT');
});

it('uses the Ramzan clock in Ramzan', function () {
    schoolClock($this->world, '08:15', '2026-04-01', '2027-01-31');

    AttendanceTiming::create([
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'name' => 'Ramzan',
        'starts_on' => '2027-02-01',
        'ends_on' => '2027-03-02',
        'day_starts_at' => '07:00',
        'late_after' => '07:10',
        'is_active' => true,
    ]);

    // The same arrival: on time in April, late in Ramzan. The whole reason the
    // timings exist.
    markWithArrival($this->world, 'P', '08:00', '2026-04-06')->assertRedirect();
    expect(recordedCode($this->world))->toBe('P');

    markWithArrival($this->world, 'P', '08:00', '2027-02-10')->assertRedirect();

    $ramzanRow = AttendanceStudent::with(['attendanceStatus', 'attendance'])
        ->get()
        ->first(fn ($row) => $row->attendance->attendance_date->format('Y-m') === '2027-02');

    expect($ramzanRow->attendanceStatus->code)->toBe('LT');
});

it('marks late when a register is corrected', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'P', '08:00')->assertRedirect();
    expect(recordedCode($this->world))->toBe('P');

    // The teacher goes back and records the real arrival time.
    markWithArrival($this->world, 'P', '08:50')->assertRedirect();

    expect(recordedCode($this->world))->toBe('LT');
});

it('marks late through the edit screen too', function () {
    schoolClock($this->world);
    markWithArrival($this->world, 'P', '08:00');

    $row = AttendanceStudent::firstOrFail();

    $this->put(route('attendance.update', $row->attendance_id), [
        'attendances' => [[
            'id' => $row->id,
            'student_id' => $this->students[0]->id,
            'attendance_status_id' => $this->world->status('P')->id,
            'check_in' => '09:20',
        ]],
    ])->assertRedirect(route('attendance.index'));

    expect(recordedCode($this->world))->toBe('LT');
});

it('counts an auto-marked late in the summary', function () {
    schoolClock($this->world);

    markWithArrival($this->world, 'P', '08:45')->assertRedirect();

    $summary = AttendanceSummary::firstOrFail();

    // Late is still a full day present; the lateness is recorded to be chased.
    expect($summary->late_count)->toBe(1)
        ->and((float) $summary->present_equivalent)->toBe(1.0);
});
