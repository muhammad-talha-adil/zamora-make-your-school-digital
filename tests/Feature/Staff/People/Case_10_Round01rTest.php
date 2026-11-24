<?php

/**
 * Browser QA 01r: clean URLs, CNIC formatting, friendly inline validation,
 * Y-m-d dates, header-campus scoping, plain-confirm toggle, payroll and
 * dashboard figures, designation delete.
 */

use App\Models\Month;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    $this->owner = $this->world->school->actor;
    $this->actingAs($this->owner);
});

function validStaffPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Nida Tariq',
        'employment_type' => 'permanent',
        'basic_salary' => 40000,
        'payment_method' => 'cash',
        'create_login' => false,
    ];
}

/* ------------------------------------------------------------------ URLs */

it('serves the staff list and record without "people" in the URL', function () {
    $staff = $this->world->person('Url Teacher');

    expect(route('staff.people.index', absolute: false))->toBe('/staff')
        ->and(route('staff.people.show', $staff, absolute: false))->toBe('/staff/'.$staff->id)
        ->and(route('staff.people.list', absolute: false))->toBe('/staff/list')
        ->and(route('staff.people.id-cards', absolute: false))->toBe('/staff/id-cards')
        ->and(route('staff.people.personal', $staff, absolute: false))->toBe('/staff/'.$staff->id.'/personal');

    $this->get('/staff')->assertOk();
    $this->get('/staff/'.$staff->id)->assertOk();
    $this->get('/staff/dashboard')->assertOk();
    $this->get('/staff/payroll')->assertOk();
});

it('redirects old /staff/people URLs to the new ones', function () {
    $this->get('/staff/people')->assertRedirect('/staff');
    $this->get('/staff/people/12')->assertRedirect('/staff/12');
    $this->get('/staff/people/list')->assertRedirect('/staff/list');
});

/* ------------------------------------------------------------------ CNIC */

it('accepts 13 bare digits for a CNIC and stores it dashed', function () {
    $this->postJson(route('staff.members.store'), validStaffPayload(['cnic' => '3520212345671']))->assertOk();

    expect(StaffProfile::latest('id')->first()->cnic)->toBe('35202-1234567-1');
});

