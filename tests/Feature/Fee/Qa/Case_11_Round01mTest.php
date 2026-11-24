<?php

/**
 * Case 11 - round 01m browser QA fixes.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeHead;
use App\Services\Fee\FeePaymentService;
use App\Services\StudentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 0, 'once' => 0]);

    FeeHead::firstOrCreate(['code' => 'PREVIOUS_BALANCE'], [
        'name' => 'Previous Balance', 'category' => 'misc', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 60, 'is_active' => true,
    ]);
    $this->fineHead = FeeHead::firstOrCreate(['code' => 'LATE_FINE'], [
        'name' => 'Late Fee', 'category' => 'fine', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 70, 'is_active' => true,
    ]);
});

it('removes auto late fees from an adjusted voucher and fixes gross on a paid one', function () {
    $this->world->generate(4);
    $adjusted = $this->world->voucherFor(4);
    $adjusted->items()->create([
        'fee_head_id' => $this->fineHead->id, 'description' => 'Late Fee - voucher FV-X (20 days late)',
        'amount' => 300, 'discount_amount' => 0, 'net_amount' => 300, 'source_type' => 'arrears', 'source_module' => 'fee',
    ]);
    $adjusted->update(['status' => VoucherStatus::ADJUSTED, 'gross_amount' => 5000, 'fine_amount' => 300, 'net_amount' => 5300, 'balance_amount' => 0, 'paid_amount' => 1000]);

    $this->world->generate(5);
    $paid = $this->world->voucherFor(5);
    $paid->items()->create([
        'fee_head_id' => null, 'description' => 'Late fee', 'amount' => 200, 'discount_amount' => 0,
        'net_amount' => 200, 'source_type' => 'manual', 'source_module' => 'fee',
    ]);
    $paid->update(['status' => VoucherStatus::PAID, 'gross_amount' => 5200, 'fine_amount' => 200, 'net_amount' => 5200, 'balance_amount' => 0, 'paid_amount' => 5200]);

    Artisan::call('fee:repair-vouchers');

    $adjusted->refresh();
    expect((float) $adjusted->net_amount)->toBe(5000.0)
        ->and((float) $adjusted->fine_amount)->toBe(0.0)
        ->and((float) $adjusted->balance_amount)->toBe(0.0)
        ->and((float) $paid->fresh()->gross_amount)->toBe(5000.0)
        ->and((float) $paid->fresh()->fine_amount)->toBe(200.0)
        ->and((float) $paid->fresh()->net_amount)->toBe(5200.0);
});

it('shows concession and late fee on the payment page props and receipt', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $item = $voucher->items()->first();
    $payment = app(FeePaymentService::class)->record([
        'student_id' => $voucher->student_id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'received_amount' => 4900, 'concession' => 100, 'concession_reason' => 'Sibling',
        'charges' => [['voucher_id' => $voucher->id, 'fee_voucher_item_id' => $item->id, 'student_account_charge_id' => $item->student_account_charge_id, 'amount' => 4900]],
    ]);

    foreach (['fee.payments.show', 'fee.payments.receipt'] as $name) {
        $this->get(route($name, $payment->id))->assertInertia(fn ($page) => $page
            ->where('payment.concession_reason', 'Sibling')
            ->where('payment.concession_amount', fn ($v) => (float) $v === 100.0));
    }
});

it('tells the cashier a voucher was carried forward', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $voucher->update(['status' => VoucherStatus::ADJUSTED]);

    $this->get(route('fee.payments.create', ['student_id' => $voucher->student_id, 'voucher_id' => $voucher->id]))
        ->assertInertia(fn ($page) => $page->where('carriedForwardNotice', fn ($m) => str_contains($m, 'carried forward')));
});

it('rejects a second payment on a paid voucher with a visible message', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $item = $voucher->items()->first();
    $data = [
        'student_id' => $voucher->student_id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'received_amount' => 5000,
        'charges' => [['voucher_id' => $voucher->id, 'fee_voucher_item_id' => $item->id, 'student_account_charge_id' => $item->student_account_charge_id, 'amount' => 5000]],
    ];
    app(FeePaymentService::class)->record($data);

    $this->post(route('fee.payments.store'), $data)->assertSessionHasErrors('vouchers');
    expect(fn () => app(FeePaymentService::class)->record($data))->toThrow(ValidationException::class, 'already paid');
});

it('lists a student whose arrears sit on a voucher not yet due', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);
    $voucher->items()->create([
        'fee_head_id' => FeeHead::where('code', 'PREVIOUS_BALANCE')->value('id'), 'description' => 'Previous Outstanding Balance (Arrears)',
        'amount' => 100, 'discount_amount' => 0, 'net_amount' => 100, 'source_type' => 'arrears', 'source_module' => 'fee',
    ]);
    $voucher->update(['status' => 'unpaid', 'due_date' => now()->addDays(20)->toDateString(), 'balance_amount' => 5100]);

    $ids = collect($this->get(route('fee.reports.defaulters'))->viewData('page')['props']['defaulters']['data'])->pluck('id');
    expect($ids)->toContain($voucher->id);
});

it('puts the outstanding balance on the student fee summary', function () {
    $this->world->generate(4);
    $voucher = $this->world->voucherFor(4);

    $summary = app(StudentService::class)->feeSummaryFor($voucher->student);
    expect((float) $summary['outstanding'])->toBe((float) $voucher->balance_amount);
});
