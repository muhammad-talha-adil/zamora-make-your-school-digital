<?php

/**
 * Phase 3 — the person, and their jobs.
 *
 * `staff_profiles.designation_id` is singular and a school does not employ
 * people that way. And `hire_date` was the whole employment history: a person
 * who left and came back had their joining date overwritten, and the year they
 * were away vanished.
 */

use App\Models\Staff\StaffAssignment;
use App\Models\Staff\StaffEmploymentPeriod;
use App\Services\Staff\StaffAssignmentService;
use App\Services\Staff\StaffEmploymentService;
use Illuminate\Validation\ValidationException;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->jobs = app(StaffAssignmentService::class);
    $this->employment = app(StaffEmploymentService::class);
    $this->person = $this->world->person('Bilal Ahmed');
});

/* ------------------------------------------------------------------- jobs */

it('makes the first job the primary one, whatever the form said', function () {
    $job = $this->jobs->give($this->person, [
        'designation_id' => $this->world->driverPost->id,
        'is_primary' => false,
    ]);

    // A person's only job is the job they are.
    expect($job->is_primary)->toBeTrue();
});

it('adds a second job without disturbing the first', function () {
    $this->jobs->give($this->person, ['designation_id' => $this->world->driverPost->id]);
    $gardening = $this->jobs->give($this->person, ['designation_id' => $this->world->teacherPost->id]);

    // The man who drives the van also does the gardening. One employee.
    expect($this->jobs->currentJobsOf($this->person))->toHaveCount(2)
        ->and($gardening->is_primary)->toBeFalse();
});

it('refuses the same job twice', function () {
    $this->jobs->give($this->person, ['designation_id' => $this->world->driverPost->id]);

    expect(fn () => $this->jobs->give($this->person, [
        'designation_id' => $this->world->driverPost->id,
    ]))->toThrow(ValidationException::class);
});

it('moves the primary without ending the old job', function () {
    $driving = $this->jobs->give($this->person, ['designation_id' => $this->world->driverPost->id]);
    $teaching = $this->jobs->give($this->person, ['designation_id' => $this->world->teacherPost->id]);

    $this->jobs->makePrimary($teaching);

    // Promoted, but he still drives the van.
    expect($teaching->fresh()->is_primary)->toBeTrue()
        ->and($driving->fresh()->is_primary)->toBeFalse()
        ->and($driving->fresh()->ended_on)->toBeNull();
});

it('keeps the profile pointing at the primary job', function () {
    $this->jobs->give($this->person, ['designation_id' => $this->world->driverPost->id]);
    $teaching = $this->jobs->give($this->person, ['designation_id' => $this->world->teacherPost->id]);

    $this->jobs->makePrimary($teaching);

    // A dozen screens read `designation_id` directly; it stays true.
    expect($this->person->fresh()->designation_id)->toBe($this->world->teacherPost->id);
});

it('hands the primary on when the primary job ends', function () {
    $driving = $this->jobs->give($this->person, ['designation_id' => $this->world->driverPost->id]);
    $this->jobs->give($this->person, ['designation_id' => $this->world->teacherPost->id]);

    $this->jobs->end($driving);

    // "What does this person do" must still have an answer.
    expect($this->person->fresh()->primaryAssignment->designation_id)
        ->toBe($this->world->teacherPost->id);
});

it('refuses a job that ends before it began', function () {
    $job = $this->jobs->give($this->person, [
        'designation_id' => $this->world->driverPost->id,
        'started_on' => '2026-04-01',
    ]);

    expect(fn () => $this->jobs->end($job, '2026-01-01'))->toThrow(ValidationException::class);
});

it('brings an existing record into the new shape', function () {
    // Every staff record has a designation and no assignment at all, so the new
    // screens would otherwise show people doing nothing.
    $job = $this->jobs->backfill($this->person);

    expect($job)->not->toBeNull()
        ->and($job->designation_id)->toBe($this->person->designation_id)
        ->and($job->is_primary)->toBeTrue();
});

it('does not backfill twice', function () {
    $this->jobs->backfill($this->person);
    $this->jobs->backfill($this->person);

    expect(StaffAssignment::where('staff_profile_id', $this->person->id)->count())->toBe(1);
});

/* ------------------------------------------------------------- employment */

