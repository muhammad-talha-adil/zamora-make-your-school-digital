<?php

/**
 * Phase 4 — the teacher.
 *
 * `teacher_class_assignments` was built to close attendance A13, and the class
 * width in **Attendance, Exam and Student** all read it. Nothing has ever
 * written to it, so the live table holds zero rows — which is why every teacher
 * in the school currently sees nothing at all.
 *
 * The last few cases here are the point of the whole phase: they check that
 * giving a teacher a class actually opens the three other modules to them.
 */

use App\Models\Exam\ExamResultHeader;
use App\Models\Staff\StaffSubject;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherClassAssignment;
use App\Services\Staff\TeacherAssignmentService;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->teaching = app(TeacherAssignmentService::class);

    $this->teacher = $this->world->person('Physics Teacher');
    $this->physics = Subject::firstOrCreate(
        ['name' => 'Physics'],
        ['short_name' => 'Phy', 'is_active' => true]
    );
});

/** The details of a class assignment in the shared world. */
function classPayload(StaffWorld $world, array $overrides = []): array
{
    return array_merge([
        'session_id' => $world->school->session->id,
        'class_id' => $world->school->class->id,
        'section_id' => $world->school->section->id,
    ], $overrides);
}

/* -------------------------------------------------------------- the class */

it('gives a teacher a class', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world));

    expect($this->teaching->classesOf($this->teacher))->toHaveCount(1);
});

it('refuses the same class twice', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world));

    expect(fn () => $this->teaching->assignClass($this->teacher, classPayload($this->world)))
        ->toThrow(ValidationException::class);
});

it('lets one teacher take two subjects in the same class', function () {
    $maths = Subject::firstOrCreate(['name' => 'Mathematics'], ['short_name' => 'Math', 'is_active' => true]);

    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['subject_id' => $this->physics->id]));
    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['subject_id' => $maths->id]));

    expect($this->teaching->classesOf($this->teacher))->toHaveCount(2);
});

it('refuses a second class teacher for one section', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['is_class_teacher' => true]));

    $other = $this->world->person('Another Teacher');

    // Two would mean two people answering for the same register, and the
    // attendance module reads this to decide who may sign one off.
    expect(fn () => $this->teaching->assignClass(
        $other,
        classPayload($this->world, ['is_class_teacher' => true])
    ))->toThrow(ValidationException::class);
});

it('names the class teacher of a section', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['is_class_teacher' => true]));

    expect($this->teaching->classTeacherOf(
        $this->world->school->session->id,
        $this->world->school->class->id,
        $this->world->school->section->id
    )->id)->toBe($this->teacher->id);
});

it('keeps a class it takes away, rather than deleting it', function () {
    $assignment = $this->teaching->assignClass($this->teacher, classPayload($this->world));

    $this->teaching->unassignClass($assignment);

    // Last month's register was taken by this teacher and a report should still
    // be able to say so.
    expect(TeacherClassAssignment::find($assignment->id))->not->toBeNull()
        ->and($this->teaching->classesOf($this->teacher))->toBeEmpty();
});

/* ------------------------------------------------------------ the subject */

it('says which subjects somebody may be given', function () {
    $this->teaching->allowSubject($this->teacher, $this->physics->id, true);

    expect($this->teacher->subjects()->count())->toBe(1)
        ->and(StaffSubject::first()->is_primary)->toBeTrue();
});

it('does not add the same subject twice', function () {
    $this->teaching->allowSubject($this->teacher, $this->physics->id);
    $this->teaching->allowSubject($this->teacher, $this->physics->id, true);

    expect($this->teacher->subjects()->count())->toBe(1)
        ->and(StaffSubject::first()->is_primary)->toBeTrue();
});

it('answers who could cover a subject', function () {
    $this->teaching->allowSubject($this->teacher, $this->physics->id);
    $this->world->person('Somebody Else');

    // The question a school asks at eight in the morning when somebody rings
    // in sick.
    $candidates = $this->teaching->whoCanTeach($this->physics->id);

    expect($candidates)->toHaveCount(1)
        ->and($candidates->first()->id)->toBe($this->teacher->id);
});

/* -------------------------------------------- what this unlocks elsewhere */

