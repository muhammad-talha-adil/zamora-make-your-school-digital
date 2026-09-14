<?php

/**
 * The portal landing page — `portal.student.access` was seeded on the
 * `student`/`guardian` roles from the start and checked nowhere. This is the
 * first thing that actually gates `/portal` on it, and the first screen that
 * reads a family's own fee/exam/attendance summary through
 * `ResolvesOwnStudent` rather than a route parameter.
 */

use App\Enums\Fee\VoucherStatus;
use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('lets a student reach the portal and see only their own summary', function () {
    [$user, $student] = $this->world->portalStudent();
    $voucher = $this->world->voucherFor($student, 4, 2026);
    $voucher->update(['status' => VoucherStatus::UNPAID, 'balance_amount' => 500]);
    $this->world->examResultFor($student);
    $this->world->attendanceFor($student, 'P');

    $response = $this->actingAs($user)->get(route('portal.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Index')
        ->where('student.id', $student->id)
        ->where('outstandingVoucher.balance_amount', fn ($value) => (float) $value === 500.0)
        ->where('latestResult.result_status', 'pass')
        ->where('todayAttendance.code', 'P')
    );
});

it('turns away a signed-in user with no linked student or guardian record', function () {
    $user = $this->world->fee->school->userWithRole('student', 'orphan.'.uniqid().'@school.test');

    $this->actingAs($user)->get(route('portal.index'))->assertForbidden();
});

it('turns away a signed-in user who does not hold portal.student.access', function () {
    // A staff role never granted the portal gate — the permission tables are
    // real here, so this is checked against the actual seeded roles, not a
    // stub.
    $outsider = $this->world->fee->school->userWithRole('accountant', 'accountant.'.uniqid().'@school.test');

    $this->actingAs($outsider)->get(route('portal.index'))->assertForbidden();
});
