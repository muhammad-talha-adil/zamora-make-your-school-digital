<?php

/**
 * The two `viewAny()` policy gaps this feature closes:
 * `FeeVoucherPolicy`/`FeePaymentPolicy` and `ExamResultHeaderPolicy` only
 * accepted the admin abilities, so a student/guardian holding only
 * `fee.view.own`/`exam.result.view.own` 403'd before the portal's own list
 * endpoints could even run their query. Fixing `viewAny()` only opens the
 * door — every list here is still scoped to the caller's own resolved
 * student, never the whole table.
 */

use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('lets a student reach their own fee voucher list', function () {
    [$user, $student] = $this->world->portalStudent();
    $this->world->voucherFor($student, 4, 2026);

    $response = $this->actingAs($user)->get(route('portal.fees.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Fees/Index')
        ->has('vouchers.data', 1)
    );
});

it('never returns another student\'s voucher in the list, even though the policy now allows the list itself', function () {
    [$userA, $studentA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $voucherA = $this->world->voucherFor($studentA, 4, 2026);
    $this->world->voucherFor($studentB, 5, 2026);

    $response = $this->actingAs($userA)->get(route('portal.fees.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Fees/Index')
        ->has('vouchers.data', 1)
        ->where('vouchers.data.0.id', $voucherA->id)
    );
});

it('lets a student reach their own exam result list, scoped to only their own results', function () {
    [$userA, $studentA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $this->world->examResultFor($studentA);
    $this->world->examResultFor($studentB);

    $response = $this->actingAs($userA)->get(route('portal.exams.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Exams/Index')
        ->has('results.data', 1)
    );
});

it('does not let the admin fee list break for an outsider holding no fee ability at all', function () {
    $outsider = $this->world->fee->school->userWithRole('driver', 'driver.'.uniqid().'@school.test');

    $this->actingAs($outsider)->getJson(route('fee.vouchers.list'))->assertForbidden();
});

it('does not let the admin exam result list break for an outsider holding no exam ability at all', function () {
    $outsider = $this->world->fee->school->userWithRole('driver', 'driver.'.uniqid().'@school.test');

    $this->actingAs($outsider)->getJson(route('exam.results.index', ['exam_id' => 1]))->assertForbidden();
});