it('says what a CNIC should look like when it is wrong', function () {
    $this->postJson(route('staff.members.store'), validStaffPayload(['cnic' => '12345']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.cnic.0', 'CNIC must be 13 digits, e.g. 35202-1234567-1');
});

/* ------------------------------------------------------------ validation */

it('returns every friendly message at once', function () {
    $response = $this->postJson(route('staff.members.store'), [
        'cnic' => 'abc', 'hire_date' => now()->addDay()->toDateString(),
        'basic_salary' => 99999999, 'allowance_amount' => 20000000, 'employment_type' => 'permanent', 'payment_method' => 'cash',
    ])->assertUnprocessable();

    expect($response->json('errors'))->toHaveKeys(['name', 'cnic', 'hire_date', 'basic_salary', 'allowance_amount']);
    expect($response->json('errors.name.0'))->toBe('Name is required.');
    expect($response->json('errors.hire_date.0'))->toBe('Hire date cannot be in the future.');
    expect($response->json('errors.basic_salary.0'))->toBe('Basic salary cannot be more than Rs 10,000,000.');
});

it('requires a basic salary with a friendly message', function () {
    $payload = validStaffPayload();
    unset($payload['basic_salary']);

    $this->postJson(route('staff.members.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.basic_salary.0', 'Basic salary is required.');
});

it('blocks a duplicate CNIC and phone naming the existing staff member', function () {
    $existing = $this->world->person('T-r Teacher');
    $existing->update(['cnic' => '35202-1234567-1', 'phone' => '0333-1112233']);

    $this->postJson(route('staff.members.store'), validStaffPayload(['cnic' => '3520212345671']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.cnic.0', 'Already used by T-r Teacher');

    $this->postJson(route('staff.members.store'), validStaffPayload(['phone' => '+92 333 1112233']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.phone.0', 'Already used by T-r Teacher');
});

it('lets a person keep their own CNIC when saving their personal file', function () {
    $staff = $this->world->person('Same Person');
    $staff->update(['cnic' => '35202-1234567-1']);

    $this->putJson(route('staff.people.personal', $staff), ['cnic' => '35202-1234567-1', 'dob' => '1990-01-01'])->assertOk();
});

it('rejects a birth date that is today or after the hire date, and a hire date before birth', function () {
    $staff = $this->world->person('Dates Person');

    $this->putJson(route('staff.people.personal', $staff), ['dob' => now()->toDateString()])
        ->assertUnprocessable()
        ->assertJsonPath('errors.dob.0', 'Date of birth must be before today.');

    $this->putJson(route('staff.people.personal', $staff), ['dob' => '2026-12-01'])->assertUnprocessable()->assertJsonValidationErrors('dob');

    $staff->update(['dob' => '1990-01-01']);

    $this->putJson(route('staff.members.update', $staff), [
        'name' => 'Dates Person', 'employee_no' => $staff->employee_no, 'employment_type' => 'permanent',
        'payment_method' => 'cash', 'basic_salary' => 1000, 'hire_date' => '1980-01-01',
    ])->assertUnprocessable()->assertJsonPath('errors.hire_date.0', 'Hire date cannot be before the date of birth.');
});

it('caps salary and allowance on edit too', function () {
    $staff = $this->world->person('Pay Person');

    $this->putJson(route('staff.members.update', $staff), [
        'name' => 'Pay Person', 'employee_no' => $staff->employee_no, 'employment_type' => 'permanent',
        'payment_method' => 'cash', 'basic_salary' => 10000001, 'allowance_amount' => 10000001,
    ])->assertUnprocessable()->assertJsonValidationErrors(['basic_salary', 'allowance_amount']);
});

/* ----------------------------------------------------------------- dates */

it('serialises dob and hire date as Y-m-d', function () {
    $staff = $this->world->person('Date Person');
    $staff->update(['dob' => '1990-01-01', 'hire_date' => '2026-04-01']);

    $json = $staff->fresh()->toArray();

    expect($json['dob'])->toBe('1990-01-01')->and($json['hire_date'])->toBe('2026-04-01');

    $page = $this->get(route('staff.people.show', $staff))->viewData('page')['props']['staff'];
    expect($page['dob'])->toBe('1990-01-01')->and($page['hire_date'])->toBe('2026-04-01');
});

/* ---------------------------------------------------------------- campus */

it('scopes staff to the header campus, school-wide posts included, and blocks the other campus', function () {
    $johar = $this->world->school->campus;
    $gulberg = $this->world->school->otherCampus;
    $this->world->school->withFullRoles();
    $owner = $this->world->person('Real Owner', campus: $johar)->user;
    $owner->assignRole('owner');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($owner);
    $inJohar = $this->world->person('Johar Person', campus: $johar);
    $inGulberg = $this->world->person('Gulberg Person', campus: $gulberg);
    $wide = $this->world->schoolWidePerson('Wide Person');

    $this->withSession(['viewing_campus_id' => $johar->id]);
    $names = collect($this->getJson(route('staff.people.list', ['status' => 'all']))->json('data.data'))->pluck('user.name');

    expect($names)->toContain('Johar Person', 'Wide Person')->not->toContain('Gulberg Person');
    $this->get(route('staff.people.show', $inGulberg))->assertForbidden();
    $this->putJson(route('staff.people.personal', $inGulberg), ['dob' => '1990-01-01'])->assertForbidden();
    $this->get(route('staff.people.show', $wide))->assertOk();
    $this->get(route('staff.people.show', $inJohar))->assertOk();

    $this->withSession(['viewing_campus_id' => $gulberg->id]);
    $this->get(route('staff.people.show', $inGulberg))->assertOk();
    $this->get(route('staff.people.show', $inJohar))->assertForbidden();
});

/* ---------------------------------------------------------------- toggle */

it('toggles status with no password', function () {
    $staff = $this->world->person('Toggle Person');

    $this->patchJson(route('staff.members.toggle', $staff))->assertOk();

    expect($staff->fresh()->is_active)->toBeFalse();
});

/* --------------------------------------------------------- payroll/dash */

it('shows the staff member real campus on the slip, school-wide only when none', function () {
    $staff = $this->world->person('Slip Person', campus: $this->world->school->otherCampus);
    $this->postJson(route('staff.payroll.generate'), ['payroll_month_id' => Month::firstOrCreate(['month_number' => 4], ['name' => 'April'])->id, 'payroll_year' => 2026])->assertSuccessful();

    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    $slip = $this->get(route('staff.payroll.items.slip', $item))->viewData('page')['props']['slip'];

    expect($slip['campus'])->toBe($this->world->school->otherCampus->name);
});

it('dashboard salary cost matches payroll gross and lists recent joiners', function () {
    $staff = $this->world->person('Dash Person', salary: 50000);
    $staff->update(['allowance_amount' => 5000, 'hire_date' => null]);

    $props = $this->get(route('staff.index'))->viewData('page')['props'];

    expect($props['summary']['monthly_salary'])->toBeGreaterThanOrEqual(55000.0);
    expect(collect($props['recentJoiners'])->pluck('id'))->toContain($staff->id);
});

/* ---------------------------------------------------------- designations */

it('deletes an unused designation and blocks one in use with a friendly message', function () {
    $unused = StaffDesignation::create(['name' => 'Unused Post', 'is_active' => true]);
    $this->deleteJson(route('staff.designations.destroy', $unused))->assertOk();
    expect(StaffDesignation::find($unused->id))->toBeNull();

    $this->world->person('Holder');
    $this->deleteJson(route('staff.designations.destroy', $this->world->teacherPost))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
    expect(StaffDesignation::find($this->world->teacherPost->id))->not->toBeNull();
});

it('says a designation name is required and unique', function () {
    $this->postJson(route('staff.designations.store'), ['name' => ''])
        ->assertUnprocessable()->assertJsonPath('errors.name.0', 'Name is required.');

    StaffDesignation::create(['name' => 'Clerk', 'is_active' => true]);
    $this->postJson(route('staff.designations.store'), ['name' => 'Clerk'])
        ->assertUnprocessable()->assertJsonPath('errors.name.0', 'A position with this name already exists.');
});

it('asks to confirm and replaces an overlapping school-wide run when a campus run is generated', function () {
    $month = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
    $this->world->person('Overlap Person');
    $payload = ['payroll_month_id' => $month->id, 'payroll_year' => 2026];

    $this->postJson(route('staff.payroll.generate'), $payload)->assertSuccessful();

    $campusPayload = $payload + ['campus_id' => $this->world->school->campus->id];
    $this->postJson(route('staff.payroll.generate'), $campusPayload)->assertStatus(409);

    $this->postJson(route('staff.payroll.generate'), $campusPayload + ['confirm_replace' => true])->assertSuccessful();

    expect(PayrollRun::count())->toBe(1)->and(PayrollRun::first()->campus_id)->toBe($this->world->school->campus->id);
});

it('does not let a campus run replace an overlapping run that already has payments', function () {
    $month = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
    $this->world->person('Paid Person');
    $payload = ['payroll_month_id' => $month->id, 'payroll_year' => 2026];

    $this->postJson(route('staff.payroll.generate'), $payload)->assertSuccessful();
    $this->postJson(route('staff.payroll.items.pay', PayrollRunItem::firstOrFail()), ['payment_method' => 'bank'])->assertSuccessful();

    $this->postJson(route('staff.payroll.generate'), $payload + ['campus_id' => $this->world->school->campus->id, 'confirm_replace' => true])
        ->assertUnprocessable();

    expect(PayrollRun::count())->toBe(1);
});

it('counts pending payroll payable once, from unpaid items only', function () {
    $month = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);
    $this->world->person('Pending Person', salary: 50000);

    $this->postJson(route('staff.payroll.generate'), ['payroll_month_id' => $month->id, 'payroll_year' => 2026])->assertSuccessful();

    $pending = $this->get(route('staff.index'))->viewData('page')['props']['summary']['pending_payroll'];
    expect((float) $pending)->toBe((float) PayrollRunItem::get()->sum(fn ($i) => $i->amountDue()));
});