it('opens a spell when somebody joins', function () {
    $period = $this->employment->join($this->person);

    expect($period->joined_on->toDateString())->toBe('2026-04-01')
        ->and($period->left_on)->toBeNull();
});

it('records leaving on the day it happened', function () {
    $this->employment->join($this->person);

    $this->employment->leave($this->person, '2026-06-30', StaffEmploymentPeriod::REASON_RESIGNED);

    $period = StaffEmploymentPeriod::where('staff_profile_id', $this->person->id)->firstOrFail();

    // Not today. June, because that is when they went — and the payroll for
    // that month reads it.
    expect($period->left_on->toDateString())->toBe('2026-06-30')
        ->and($period->leaving_reason)->toBe('resigned')
        ->and($this->person->fresh()->is_active)->toBeFalse();
});

it('ends the jobs with the person', function () {
    $this->employment->join($this->person);
    $this->jobs->give($this->person, ['designation_id' => $this->world->driverPost->id]);

    $this->employment->leave($this->person, '2026-06-30');

    // A person who has left still holding the post would keep appearing where
    // the school asks who the driver is — and would block their replacement.
    expect($this->jobs->currentJobsOf($this->person))->toBeEmpty();
});

it('turns the login off with them', function () {
    $this->employment->join($this->person);
    $this->employment->leave($this->person, '2026-06-30');

    expect((bool) $this->person->fresh()->user->is_active)->toBeFalse();
});

it('refuses a leaving date before they joined', function () {
    $this->employment->join($this->person);

    expect(fn () => $this->employment->leave($this->person, '2020-01-01'))
        ->toThrow(ValidationException::class);
});

it('refuses a leaving date in the future', function () {
    $this->employment->join($this->person);

    // A notice period is not a leaving date: until the day comes they are still
    // on the payroll and still on the register.
    expect(fn () => $this->employment->leave($this->person, now()->addMonth()->toDateString()))
        ->toThrow(ValidationException::class);
});

it('takes somebody back on without losing the first spell', function () {
    $this->employment->join($this->person);
    $this->employment->leave($this->person, '2026-06-30');

    $this->employment->rejoin($this->person, ['joined_on' => '2026-09-01']);

    $periods = StaffEmploymentPeriod::where('staff_profile_id', $this->person->id)
        ->orderBy('id')->get();

    // Two periods, one record — the year away is still there.
    expect($periods)->toHaveCount(2)
        ->and($periods[0]->left_on->toDateString())->toBe('2026-06-30')
        ->and($periods[1]->joined_on->toDateString())->toBe('2026-09-01')
        ->and($this->person->fresh()->is_active)->toBeTrue();
});

it('closes an open spell rather than hitting the constraint', function () {
    $this->employment->join($this->person);

    // Somebody forgot to mark them as having left. The ordinary case.
    $this->employment->rejoin($this->person, ['joined_on' => '2026-09-01']);

    expect(StaffEmploymentPeriod::where('staff_profile_id', $this->person->id)
        ->whereNull('left_on')->count())->toBe(1);
});

it('counts service across every spell', function () {
    $this->employment->join($this->person);
    $this->employment->leave($this->person, '2026-06-30');
    $this->employment->rejoin($this->person, ['joined_on' => '2026-07-01']);

    // Somebody who left and returned has served both, which is what an
    // experience letter has to say.
    expect($this->employment->serviceMonths($this->person))->toBeGreaterThanOrEqual(2);
});

/* ------------------------------------------------------------------ HTTP */

it('adds a job from the screen', function () {
    $this->postJson(route('staff.people.jobs.add', $this->person->id), [
        'designation_id' => $this->world->driverPost->id,
        'started_on' => '2026-04-01',
    ])->assertCreated();

    expect($this->jobs->currentJobsOf($this->person))->toHaveCount(1);
});

it('saves the personal file the module had nowhere to put', function () {
    $this->putJson(route('staff.people.personal', $this->person->id), [
        'cnic' => '35202-1234567-1',
        'phone' => '03001234567',
        'emergency_contact_name' => 'Fatima Bibi',
        'emergency_contact_phone' => '03007654321',
    ])->assertSuccessful();

    expect($this->person->fresh()->cnic)->toBe('35202-1234567-1');
});

it('refuses a CNIC that is not one', function () {
    $this->putJson(route('staff.people.personal', $this->person->id), [
        'cnic' => '1234',
    ])->assertStatus(422)->assertJsonValidationErrors('cnic');
});
