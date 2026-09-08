<?php

/**
 * Case 01 — the class report shows the guardian the office should ring.
 *
 * The lookup matched a `type` column against `'primary'`. The pivot has
 * `is_primary`, a boolean, and no `type` at all — so the primary guardian was
 * never found and the report quietly showed whichever guardian happened to be
 * first. On a class report that is the number rung when a child is absent.
 */

use App\Models\Guardian;
use App\Models\StudentGuardian;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\Support\AttendanceWorld;

beforeEach(function () {
    $this->world = AttendanceWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->students = $this->world->enrol(1);
});

/** A guardian linked to the student, primary or not. */
function linkGuardian(AttendanceWorld $world, int $studentId, string $name, string $phone, bool $isPrimary): Guardian
{
    $user = User::create([
        'name' => $name,
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

/** The guardian_info the class report renders for the first student. */
function reportedGuardian(AttendanceWorld $world): string
{
    $info = '';

    test()->get(route('attendance.class-report', [
        'class_id' => $world->school->class->id,
        'month' => 4,
        'year' => 2026,
    ]))->assertInertia(function (AssertableInertia $page) use (&$info) {
        $info = $page->toArray()['props']['summary'][0]['guardian_info'];
    });

    return $info;
}

it('shows the primary guardian when there is one', function () {
    // Linked second, so "whichever comes first" would pick the other one.
    linkGuardian($this->world, $this->students[0]->id, 'Uncle Karim', '03001111111', false);
    linkGuardian($this->world, $this->students[0]->id, 'Father Ahmed', '03002222222', true);

    expect(reportedGuardian($this->world))->toContain('Father Ahmed')
        ->and(reportedGuardian($this->world))->toContain('03002222222');
});

it('falls back to any guardian when none is marked primary', function () {
    linkGuardian($this->world, $this->students[0]->id, 'Uncle Karim', '03001111111', false);

    expect(reportedGuardian($this->world))->toContain('Uncle Karim');
});

it('shows a dash when the child has no guardian on record', function () {
    expect(reportedGuardian($this->world))->toBe('-');
});

it('shows the guardian phone beside the name', function () {
    linkGuardian($this->world, $this->students[0]->id, 'Father Ahmed', '03009999999', true);

    expect(reportedGuardian($this->world))->toBe('Father Ahmed - 03009999999');
});
