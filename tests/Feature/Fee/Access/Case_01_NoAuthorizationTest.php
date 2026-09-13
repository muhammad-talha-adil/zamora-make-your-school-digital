<?php

/**
 * Phase — who may touch a fee record at all.
 *
 * The ten `fee.*` permissions had been seeded since the beginning and **not
 * one of them was checked** — every route in `routes/fee.php` sat on `auth`
 * alone, so any signed-in account (a student's own portal login included,
 * since students and staff share one `User` model and one guard) could record
 * a payment, delete a fee structure, or read anybody's voucher.
 */

use App\Models\User;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(4);

    $this->outsider = User::create([
        'name' => 'No Rights',
        'username' => 'no.rights.'.uniqid(),
        'email' => 'no.rights.'.uniqid().'@school.test',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
});

it('turns away an unpermissioned account from the payment list', function () {
    $this->actingAs($this->outsider)->get(route('fee.payments.index'))->assertForbidden();
});

it('turns away an unpermissioned account from recording a payment', function () {
    $item = $this->items->first();

    $this->actingAs($this->outsider)->post(route('fee.payments.store'), [
        'student_id' => $this->world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => (float) $item->net_amount,
        'charges' => [[
            'voucher_id' => $item->fee_voucher_id,
            'fee_voucher_item_id' => $item->id,
            'amount' => (float) $item->net_amount,
        ]],
    ])->assertForbidden();
});

it('turns away an unpermissioned account from the voucher list', function () {
    $this->actingAs($this->outsider)->get(route('fee.vouchers.index'))->assertForbidden();
});

it('turns away an unpermissioned account from a voucher record', function () {
    $voucher = $this->world->voucherFor(4);

    $this->actingAs($this->outsider)->get(route('fee.vouchers.show', $voucher->id))->assertForbidden();
});

it('turns away an unpermissioned account from deleting a fee head', function () {
    $this->actingAs($this->outsider)->delete(route('fee.heads.destroy', $this->world->admissionHead()->id))
        ->assertForbidden();
});

it('turns away an unpermissioned account from the collection report', function () {
    $this->actingAs($this->outsider)->get(route('fee.reports.collection'))->assertForbidden();
});

it('lets the actor with fee abilities through', function () {
    $this->actingAs($this->world->school->actor)->get(route('fee.payments.index'))->assertOk();
});
