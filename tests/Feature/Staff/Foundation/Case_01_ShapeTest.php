<?php

/**
 * Phase 1 — the foundation holds.
 *
 * The schema decides what this module can ever do, so these tests are about the
 * shape rather than any screen: one person with several jobs, a salary in named
 * parts that remembers what it was in June, and the two rules the database has
 * to hold on its own.
 */

use App\Models\Staff\SalaryHead;
use App\Models\Staff\StaffAssignment;
use App\Models\Staff\StaffEmploymentPeriod;
use App\Models\Staff\StaffSalaryComponent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->person = $this->world->person('Bilal Ahmed');
});

/* ---------------------------------------------------------------- the jobs */

it('lets one person hold several jobs', function () {
    // The man who drives the van also does the gardening. One employee.
    StaffAssignment::create([
        'staff_profile_id' => $this->person->id,
        'designation_id' => $this->world->teacherPost->id,
        'is_primary' => true,
        'started_on' => '2026-04-01',
    ]);

    StaffAssignment::create([
        'staff_profile_id' => $this->person->id,
        'designation_id' => $this->world->driverPost->id,
        'started_on' => '2026-04-01',
    ]);

    expect($this->person->assignments()->count())->toBe(2)
        ->and($this->person->primaryAssignment->designation_id)->toBe($this->world->teacherPost->id);
});

it('refuses a second primary job', function () {
    StaffAssignment::create([
        'staff_profile_id' => $this->person->id,
        'designation_id' => $this->world->teacherPost->id,
        'is_primary' => true,
    ]);

    // The job somebody *is* cannot be two jobs. The database holds it, not a
    // promise in the code.
    expect(fn () => StaffAssignment::create([
        'staff_profile_id' => $this->person->id,
        'designation_id' => $this->world->driverPost->id,
        'is_primary' => true,
    ]))->toThrow(QueryException::class);
});

it('lets a finished job be replaced by a new primary one', function () {
    $first = StaffAssignment::create([
        'staff_profile_id' => $this->person->id,
        'designation_id' => $this->world->driverPost->id,
        'is_primary' => true,
        'started_on' => '2024-04-01',
    ]);

    $first->update(['ended_on' => '2026-03-31']);

    // Promoted from driver to teacher: the old job is history, not a clash.
    StaffAssignment::create([
        'staff_profile_id' => $this->person->id,
        'designation_id' => $this->world->teacherPost->id,
        'is_primary' => true,
        'started_on' => '2026-04-01',
    ]);

    expect($this->person->assignments()->count())->toBe(2)
        ->and($this->person->fresh()->primaryAssignment->designation_id)
        ->toBe($this->world->teacherPost->id);
});

/* -------------------------------------------------------------- the salary */

it('names the parts of a salary', function () {
    $house = SalaryHead::create([
        'name' => 'House Rent',
        'type' => SalaryHead::TYPE_ALLOWANCE,
        'is_part_of_gross' => true,
    ]);

    StaffSalaryComponent::create([
        'staff_profile_id' => $this->person->id,
        'salary_head_id' => $house->id,
        'amount' => 5000,
        'effective_from' => '2026-04-01',
    ]);

    // "What is this five thousand for?" now has an answer.
    expect($this->person->salaryComponents()->first()->salaryHead->name)->toBe('House Rent');
});

it('remembers what the allowance was in June', function () {
    $house = SalaryHead::create(['name' => 'House Rent', 'type' => SalaryHead::TYPE_ALLOWANCE]);

    // 5,000 from April, raised to 8,000 from July.
    StaffSalaryComponent::create([
        'staff_profile_id' => $this->person->id,
        'salary_head_id' => $house->id,
        'amount' => 5000,
        'effective_from' => '2026-04-01',
        'effective_to' => '2026-06-30',
    ]);

    StaffSalaryComponent::create([
        'staff_profile_id' => $this->person->id,
        'salary_head_id' => $house->id,
        'amount' => 8000,
        'effective_from' => '2026-07-01',
    ]);

    // A raise is a new row, not an edit — or June's payroll could not be
    // explained afterwards.
    $inJune = StaffSalaryComponent::where('staff_profile_id', $this->person->id)
        ->inForceOn('2026-06-15')->first();

    $inAugust = StaffSalaryComponent::where('staff_profile_id', $this->person->id)
        ->inForceOn('2026-08-15')->first();

    expect((float) $inJune->amount)->toBe(5000.0)
        ->and((float) $inAugust->amount)->toBe(8000.0);
});

