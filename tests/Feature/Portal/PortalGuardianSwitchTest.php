<?php

/**
 * A guardian with more than one child. The portal is a query-string
 * selector, not a switcher UI (that is the frontend's concern) — the backend
 * just has to accept an optional `?student_id=`, default sensibly when it is
 * missing, and validate it against the guardian's real children before using
 * it for anything.
 */

use Tests\Support\PortalWorld;

beforeEach(function () {
    $this->world = PortalWorld::make();
});

it('defaults to the first child when a guardian does not pick one', function () {
    [, $studentA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $guardian = $this->world->guardianFor([$studentA, $studentB]);

    $response = $this->actingAs($guardian)->get(route('portal.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('student.id', $studentA->id)
        ->has('students', 2)
    );
});

it('lets a guardian switch to the second child via ?student_id=', function () {
    [, $studentA] = $this->world->portalStudent('a');
    [, $studentB] = $this->world->portalStudent('b');
    $guardian = $this->world->guardianFor([$studentA, $studentB]);
    $this->world->examResultFor($studentB);

    $response = $this->actingAs($guardian)->get(route('portal.exams.index', ['student_id' => $studentB->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('student.id', $studentB->id)
        ->has('results.data', 1)
    );
});

it('lets a guardian read a linked child\'s voucher and exam result directly', function () {
    [, $student] = $this->world->portalStudent();
    $guardian = $this->world->guardianFor([$student]);
    $voucher = $this->world->voucherFor($student, 6, 2026);
    $result = $this->world->examResultFor($student);

    $this->actingAs($guardian)->get(route('portal.fees.show', $voucher->id))->assertOk();
    $this->actingAs($guardian)->get(route('portal.exams.show', $result->id))->assertOk();
});
