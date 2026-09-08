<?php

/**
 * Case 01 — a family asking for leave.
 *
 * `student_leaves` already had a status and an approver, but nothing ever
 * created a leave except the office — so those columns described a decision
 * nobody had asked for. A guardian who telephones about a wedding relies on
 * somebody remembering to type it in, and when they forget, the child is marked
 * absent and the family is sent a message saying so.
 *
 * **There is no separate guardian portal.** A guardian signs in to the
 * student's portal, so the same endpoint serves the child, the family and the
 * office, and who submitted it is recorded rather than inferred.
 */

use App\Enums\LeaveStatus;
use App\Models\AttendanceStudent;
use App\Models\Guardian;
use App\Models\LeaveType;
use App\Models\StudentGuardian;
use App\Models\StudentLeave;
use App\Models\User;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->students = $this->world->enrol(1);
    $this->student = $this->students[0];

    $this->leaveType = LeaveType::create([
        'name' => 'Family Function',
        'description' => 'Wedding or similar',
        'is_active' => true,
    ]);

    $this->teacher = $this->world->staffUser('teacher', 'leave.teacher@test.local');
    $this->world->assignTeacher($this->teacher, $this->world->school->section->id);
});

/** A guardian who signs in to the child's portal. */
function guardianOf(AttendanceWorld $world, int $studentId): User
{
    $user = User::create([
        'name' => 'Father',
        'username' => 'g_'.uniqid(),
        'email' => uniqid().'@guardian.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $guardian = Guardian::create([
        'user_id' => $user->id,
        'cnic' => '35201-'.random_int(1000000, 9999999).'-1',
        'phone' => '03001234567',
        'occupation' => 'Business',
        'address' => 'Lahore',
    ]);

    StudentGuardian::create([
        'student_id' => $studentId,
        'guardian_id' => $guardian->id,
        'relation_id' => $world->school->fatherRelation->id,
        'is_primary' => true,
    ]);

    return $user;
}

/** The payload a family submits. */
function leavePayload(AttendanceWorld $world, array $overrides = []): array
{
    return array_merge([
        'leave_type_id' => test()->leaveType->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'description' => 'Family wedding out of town',
    ], $overrides);
}

it('lets a guardian apply from the student portal', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world))
        ->assertCreated();

    expect(StudentLeave::count())->toBe(1);
});

it('records who asked', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    $leave = StudentLeave::firstOrFail();

    // What tells the office afterwards whether the family asked or the office
    // entered it.
    expect($leave->applied_by)->toBe($guardian->id)
        ->and($leave->applied_at)->not->toBeNull();
});

it('starts as a request, not as leave', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    expect(StudentLeave::firstOrFail()->status)->toBe(LeaveStatus::PENDING);
});

it('keeps one family out of another child s record', function () {
    $stranger = guardianOf($this->world, $this->world->enrol(1)[1]->id);

    $this->actingAs($stranger)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world))
        ->assertForbidden();
});

it('refuses a second application for the same days', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world))
        ->assertCreated();

    // A family asking twice is usually one that did not see the first answer.
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world))
        ->assertStatus(422);
});

it('requires a reason', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world, [
            'description' => '',
        ]))
        ->assertStatus(422);
});

it('refuses an end date before the start', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world, [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]))
        ->assertStatus(422);
});

it('refuses more than a month at a time', function () {
    $guardian = guardianOf($this->world, $this->student->id);

    // Longer than that is a child leaving the school for a while, which is a
    // different conversation and a different record.
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world, [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
        ]))
        ->assertStatus(422);
});

it('lets the class teacher approve it', function () {
    $guardian = guardianOf($this->world, $this->student->id);
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    $leave = StudentLeave::firstOrFail();

    $this->actingAs($this->teacher)
        ->postJson(route('student-leaves.approve', $leave), ['note' => 'Granted'])
        ->assertSuccessful();

    expect($leave->fresh()->status)->toBe(LeaveStatus::APPROVED)
        ->and($leave->fresh()->approved_by)->toBe($this->teacher->id);
});

it('stops a teacher deciding on another class s child', function () {
    $other = $this->world->staffUser('teacher', 'other.teacher@test.local');
    $this->world->assignTeacher($other, $this->world->school->otherSection->id);

    $guardian = guardianOf($this->world, $this->student->id);
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    $this->actingAs($other)
        ->postJson(route('student-leaves.approve', StudentLeave::firstOrFail()))
        ->assertForbidden();
});

it('requires a reason to refuse', function () {
    $guardian = guardianOf($this->world, $this->student->id);
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    // The office cannot answer the family a month later without one.
    $this->actingAs($this->teacher)
        ->postJson(route('student-leaves.reject', StudentLeave::firstOrFail()), [])
        ->assertStatus(422);
});

it('records the reason it was refused', function () {
    $guardian = guardianOf($this->world, $this->student->id);
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    $this->actingAs($this->teacher)
        ->postJson(route('student-leaves.reject', StudentLeave::firstOrFail()), [
            'reason' => 'Examinations are running those days',
        ])->assertSuccessful();

    $leave = StudentLeave::firstOrFail();

    expect($leave->status)->toBe(LeaveStatus::REJECTED)
        ->and($leave->decision_note)->toContain('Examinations');
});

it('stops the register calling an approved leave absence', function () {
    // Within the window a family may apply for — the rule above refuses
    // anything more than a month in the past.
    $away = now()->addDays(3)->toDateString();

    $guardian = guardianOf($this->world, $this->student->id);

    $this->actingAs($guardian)->postJson(
        route('student-leaves.store', $this->student),
        leavePayload($this->world, ['start_date' => $away, 'end_date' => $away])
    )->assertCreated();

    $this->actingAs($this->teacher)
        ->postJson(route('student-leaves.approve', StudentLeave::firstOrFail()))
        ->assertSuccessful();

    // The register links the approved leave by itself; nothing more to remember.
    $this->actingAs($this->world->school->actor)
        ->post(route('attendance.store'), $this->world->payload('L', $away))
        ->assertRedirect();

    $row = AttendanceStudent::where('student_id', $this->student->id)->firstOrFail();

    expect($row->student_leave_id)->toBe(StudentLeave::firstOrFail()->id);
});

it('lists what the school still has to decide', function () {
    $guardian = guardianOf($this->world, $this->student->id);
    $this->actingAs($guardian)
        ->postJson(route('student-leaves.store', $this->student), leavePayload($this->world));

    $this->actingAs($this->teacher)
        ->getJson(route('student-leaves.pending'))
        ->assertSuccessful()
        ->assertJsonCount(1, 'leaves');
});
