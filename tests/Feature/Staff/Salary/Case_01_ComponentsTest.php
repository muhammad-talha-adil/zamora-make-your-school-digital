<?php

use App\Models\Staff\SalaryHead;
use App\Models\Staff\StaffSalaryComponent;
use App\Services\Staff\StaffSalaryService;
use Database\Seeders\SalaryHeadSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new SalaryHeadSeeder)->run();
});

it('a profile with no components falls back to the legacy lump columns', function () {
    $staff = $this->world->person('Zainab Ali', salary: 50000);
    $staff->update(['allowance_amount' => 5000, 'deduction_amount' => 1000]);

    $service = app(StaffSalaryService::class);

    expect($service->grossOn($staff, now()->toDateString()))->toBe(55000.0);
    expect($service->deductionsOn($staff, now()->toDateString()))->toBe(1000.0);
});

it('sets a salary component and it counts toward gross once any exist', function () {
    $staff = $this->world->person('Zainab Ali', salary: 50000);
    $hra = SalaryHead::where('code', 'HRA')->firstOrFail();

    $response = $this->actingAs($this->world->school->actor)->postJson(route('staff.salary.store', $staff), [
        'salary_head_id' => $hra->id,
        'amount' => 8000,
        'effective_from' => '2026-04-01',
    ]);

    $response->assertCreated();

    $service = app(StaffSalaryService::class);
    expect($service->grossOn($staff, '2026-04-15'))->toBe(58000.0);
});

it('a raise closes the previous component instead of editing it', function () {
    $staff = $this->world->person('Zainab Ali', salary: 50000);
    $hra = SalaryHead::where('code', 'HRA')->firstOrFail();
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->postJson(route('staff.salary.store', $staff), [
        'salary_head_id' => $hra->id,
        'amount' => 8000,
        'effective_from' => '2026-04-01',
    ])->assertCreated();

    $this->actingAs($actor)->postJson(route('staff.salary.store', $staff), [
        'salary_head_id' => $hra->id,
        'amount' => 10000,
        'effective_from' => '2026-07-01',
    ])->assertCreated();

    expect(StaffSalaryComponent::where('staff_profile_id', $staff->id)->count())->toBe(2);

    $service = app(StaffSalaryService::class);
    expect($service->grossOn($staff, '2026-06-01'))->toBe(58000.0);
    expect($service->grossOn($staff, '2026-08-01'))->toBe(60000.0);
});

it('a deduction head does not count toward gross', function () {
    $staff = $this->world->person('Zainab Ali', salary: 50000);
    $pf = SalaryHead::where('code', 'PF')->firstOrFail();

    $this->actingAs($this->world->school->actor)->postJson(route('staff.salary.store', $staff), [
        'salary_head_id' => $pf->id,
        'amount' => 2000,
        'effective_from' => '2026-04-01',
    ])->assertCreated();

    $service = app(StaffSalaryService::class);
    expect($service->grossOn($staff, '2026-04-15'))->toBe(50000.0);
    expect($service->deductionsOn($staff, '2026-04-15'))->toBe(2000.0);
});

it('a person without staff.salary.manage may not set a component', function () {
    $staff = $this->world->person('Zainab Ali');
    $outsider = $this->world->person('No Rights', abilities: ['staff.manage']);
    $hra = SalaryHead::where('code', 'HRA')->firstOrFail();

    $this->actingAs($outsider->user)->postJson(route('staff.salary.store', $staff), [
        'salary_head_id' => $hra->id,
        'amount' => 8000,
        'effective_from' => '2026-04-01',
    ])->assertForbidden();
});
