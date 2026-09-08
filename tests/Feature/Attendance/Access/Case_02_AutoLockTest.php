<?php

/**
 * Case 02 — registers that close themselves.
 *
 * The lock worked but had to be operated by hand, so in practice registers
 * stayed editable for ever — and a register that can still be changed a year
 * later is not a record of anything.
 *
 * Each campus sets its own window, and zero — the default — closes nothing, so
 * a school that has not asked for this sees no change at all.
 */

use App\Models\Attendance;
use App\Models\AttendancePolicy;
use Illuminate\Support\Carbon;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
});

/** Sets how long a campus gives itself to correct a register. */
function lockWindow(AttendanceWorld $world, int $days): AttendancePolicy
{
    return AttendancePolicy::updateOrCreate(
        [
            'campus_id' => $world->school->campus->id,
            'session_id' => $world->school->session->id,
        ],
        ['working_days' => [1, 2, 3, 4, 5, 6], 'lock_after_days' => $days, 'is_active' => true]
    );
}

it('closes nothing when no window is set', function () {
    $this->world->register('2026-01-01');

    $this->artisan('attendance:lock-settled')->assertSuccessful();

    expect(Attendance::firstOrFail()->is_locked)->toBeFalse();
});

it('closes a register once the window has passed', function () {
    lockWindow($this->world, 7);
    $this->world->register('2026-04-06');

    Carbon::setTestNow('2026-04-20');
    $this->artisan('attendance:lock-settled')->assertSuccessful();
    Carbon::setTestNow();

    expect(Attendance::firstOrFail()->is_locked)->toBeTrue();
});

it('leaves a register inside the window alone', function () {
    lockWindow($this->world, 7);
    $this->world->register('2026-04-06');

    // Three days later: the school still has time to correct it.
    Carbon::setTestNow('2026-04-09');
    $this->artisan('attendance:lock-settled')->assertSuccessful();
    Carbon::setTestNow();

    expect(Attendance::firstOrFail()->is_locked)->toBeFalse();
});

it('closes on the day the window runs out, not after', function () {
    lockWindow($this->world, 7);
    $this->world->register('2026-04-06');

    Carbon::setTestNow('2026-04-13');
    $this->artisan('attendance:lock-settled')->assertSuccessful();
    Carbon::setTestNow();

    expect(Attendance::firstOrFail()->is_locked)->toBeTrue();
});

it('shows what it would close without closing it', function () {
    lockWindow($this->world, 7);
    $this->world->register('2026-04-06');

    Carbon::setTestNow('2026-04-20');
    $this->artisan('attendance:lock-settled --dry-run')->assertSuccessful();
    Carbon::setTestNow();

    expect(Attendance::firstOrFail()->is_locked)->toBeFalse();
});

it('leaves an already closed register alone', function () {
    lockWindow($this->world, 7);
    $register = $this->world->register('2026-04-06');
    $register->lock();

    Carbon::setTestNow('2026-04-20');
    $this->artisan('attendance:lock-settled')->assertSuccessful();
    Carbon::setTestNow();

    expect(Attendance::firstOrFail()->is_locked)->toBeTrue();
});

it('stops a teacher changing a register that closed itself', function () {
    lockWindow($this->world, 7);
    $this->world->register('2026-04-06');

    Carbon::setTestNow('2026-04-20');
    $this->artisan('attendance:lock-settled');
    Carbon::setTestNow();

    $teacher = $this->world->staffUser('teacher', 'late.teacher@test.local');
    $this->world->assignTeacher($teacher, $this->world->school->section->id);

    $this->actingAs($teacher)
        ->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'))
        ->assertForbidden();
});

it('limits itself to one campus when asked', function () {
    lockWindow($this->world, 7);
    $this->world->register('2026-04-06');

    Carbon::setTestNow('2026-04-20');
    $this->artisan('attendance:lock-settled --campus='.$this->world->school->otherCampus->id)
        ->assertSuccessful();
    Carbon::setTestNow();

    expect(Attendance::firstOrFail()->is_locked)->toBeFalse();
});
