<?php

/**
 * Case 04 — the "Pay" row action (Vouchers/Index.vue, Vouchers/Show.vue)
 * navigates to /fee/payments/create?student_id=X. This guards the page
 * actually renders for a student with unpaid vouchers, instead of the
 * reported blank page.
 */

use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(4);
});

it('loads the create payment page for a student with unpaid vouchers', function () {
    $response = $this->get(route('fee.payments.create', ['student_id' => $this->world->student->id]));

    $response->assertOk();

    $page = $response->viewData('page');
    expect($page['component'])->toBe('Fee/Payments/Create')
        ->and($page['props']['unpaidVouchers'])->toHaveCount(1)
        ->and($page['props']['selectedStudent']['id'])->toBe($this->world->student->id);
});
