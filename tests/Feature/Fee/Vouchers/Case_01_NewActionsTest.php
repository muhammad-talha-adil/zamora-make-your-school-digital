<?php

/**
 * Case 01 — the voucher actions `routes/fee.php` named but the controller
 * never defined: `create`, `destroy`, `generateBulk`, `publish`,
 * `addAdjustment`, `logPrint`. Every one of these was a guaranteed 500 the
 * moment its route was hit — a `BadMethodCallException`, not a 404, since the
 * route resolved and the controller simply had no such method.
 */

use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherAdjustment;
use App\Models\Fee\FeeVoucherPrintLog;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->world->generate(4);
    $this->voucher = $this->world->voucherFor(4);
});

it('the create route renders the same screen as generate.form', function () {
    $this->get(route('fee.vouchers.create'))->assertOk();
});

it('publishes a voucher', function () {
    expect($this->voucher->published_at)->toBeNull();

    $this->patch(route('fee.vouchers.publish', $this->voucher->id))->assertRedirect();

    expect($this->voucher->fresh()->published_at)->not->toBeNull();
});

it('refuses to publish an already-published voucher', function () {
    $this->voucher->update(['published_at' => now()]);

    $this->patch(route('fee.vouchers.publish', $this->voucher->id))->assertSessionHasErrors();
});

it('records a voucher-level adjustment and adjusts the balance', function () {
    $before = (float) $this->voucher->balance_amount;

    $this->post(route('fee.vouchers.add-adjustment', $this->voucher->id), [
        'adjustment_type' => 'arrears',
        'amount' => 1000,
        'description' => 'Carried over from last term',
    ])->assertRedirect();

    expect(FeeVoucherAdjustment::where('fee_voucher_id', $this->voucher->id)->count())->toBe(1)
        ->and((float) $this->voucher->fresh()->balance_amount)->toBe($before + 1000);
});

it('a waiver reduces the balance instead of raising it', function () {
    $before = (float) $this->voucher->balance_amount;

    $this->post(route('fee.vouchers.add-adjustment', $this->voucher->id), [
        'adjustment_type' => 'waiver',
        'amount' => 500,
    ])->assertRedirect();

    expect((float) $this->voucher->fresh()->balance_amount)->toBe(max(0, $before - 500));
});

it('logs a print', function () {
    $this->post(route('fee.vouchers.log-print', $this->voucher->id))->assertSuccessful();

    $log = FeeVoucherPrintLog::where('fee_voucher_id', $this->voucher->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->print_count)->toBe(1);

    $this->post(route('fee.vouchers.log-print', $this->voucher->id))->assertSuccessful();
    expect($log->fresh()->print_count)->toBe(2);
});

it('deletes a voucher that was never paid', function () {
    $this->delete(route('fee.vouchers.destroy', $this->voucher->id))->assertRedirect();

    expect(FeeVoucher::find($this->voucher->id))->toBeNull();
});

it('refuses to delete a voucher with payments', function () {
    $this->voucher->update(['paid_amount' => 500]);

    $this->delete(route('fee.vouchers.destroy', $this->voucher->id))->assertSessionHasErrors();
    expect(FeeVoucher::find($this->voucher->id))->not->toBeNull();
});

it('generates vouchers for every active class in the campus at once', function () {
    $response = $this->post(route('fee.vouchers.generate-bulk'), [
        'session_id' => $this->world->school->session->id,
        'campus_id' => $this->world->school->campus->id,
        'month_ids' => [$this->world->monthId(5)],
        'year' => 2026,
    ]);

    $response->assertRedirect();
    expect(FeeVoucher::where('voucher_month_id', $this->world->monthId(5))->exists())->toBeTrue();
});
