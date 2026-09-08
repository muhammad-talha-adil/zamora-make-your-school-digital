<?php

/**
 * Case 01 — telling a guardian their child is not in school.
 *
 * The single most expected thing an attendance module does here, and everything
 * it needed was already on hand: the guardian's phone, the absence, and the
 * moment the register was taken.
 *
 * The row survives sending, because it is the answer to "nobody told me".
 */

use App\Models\AttendanceAbsenceAlert;
use App\Models\Guardian;
use App\Models\StudentGuardian;
use App\Models\User;
use App\Services\Attendance\AbsenceAlertService;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(2);
    $this->world->policy([1, 2, 3, 4, 5, 6], alerts: true);
});

/** A guardian on the child's record. */
function guardianFor(AttendanceWorld $world, int $studentId, string $phone, bool $isPrimary = true): Guardian
{
    $user = User::create([
        'name' => 'Father '.$studentId,
        'username' => 'g_'.uniqid(),
        'email' => uniqid().'@guardian.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $guardian = Guardian::create([
        'user_id' => $user->id,
        'cnic' => '35201-'.random_int(1000000, 9999999).'-1',
        'phone' => $phone,
        'occupation' => 'Business',
        'address' => 'Lahore',
    ]);

    StudentGuardian::create([
        'student_id' => $studentId,
        'guardian_id' => $guardian->id,
        'relation_id' => $world->school->fatherRelation->id,
        'is_primary' => $isPrimary,
    ]);

    return $guardian;
}

it('records an alert for an absent child', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(AttendanceAbsenceAlert::where('student_id', $this->students[0]->id)->exists())->toBeTrue();
});

it('records nothing for a child who was present', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('P', '2026-04-06'));

    expect(AttendanceAbsenceAlert::count())->toBe(0);
});

it('records nothing for a child on approved leave', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    // The family already knows; they asked for the leave.
    $this->post(route('attendance.store'), $this->world->payload('L', '2026-04-06'));

    expect(AttendanceAbsenceAlert::count())->toBe(0);
});

it('records nothing for a late arrival', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('LT', '2026-04-06'));

    expect(AttendanceAbsenceAlert::count())->toBe(0);
});

it('does nothing until the campus switches alerts on', function () {
    $this->world->policy([1, 2, 3, 4, 5, 6], alerts: false);
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(AttendanceAbsenceAlert::count())->toBe(0);
});

it('addresses the primary guardian', function () {
    guardianFor($this->world, $this->students[0]->id, '03009999999', isPrimary: false);
    guardianFor($this->world, $this->students[0]->id, '03001234567', isPrimary: true);

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(AttendanceAbsenceAlert::where('student_id', $this->students[0]->id)->value('recipient_phone'))
        ->toBe('03001234567');
});

it('names the child and the date in the message', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(AttendanceAbsenceAlert::where('student_id', $this->students[0]->id)->value('message'))
        ->toContain('06 Apr 2026');
});

it('records one alert per child per day however often the register is saved', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(AttendanceAbsenceAlert::where('student_id', $this->students[0]->id)->count())->toBe(1);
});

it('marks a child with no number on record as skipped, not failed', function () {
    // Not a failure to send: a family whose details need filling in, and the
    // office should be able to see it as exactly that.
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    $alert = AttendanceAbsenceAlert::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($alert->status)->toBe(AttendanceAbsenceAlert::STATUS_SKIPPED)
        ->and($alert->failure_reason)->toContain('phone');
});

it('sends the pending alerts and keeps the record', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    $result = app(AbsenceAlertService::class)->dispatchPending();

    $alert = AttendanceAbsenceAlert::where('student_id', $this->students[0]->id)->firstOrFail();

    expect($result['sent'])->toBe(1)
        ->and($alert->status)->toBe(AttendanceAbsenceAlert::STATUS_SENT)
        ->and($alert->sent_at)->not->toBeNull()
        // The row is the answer to "nobody told me", so it stays.
        ->and($alert->message)->not->toBeEmpty();
});

it('does not send a skipped alert', function () {
    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    $result = app(AbsenceAlertService::class)->dispatchPending();

    expect($result['sent'])->toBe(0)
        ->and($result['failed'])->toBe(0);
});

it('records an alert for each absent child', function () {
    guardianFor($this->world, $this->students[0]->id, '03001111111');
    guardianFor($this->world, $this->students[1]->id, '03002222222');

    $this->post(route('attendance.store'), $this->world->payload('A', '2026-04-06'));

    expect(AttendanceAbsenceAlert::count())->toBe(2);
});
