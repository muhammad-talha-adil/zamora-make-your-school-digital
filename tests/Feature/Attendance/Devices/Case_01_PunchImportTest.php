<?php

/**
 * Case 01 — taking the gate machine's punches in.
 *
 * Most schools of any size already have a thumb scanner at the gate, and its
 * log is typed into a register by hand or not used at all.
 *
 * What is built is everything on this side of the device driver: whatever
 * eventually reads the machine hands `import()` a list of punches. Two rules
 * run through it — a punch is never edited, and importing the same log twice
 * imports it once.
 */

use App\Models\AttendanceDevice;
use App\Models\AttendanceDeviceIdentity;
use App\Models\AttendanceDevicePunch;
use App\Services\Attendance\AttendancePunchImporter;
use Illuminate\Support\Carbon;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(2);
    $this->importer = app(AttendancePunchImporter::class);

    $this->device = AttendanceDevice::create([
        'campus_id' => $this->world->school->campus->id,
        'code' => 'GATE-1',
        'name' => 'Main gate scanner',
        'device_type' => 'biometric',
        'reads' => AttendanceDevice::READS_BOTH,
        'is_active' => true,
    ]);

    AttendanceDeviceIdentity::create([
        'student_id' => $this->students[0]->id,
        'identifier' => '1001',
        'identity_type' => 'biometric',
        'is_active' => true,
    ]);
});

it('stores a punch and matches it to a child', function () {
    $result = $this->importer->import($this->device, [
        ['identifier' => '1001', 'punched_at' => '2026-04-06 07:55:00', 'direction' => 'in'],
    ]);

    $punch = AttendanceDevicePunch::firstOrFail();

    expect($result['stored'])->toBe(1)
        ->and($punch->student_id)->toBe($this->students[0]->id)
        ->and($punch->status)->toBe(AttendanceDevicePunch::STATUS_PENDING);
});

it('keeps a punch it could not match', function () {
    $result = $this->importer->import($this->device, [
        ['identifier' => '9999', 'punched_at' => '2026-04-06 07:55:00', 'direction' => 'in'],
    ]);

    $punch = AttendanceDevicePunch::firstOrFail();

    // Evidence that a card is unregistered, or that somebody used one that is
    // not theirs — not an error to be cleared away.
    expect($result['unmatched'])->toBe(1)
        ->and($punch->status)->toBe(AttendanceDevicePunch::STATUS_UNMATCHED)
        ->and($punch->identifier)->toBe('9999')
        ->and($punch->note)->toContain('No active identity');
});

it('imports the same log twice as once', function () {
    $punches = [['identifier' => '1001', 'punched_at' => '2026-04-06 07:55:00', 'direction' => 'in']];

    $this->importer->import($this->device, $punches);
    $second = $this->importer->import($this->device, $punches);

    // Re-reading a machine's log is the normal way of recovering from a failure.
    expect($second['duplicates'])->toBe(1)
        ->and($second['stored'])->toBe(0)
        ->and(AttendanceDevicePunch::count())->toBe(1);
});

it('records when the machine was last heard from', function () {
    $this->importer->import($this->device, [
        ['identifier' => '1001', 'punched_at' => '2026-04-06 07:55:00'],
    ]);

    expect($this->device->fresh()->last_seen_at)->not->toBeNull();
});

it('takes the direction from a machine that only reads one way', function () {
    $this->device->update(['reads' => AttendanceDevice::READS_OUT]);

    $this->importer->import($this->device, [
        // The machine claims "in"; an exit machine's punches are departures
        // whatever the punch says.
        ['identifier' => '1001', 'punched_at' => '2026-04-06 13:30:00', 'direction' => 'in'],
    ]);

    expect(AttendanceDevicePunch::firstOrFail()->direction)->toBe('out');
});

it('ignores a punch with nothing to identify', function () {
    $result = $this->importer->import($this->device, [
        ['identifier' => '', 'punched_at' => '2026-04-06 07:55:00'],
        ['identifier' => '1001', 'punched_at' => ''],
    ]);

    expect($result['stored'])->toBe(0)
        ->and(AttendanceDevicePunch::count())->toBe(0);
});

it('ignores a card that has been deactivated', function () {
    AttendanceDeviceIdentity::query()->update(['is_active' => false]);

    $this->importer->import($this->device, [
        ['identifier' => '1001', 'punched_at' => '2026-04-06 07:55:00'],
    ]);

    // A lost card stops identifying its owner, but the punch is still recorded.
    expect(AttendanceDevicePunch::firstOrFail()->status)
        ->toBe(AttendanceDevicePunch::STATUS_UNMATCHED);
});

it('gives the first arrival and last departure of a day', function () {
    $this->importer->import($this->device, [
        ['identifier' => '1001', 'punched_at' => '2026-04-06 07:55:00', 'direction' => 'in'],
        ['identifier' => '1001', 'punched_at' => '2026-04-06 10:30:00', 'direction' => 'out'],
        ['identifier' => '1001', 'punched_at' => '2026-04-06 10:50:00', 'direction' => 'in'],
        ['identifier' => '1001', 'punched_at' => '2026-04-06 13:40:00', 'direction' => 'out'],
    ]);

    $summary = $this->importer->dailySummary(Carbon::parse('2026-04-06'));

    // A child crosses the gate several times; the register wants the two ends.
    expect($summary)->toHaveCount(1)
        ->and($summary[0]['first_in'])->toBe('07:55')
        ->and($summary[0]['last_out'])->toBe('13:40');
});

it('reads a single gate machine s last punch as the departure', function () {
    $this->device->update(['reads' => AttendanceDevice::READS_BOTH]);

    $this->importer->import($this->device, [
        ['identifier' => '1001', 'punched_at' => '2026-04-06 07:55:00'],
        ['identifier' => '1001', 'punched_at' => '2026-04-06 13:40:00'],
    ]);

    $summary = $this->importer->dailySummary(Carbon::parse('2026-04-06'));

    expect($summary[0]['first_in'])->toBe('07:55')
        ->and($summary[0]['last_out'])->toBe('13:40');
});

it('leaves unmatched punches out of the day summary', function () {
    $this->importer->import($this->device, [
        ['identifier' => '9999', 'punched_at' => '2026-04-06 07:55:00'],
    ]);

    expect($this->importer->dailySummary(Carbon::parse('2026-04-06')))->toHaveCount(0);
});

it('identifies a member of staff at the same gate', function () {
    $teacher = $this->world->staffUser('teacher', 'gate.teacher@test.local');

    AttendanceDeviceIdentity::create([
        'staff_profile_id' => $teacher->staffProfile->id,
        'identifier' => '2001',
        'identity_type' => 'biometric',
        'is_active' => true,
    ]);

    $this->importer->import($this->device, [
        ['identifier' => '2001', 'punched_at' => '2026-04-06 07:30:00'],
    ]);

    $punch = AttendanceDevicePunch::where('identifier', '2001')->firstOrFail();

    expect($punch->staff_profile_id)->toBe($teacher->staffProfile->id)
        ->and($punch->student_id)->toBeNull();
});
