<?php

/**
 * Case 04 — a fee voucher should surface a student's full previous record
 * (issue #113): line items originating from another module (inventory dues
 * billed through the unified `StudentAccountCharge` ledger) must say so, and
 * the voucher show page must list the student's other vouchers so a clerk
 * doesn't have to search for them separately.
 */

use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Finance\StudentAccountCharge;
use App\Models\StudentInventory;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
});

it('shows the source module for a voucher item billed from inventory dues', function () {
    $items = $this->world->generate(6);
    $voucher = FeeVoucher::findOrFail($items->first()->fee_voucher_id);

    $charge = StudentAccountCharge::create([
        'student_id' => $this->world->student->id,
        'source_module' => 'inventory',
        'source_type' => 'student_inventory_record',
        'charge_category' => 'inventory',
        'title' => 'Uniform & Books',
        'charge_date' => '2026-06-01',
        'amount' => 3500,
        'net_amount' => 3500,
        'voucher_id' => $voucher->id,
    ]);

    StudentInventory::create([
        'campus_id' => $this->world->school->campus->id,
        'student_id' => $this->world->student->id,
        'total_amount' => 3500,
        'total_discount' => 0,
        'final_amount' => 3500,
        'assigned_date' => '2026-06-01',
        'status' => StudentInventory::STATUS_ASSIGNED,
        'billing_status' => 'billed',
        'is_billable' => true,
        'student_account_charge_id' => $charge->id,
    ]);

    $inventoryItem = FeeVoucherItem::create([
        'fee_voucher_id' => $voucher->id,
        'student_account_charge_id' => $charge->id,
        'description' => 'Uniform & Books',
        'amount' => 3500,
        'net_amount' => 3500,
        'source_type' => 'manual',
        'source_module' => 'inventory',
        'reference_id' => $charge->id,
    ]);

    $response = $this->get(route('fee.vouchers.show', $voucher->id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Fee/Vouchers/Show')
        ->where('voucher.items', fn ($items) => collect($items)
            ->firstWhere('id', $inventoryItem->id)['source_module'] === 'inventory'
        )
    );
});

it('lists the student other previously-generated vouchers, excluding the current one', function () {
    $this->world->generate(6);
    $juneVoucher = $this->world->voucherFor(6);

    $this->world->generate(7);
    $julyVoucher = $this->world->voucherFor(7);

    $response = $this->get(route('fee.vouchers.show', $juneVoucher->id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Fee/Vouchers/Show')
        ->where('otherVouchers', fn ($otherVouchers) => collect($otherVouchers)->pluck('id')->contains($julyVoucher->id)
            && ! collect($otherVouchers)->pluck('id')->contains($juneVoucher->id)
        )
    );
});