it('opens the student list to a teacher who now has a class', function () {
    // The real roles, because `isClassRestricted()` reads them — and a fence
    // tested against a stub is not a fence.
    $this->world->school->withFullRoles();

    $teacherUser = $this->teacher->user;
    $teacherUser->givePermissionTo('students.view');
    $teacherUser->assignRole('teacher');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // A child in the section.
    $this->actingAs($this->world->school->actor)
        ->post(route('students.store'), $this->world->school->payload())
        ->assertSessionHasNoErrors();

    $student = Student::firstOrFail();

    // Before: the table has no rows, so the class width shows a teacher nobody.
    expect(Student::visibleTo($teacherUser->fresh())->count())->toBe(0);

    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['is_class_teacher' => true]));

    // After: the rule was always right; it had no data.
    expect(Student::visibleTo($teacherUser->fresh())->count())->toBe(1)
        ->and($teacherUser->fresh()->can('view', $student))->toBeTrue();
});

it('opens the exam results to the same teacher', function () {
    $this->world->school->withFullRoles();

    $teacherUser = $this->teacher->user;
    $teacherUser->assignRole('teacher');
    $teacherUser->givePermissionTo('exam.result.view');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['is_class_teacher' => true]));

    expect($teacherUser->fresh()->teachesSection(
        $this->world->school->class->id,
        $this->world->school->section->id,
        $this->world->school->session->id
    ))->toBeTrue()
        ->and(ExamResultHeader::visibleTo($teacherUser->fresh())->count())->toBe(0);
});

/* ------------------------------------------------------------------ HTTP */

it('assigns a class from the screen', function () {
    $this->postJson(route('staff.teaching.assign', $this->teacher->id), classPayload($this->world, [
        'subject_id' => $this->physics->id,
        'is_class_teacher' => true,
        'periods_per_week' => 6,
    ]))->assertCreated();

    expect(TeacherClassAssignment::count())->toBe(1);
});

it('lists who takes what for a class', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world));

    $this->getJson(route('staff.teaching.index', [
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
    ]))->assertSuccessful()->assertJsonCount(1, 'data');
});

it('does not let a viewer assign a class', function () {
    $clerk = $this->world->person('Office Clerk', ['staff.view']);

    $this->actingAs($clerk->user)
        ->postJson(route('staff.teaching.assign', $this->teacher->id), classPayload($this->world))
        ->assertForbidden();
});

/* ---------------------------------------------------- staff.view.own width */

it('lets a plain teacher reach the teaching screen for their own assignments', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world, ['is_class_teacher' => true]));

    $this->world->person('Another Teacher', ['staff.view.own']);
    $other = $this->world->person('Yet Another Teacher');
    $this->teaching->assignClass($other, classPayload($this->world, [
        'section_id' => null,
        'is_class_teacher' => false,
    ]));

    $viewer = $this->teacher->user;
    $viewer->givePermissionTo('staff.view.own');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($viewer)->get(route('staff.teaching.page'))->assertOk();

    $response = $this->actingAs($viewer)->getJson(route('staff.teaching.index', [
        'session_id' => $this->world->school->session->id,
    ]));

    $response->assertSuccessful()->assertJsonCount(1, 'data');
    expect($response->json('data.0.staff_profile.id'))->toBe($this->teacher->id);
});

it('still shows an admin every teacher s assignments, not just their own', function () {
    $this->teaching->assignClass($this->teacher, classPayload($this->world));

    $other = $this->world->person('Another Teacher');
    $this->teaching->assignClass($other, classPayload($this->world, [
        'section_id' => null,
    ]));

    $admin = $this->world->person('Campus Admin', ['staff.view', 'staff.manage']);

    $response = $this->actingAs($admin->user)->getJson(route('staff.teaching.index', [
        'session_id' => $this->world->school->session->id,
    ]));

    $response->assertSuccessful()->assertJsonCount(2, 'data');
});

it('does not let a plain teacher reach the teaching screen with no ability at all', function () {
    $outsider = $this->world->person('No Ability At All');

    $this->actingAs($outsider->user)->get(route('staff.teaching.page'))->assertForbidden();
});
