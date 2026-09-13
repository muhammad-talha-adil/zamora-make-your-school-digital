<?php

use App\Models\Staff\StaffLeave;
use App\Models\Staff\StaffLeaveType;
use Database\Seeders\SalaryHeadSeeder;
use Database\Seeders\StaffLeaveTypeSeeder;
use Tests\Support\StaffWorld;

beforeEach(function () {
    $this->world = StaffWorld::make();
    (new StaffLeaveTypeSeeder)->run();
    (new SalaryHeadSeeder)->run();
});

it('applies for casual leave and it lands pending', function () {
    $staff = $this->world->person('Zainab Ali');
    $type = StaffLeaveType::where('code', 'CL')->firstOrFail();

    $response = $this->actingAs($this->world->school->actor)->postJson(route('staff.leaves.store', $staff), [
        'staff_leave_type_id' => $type->id,
        'from_date' => '2026-05-04',
        'to_date' => '2026-05-05',
        'reason' => 'Family event',
    ]);

    $response->assertSuccessful();
    $leave = StaffLeave::firstOrFail();
    expect($leave->status)->toBe(StaffLeave::STATUS_PENDING);
    expect((float) $leave->days)->toBe(2.0);
});

it('refuses an application overlapping one already on file', function () {
    $staff = $this->world->person('Zainab Ali');
    $type = StaffLeaveType::where('code', 'CL')->firstOrFail();

    $payload = fn ($from, $to) => [
        'staff_leave_type_id' => $type->id,
        'from_date' => $from,
        'to_date' => $to,
    ];

    $this->actingAs($this->world->school->actor)
        ->postJson(route('staff.leaves.store', $staff), $payload('2026-05-04', '2026-05-06'))
        ->assertSuccessful();

    $this->actingAs($this->world->school->actor)
        ->postJson(route('staff.leaves.store', $staff), $payload('2026-05-05', '2026-05-07'))
        ->assertUnprocessable();
});

it('approves a pending application', function () {
    $staff = $this->world->person('Zainab Ali');
    $type = StaffLeaveType::where('code', 'CL')->firstOrFail();

    $this->actingAs($this->world->school->actor)->postJson(route('staff.leaves.store', $staff), [
        'staff_leave_type_id' => $type->id,
        'from_date' => '2026-05-04',
        'to_date' => '2026-05-05',
    ])->assertSuccessful();

    $leave = StaffLeave::firstOrFail();

    $this->actingAs($this->world->school->actor)
        ->patchJson(route('staff.leaves.decide', $leave), ['approve' => true])
        ->assertSuccessful();

    expect($leave->fresh()->status)->toBe(StaffLeave::STATUS_APPROVED);
});

it('cannot decide an application twice', function () {
    $staff = $this->world->person('Zainab Ali');
    $type = StaffLeaveType::where('code', 'CL')->firstOrFail();

    $this->actingAs($this->world->school->actor)->postJson(route('staff.leaves.store', $staff), [
        'staff_leave_type_id' => $type->id,
        'from_date' => '2026-05-04',
        'to_date' => '2026-05-05',
    ])->assertSuccessful();

    $leave = StaffLeave::firstOrFail();
    $actor = $this->world->school->actor;

    $this->actingAs($actor)->patchJson(route('staff.leaves.decide', $leave), ['approve' => true])->assertSuccessful();
    $this->actingAs($actor)->patchJson(route('staff.leaves.decide', $leave), ['approve' => false])->assertUnprocessable();
});

it('cancels a pending application', function () {
    $staff = $this->world->person('Zainab Ali');
    $type = StaffLeaveType::where('code', 'CL')->firstOrFail();

    $this->actingAs($this->world->school->actor)->postJson(route('staff.leaves.store', $staff), [
        'staff_leave_type_id' => $type->id,
        'from_date' => '2026-05-04',
        'to_date' => '2026-05-05',
    ])->assertSuccessful();

    $leave = StaffLeave::firstOrFail();

    $this->actingAs($this->world->school->actor)
        ->patchJson(route('staff.leaves.cancel', $leave))
        ->assertSuccessful();

    expect($leave->fresh()->status)->toBe(StaffLeave::STATUS_CANCELLED);
});

it('reports the balance remaining against the yearly entitlement', function () {
    $staff = $this->world->person('Zainab Ali');
    $type = StaffLeaveType::where('code', 'CL')->firstOrFail();

    $this->actingAs($this->world->school->actor)->postJson(route('staff.leaves.store', $staff), [
        'staff_leave_type_id' => $type->id,
        'from_date' => '2026-05-04',
        'to_date' => '2026-05-05',
    ])->assertSuccessful();

    StaffLeave::firstOrFail()->update(['status' => StaffLeave::STATUS_APPROVED]);

    $response = $this->actingAs($this->world->school->actor)
        ->getJson(route('staff.leaves.balance', $staff).'?year=2026')
        ->assertSuccessful();

    $clRow = collect($response->json('data'))->firstWhere('leave_type.code', 'CL');
    expect($clRow['remaining'])->toBe($type->days_per_year - 2);
});
