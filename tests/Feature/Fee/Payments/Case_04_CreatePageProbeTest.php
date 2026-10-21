<?php

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
});
