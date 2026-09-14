<?php

/**
 * A student cannot see another family's records by guessing an id — neither
 * through a portal detail route, nor by putting a stranger's `student_id` in
 * the query string of an index route.
 */

use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('forbids a student from opening another student\'s voucher through the portal', function () {
    [$userA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $voucherB = $this->world->voucherFor($studentB, 4, 2026);

    $this->actingAs($userA)->get(route('portal.fees.show', $voucherB->id))->assertForbidden();
});

it('forbids a student from opening another student\'s exam result through the portal', function () {
    [$userA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $resultB = $this->world->examResultFor($studentB);

    $this->actingAs($userA)->get(route('portal.exams.show', $resultB->id))->assertForbidden();
});

it('forbids a student from switching the portal to another family\'s student_id', function () {
    [$userA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');

    $this->actingAs($userA)->get(route('portal.index', ['student_id' => $studentB->id]))->assertForbidden();
    $this->actingAs($userA)->get(route('portal.fees.index', ['student_id' => $studentB->id]))->assertForbidden();
    $this->actingAs($userA)->get(route('portal.exams.index', ['student_id' => $studentB->id]))->assertForbidden();
    $this->actingAs($userA)->get(route('portal.attendance.index', ['student_id' => $studentB->id]))->assertForbidden();
});
