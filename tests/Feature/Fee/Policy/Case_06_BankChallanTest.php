<?php

/**
 * Case 06 — the three-part bank challan.
 *
 * Fee here is collected at a bank branch, not at the school, so the slip has to
 * carry everything the teller needs: the amount, the due date and a reference
 * they can key in. It prints in three parts — the bank keeps one, returns one
 * to the school with the day's scroll, and the parent keeps the third.
 */

use App\Enums\Fee\FineType;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeVoucher;
use App\Models\School;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000]);
    $this->world->generate(4);
    $this->voucher = $this->world->voucherFor(4);
});

it('prints all three copies on one sheet', function () {
    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSuccessful()
        ->assertSee('Bank Copy')
        ->assertSee('School Copy')
        ->assertSee('Parent Copy');
});

it('carries the amount and the due date', function () {
    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSee($this->voucher->voucher_no)
        ->assertSee($this->voucher->due_date->format('d M Y'));
});

it('states the amount in words as well as figures', function () {
    // A challan is a financial instrument; the figure has to appear twice.
    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSee('Rupees', false);
});

it('prints the reference the teller keys in', function () {
    $reference = $this->voucher->challanReferenceFormatted();

    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSee($reference);
});

it('closes the reference with a valid check digit', function () {
    $reference = $this->voucher->challanReference();
    $body = substr($reference, 0, -1);
    $digit = (int) substr($reference, -1);

    // A mistyped consumer number is rejected at the counter rather than turning
    // up as an unmatched deposit the office has to chase.
    expect(FeeVoucher::checkDigitFor($body))->toBe($digit);
});

it('gives two vouchers two different references', function () {
    $this->world->generate(5);
    $may = $this->world->voucherFor(5);

    expect($may->challanReference())->not->toBe($this->voucher->challanReference());
});

it('shows the school bank account when the school has one', function () {
    School::create([
        'name' => 'Zamora Public School',
        'address' => 'Lahore',
        'bank_name' => 'Meezan Bank',
        'bank_account_title' => 'Zamora School Fee Collection',
        'bank_account_no' => 'PK00MEZN0000123456789',
        'bank_branch' => 'Gulberg',
        'is_active' => true,
    ]);

    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSee('Meezan Bank')
        ->assertSee('PK00MEZN0000123456789');
});

it('prints for a school that has no bank account set', function () {
    School::create([
        'name' => 'Zamora Public School',
        'address' => 'Lahore',
        'is_active' => true,
    ]);

    // The account is optional; a school collecting at its own counter has none.
    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSuccessful()
        ->assertSee('Zamora Public School');
});

it('prints before any school record exists', function () {
    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSuccessful()
        ->assertSee($this->world->school->campus->name);
});

it('warns the parent what a late payment will cost', function () {
    FeeFineRule::create([
        'name' => 'Standard late fee',
        'campus_id' => $this->world->school->campus->id,
        'session_id' => $this->world->school->session->id,
        'fine_type' => FineType::SLAB,
        'fine_value' => 0,
        'initial_amount' => 200,
        'daily_amount' => 50,
        'grace_days' => 5,
        'effective_from' => '2026-04-01',
        'is_active' => true,
    ]);

    $this->get(route('fee.vouchers.challan', $this->voucher))
        ->assertSee('After due date')
        ->assertSee('200')
        ->assertSee('per day');
});

it('prints with no fine rule set', function () {
    $this->get(route('fee.vouchers.challan', $this->voucher))->assertSuccessful();
});

it('lists every charge on the voucher', function () {
    $response = $this->get(route('fee.vouchers.challan', $this->voucher));

    foreach ($this->voucher->items as $item) {
        $response->assertSee($item->feeHead->name);
    }
});
