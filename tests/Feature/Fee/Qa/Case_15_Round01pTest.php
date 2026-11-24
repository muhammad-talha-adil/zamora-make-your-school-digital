<?php

/**
 * Round 01p — current-campus scoping of every Fee list/report, the reversed
 * receipt, the admission number on receipts and phone/discount-limit rules.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Support\Phone;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->world->generate(4);
    $this->campusA = $this->world->school->campus;
    $this->campusB = $this->world->school->otherCampus;

    FeePayment::create([
        'receipt_no' => 'TEST-SCOPE-1',
        'student_id' => $this->world->student->id,
        'student_enrollment_record_id' => $this->world->enrollment->id,
        'campus_id' => $this->campusA->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => 500,
        'allocated_amount' => 500,
        'excess_amount' => 0,
        'remaining_unallocated_amount' => 0,
        'status' => 'posted',
        'received_by' => $this->world->school->actor->id,
    ]);
});

it('shows a campus its own vouchers, payments and report totals and nothing from the other campus', function () {
    $this->withSession(['viewing_campus_id' => $this->campusB->id]);

    expect($this->getJson(route('fee.vouchers.list'))->json('data'))->toBeEmpty();
    $this->get(route('fee.payments.index'))->assertInertia(fn ($page) => $page->where('payments.total', 0));
    $summary = $this->get(route('fee.reports.collection'))->viewData('page')['props']['summary'];
    expect((int) $summary['payment_count'])->toBe(0)->and((float) $summary['total_received'])->toBe(0.0);
    $this->get(route('fee.reports.outstanding'))->assertInertia(fn ($page) => $page->where('topDefaulters', []));
    $stats = $this->getJson(route('fee.dashboard.stats'))->json('stats');
    expect((int) $stats['totalVouchers'])->toBe(0)->and((float) $stats['totalCollected'])->toBe(0.0)->and((float) $stats['totalOutstanding'])->toBe(0.0);

    $this->withSession(['viewing_campus_id' => $this->campusA->id]);

    expect($this->getJson(route('fee.vouchers.list'))->json('data'))->not->toBeEmpty();
    $this->get(route('fee.payments.index'))->assertInertia(fn ($page) => $page->where('payments.total', 1));
    expect((int) $this->get(route('fee.reports.collection'))->viewData('page')['props']['summary']['payment_count'])->toBe(1);
    expect((int) $this->getJson(route('fee.dashboard.stats'))->json('stats.totalVouchers'))->toBeGreaterThan(0);
});

it('shows the reversed receipt as reversed with the voucher real balance', function () {
    $voucher = FeeVoucher::query()->where('student_id', $this->world->student->id)->first();
    $voucher->update(['balance_amount' => 9000, 'status' => VoucherStatus::UNPAID]);
    $payment = FeePayment::first();
    $payment->update(['status' => 'reversed']);
    $payment->allocations()->create([
        'fee_voucher_id' => $voucher->id,
        'allocated_amount' => 500,
        'allocation_date' => '2026-04-10',
    ]);

    $this->get(route('fee.payments.show', $payment))->assertInertia(fn ($page) => $page
        ->where('payment.status', 'reversed')
        ->where('payment.remaining_balance', 9000)
        ->where('payment.student.admission_no', $this->world->student->admission_no));
});

it('normalises phone numbers to the 0333-1112233 format', function (string $typed) {
    expect(Phone::normalize($typed))->toBe('0333-1112233');
})->with(['plain' => '03331112233', 'dashed' => '0333-1112233', 'plus92' => '+92 333 1112233', '0092' => '00923331112233', 'spaces' => '0333 111 2233']);

it('rejects a discount type above its limit', function (string $type, string $value) {
    $this->post(route('fee.discount-types.store'), [
        'name' => 'Too big',
        'default_value_type' => $type,
        'default_value' => $value,
    ])->assertSessionHasErrors('default_value');
})->with([['percent', '150'], ['fixed', '10000001']]);