it('keeps the salary on the person, not on a job', function () {
    // Phase 0's decision, written into the shape: one salary for the person.
    // A second-job allowance is a head, not a second salary.
    $second = SalaryHead::create([
        'name' => 'Second Duty Allowance',
        'type' => SalaryHead::TYPE_ALLOWANCE,
    ]);

    StaffSalaryComponent::create([
        'staff_profile_id' => $this->person->id,
        'salary_head_id' => $second->id,
        'amount' => 4000,
        'effective_from' => '2026-04-01',
    ]);

    expect(Schema::hasColumn('staff_salary_components', 'staff_assignment_id'))->toBeFalse()
        ->and($this->person->salaryComponents()->count())->toBe(1);
});

/* ------------------------------------------------------------- employment */

it('records joining, leaving and coming back', function () {
    $first = StaffEmploymentPeriod::create([
        'staff_profile_id' => $this->person->id,
        'joined_on' => '2024-04-01',
        'left_on' => '2026-06-30',
        'leaving_reason' => StaffEmploymentPeriod::REASON_RESIGNED,
    ]);

    StaffEmploymentPeriod::create([
        'staff_profile_id' => $this->person->id,
        'joined_on' => '2026-09-01',
        'previous_period_id' => $first->id,
    ]);

    // Two periods, one record — the shape that already works for a child.
    expect($this->person->employmentPeriods()->count())->toBe(2)
        ->and($this->person->currentEmployment->joined_on->toDateString())->toBe('2026-09-01');
});

it('refuses a second open spell of employment', function () {
    StaffEmploymentPeriod::create([
        'staff_profile_id' => $this->person->id,
        'joined_on' => '2024-04-01',
    ]);

    expect(fn () => StaffEmploymentPeriod::create([
        'staff_profile_id' => $this->person->id,
        'joined_on' => '2026-09-01',
    ]))->toThrow(QueryException::class);
});

it('answers whether somebody was here on a given day', function () {
    StaffEmploymentPeriod::create([
        'staff_profile_id' => $this->person->id,
        'joined_on' => '2024-04-01',
        'left_on' => '2026-06-30',
    ]);

    // The question every payroll run asks.
    expect(StaffEmploymentPeriod::where('staff_profile_id', $this->person->id)
        ->coveringDate('2026-05-01')->exists())->toBeTrue()
        ->and(StaffEmploymentPeriod::where('staff_profile_id', $this->person->id)
            ->coveringDate('2026-08-01')->exists())->toBeFalse();
});

/* ---------------------------------------------------------- personal file */

it('keeps the personal file the module had nowhere to put', function () {
    // Neither `staff_profiles` nor `users` carried any of this.
    $this->person->update([
        'cnic' => '35202-1234567-1',
        'phone' => '03001234567',
        'dob' => '1990-05-12',
        'emergency_contact_name' => 'Fatima Bibi',
        'emergency_contact_phone' => '03007654321',
        'emergency_contact_relation' => 'Wife',
    ]);

    $fresh = $this->person->fresh();

    expect($fresh->cnic)->toBe('35202-1234567-1')
        ->and($fresh->emergency_contact_name)->toBe('Fatima Bibi')
        ->and($fresh->dob->toDateString())->toBe('1990-05-12');
});

it('leaves the old salary columns working', function () {
    // Payroll runs off these today and must keep running off them until the
    // components replace them. A rebuild that takes the module offline is not
    // a rebuild.
    expect((float) $this->person->basic_salary)->toBe(50000.0)
        ->and($this->person->gross_salary)->toBe(50000.0)
        ->and($this->person->net_salary)->toBe(50000.0);
});
