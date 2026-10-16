<?php

/**
 * Case 06 — the voucher's student identity is frozen at generation time.
 *
 * This table's own docblock calls a voucher "a historical snapshot" — true
 * for its amounts, but the student's name/registration number were always
 * read live off the `student` relation, so a later name correction silently
 * rewrote a voucher already printed and handed to a parent. Same class of
 * bug `new_fee_payments` had.
 */

use App\Models\Fee\FeeVoucher;
use App\Services\Fee\VoucherGenerationService;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->service = app(VoucherGenerationService::class);
});

it('captures the student name and registration number at generation time', function () {
    $this->service->generateMonthlyVouchers(4, 2026);

    $voucher = FeeVoucher::firstOrFail();

    expect($voucher->student_name_snapshot)->toBe($this->world->student->name)
        ->and($voucher->registration_no_snapshot)->toBe($this->world->student->registration_no);
});

it('keeps showing the original name on the voucher page after the student is renamed', function () {
    $this->service->generateMonthlyVouchers(4, 2026);
    $voucher = FeeVoucher::firstOrFail();
    $originalName = $voucher->student_name_snapshot;

    $this->world->student->user->update(['name' => 'A Completely Different Name']);

    $response = $this->get(route('fee.vouchers.show', $voucher->id));
    $response->assertOk();

    $shown = $response->viewData('page')['props']['voucher']['student']['name'];

    expect($shown)->toBe($originalName)
        ->and($shown)->not->toBe('A Completely Different Name');
});
