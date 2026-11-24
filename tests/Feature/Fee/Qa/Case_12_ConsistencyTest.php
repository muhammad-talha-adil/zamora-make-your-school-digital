<?php

/**
 * Case 12 - one definition of "what a student owes".
 *
 * Profile card, ledger, Outstanding report, Defaulters report and the
 * dashboard must all agree because they read StudentBalanceService.
 */

use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeePayment;
use App\Services\Fee\FeePaymentService;
use App\Services\Fee\StudentBalanceService;
use App\Services\StudentService;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'yearly' => 0, 'once' => 0]);
    FeeHead::firstOrCreate(['code' => 'PREVIOUS_BALANCE'], [
        'name' => 'Previous Balance', 'category' => 'misc', 'is_recurring' => false,
        'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 60, 'is_active' => true,
    ]);

    $this->world->generate(4);
    $base = $this->world->voucherFor(4);
    $this->student = $base->student;

    $clone = function (int $month) use ($base) {
        $copy = $base->replicate();
        $copy->voucher_no = 'FV-TEST-'.$month;
        $copy->voucher_month_id = $this->world->monthId($month);
        $copy->save();
        foreach ($base->items as $item) {
            $copy->items()->create($item->only(['fee_head_id', 'student_account_charge_id', 'description', 'amount', 'discount_amount', 'net_amount', 'source_type', 'source_module']));
        }

        return $copy;
    };

    $this->overdueOld = $base;
    $this->carried = $clone(5);
    $this->partial = $clone(6);
    $this->paid = $clone(7);
    $this->future = $clone(8);

    $this->overdueOld->update(['status' => 'unpaid', 'due_date' => now()->subDays(100)->toDateString(), 'net_amount' => 5000, 'balance_amount' => 5000]);

    $this->carried->update(['status' => VoucherStatus::ADJUSTED, 'due_date' => now()->subDays(60)->toDateString(), 'balance_amount' => 0, 'notes' => 'Balance carried forward to voucher X.']);

    $this->partial->update(['status' => 'partial', 'due_date' => now()->subDays(10)->toDateString(), 'net_amount' => 5000, 'paid_amount' => 2000, 'balance_amount' => 3000]);

    $this->paid->update(['status' => 'paid', 'balance_amount' => 0, 'paid_amount' => 5000]);

    $this->future->update(['status' => 'unpaid', 'due_date' => now()->addMonths(6)->toDateString(), 'net_amount' => 4050, 'balance_amount' => 4050]);
});

it('computes outstanding, overdue and not-yet-due once', function () {
    $balance = app(StudentBalanceService::class)->forStudent($this->student->id);

    expect($balance['outstanding'])->toBe(12050.0)
        ->and($balance['overdue'])->toBe(8000.0)
        ->and($balance['not_yet_due'])->toBe(4050.0)
        ->and($balance['days_overdue'])->toBe(100)
        ->and($balance['voucher_count'])->toBe(3);
});

it('shows the same outstanding on profile, ledger, outstanding report and defaulters', function () {
    $profile = app(StudentService::class)->feeSummaryFor($this->student->load('enrollmentRecords'));
    $ledger = $this->get(route('fee.ledger.show', $this->student->id))->viewData('page')['props'];
    $outstanding = $this->get(route('fee.reports.outstanding'))->viewData('page')['props'];
    $defaulters = $this->get(route('fee.reports.defaulters'))->viewData('page')['props'];
    $row = collect($defaulters['defaulters']['data'])->firstWhere('student_id', $this->student->id);

    expect($profile['outstanding'])->toBe(12050.0)
        ->and($ledger['balances']['outstanding'])->toBe(12050.0)
        ->and((float) $outstanding['summary']['total_outstanding'])->toBe(12050.0)
        ->and($row['outstanding'])->toBe(12050.0)
        ->and($row['overdue'])->toBe(8000.0)
        ->and($row['days_overdue'])->toBe(100);
});

it('keeps students with only not-yet-due vouchers off the defaulters list', function () {
    foreach ([$this->overdueOld, $this->partial] as $voucher) {
        $voucher->update(['status' => 'paid', 'balance_amount' => 0]);
    }

    $defaulters = $this->get(route('fee.reports.defaulters'))->viewData('page')['props']['defaulters']['data'];

    expect(collect($defaulters)->pluck('student_id'))->not->toContain($this->student->id);
});

it('counts carried-forward arrears as overdue from the carried voucher date', function () {
    foreach ([$this->overdueOld, $this->partial] as $voucher) {
        $voucher->update(['status' => 'paid', 'balance_amount' => 0]);
    }
    $this->future->items()->create([
        'fee_head_id' => FeeHead::where('code', 'PREVIOUS_BALANCE')->value('id'), 'description' => 'Arrears',
        'amount' => 1000, 'discount_amount' => 0, 'net_amount' => 1000, 'source_type' => 'arrears', 'source_module' => 'fee',
    ]);

    $balance = app(StudentBalanceService::class)->forStudent($this->student->id);

    expect($balance['overdue'])->toBe(1000.0)
        ->and($balance['not_yet_due'])->toBe(3050.0)
        ->and($balance['days_overdue'])->toBe(60);
});

it('never lets the ledger running balance go negative from carry-forward ordering', function () {
    $rows = $this->get(route('fee.ledger.show', $this->student->id))->viewData('page')['props']['rows'];

    expect(collect($rows)->pluck('balance')->min())->toBeGreaterThanOrEqual(0);
});

it('matches the collection report to the payments list and ignores reversed ones', function () {
    $item = $this->partial->items()->first();
    $pay = fn (float $amount) => app(FeePaymentService::class)->record([
        'student_id' => $this->student->id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'received_amount' => $amount,
        'charges' => [['voucher_id' => $this->partial->id, 'fee_voucher_item_id' => $item->id, 'student_account_charge_id' => $item->student_account_charge_id, 'amount' => $amount]],
    ]);
    $pay(1000);
    $second = $pay(700);
    $this->post(route('fee.payments.reverse', $second->id), ['reason' => 'Entered twice'])->assertRedirect();

    $summary = $this->get(route('fee.reports.collection'))->viewData('page')['props']['summary'];
    $listed = FeePayment::query()->where('status', '!=', 'reversed')->sum('received_amount');

    expect((float) $summary['total_received'])->toBe((float) $listed)->toBe(1000.0)
        ->and((float) $summary['reversed_total'])->toBe(700.0);
});

it('exports the reports as csv', function () {
    foreach (['fee.reports.outstanding', 'fee.reports.defaulters', 'fee.reports.collection'] as $name) {
        $this->get(route($name, ['export' => 1]))->assertOk()->assertDownload();
    }
});

it('shows the late fee once on a voucher page', function () {
    $this->partial->items()->create([
        'fee_head_id' => FeeHead::firstOrCreate(['code' => 'LATE_FINE'], ['name' => 'Late Fee', 'category' => 'fine', 'is_recurring' => false, 'default_frequency' => 'once', 'is_optional' => false, 'sort_order' => 70, 'is_active' => true])->id,
        'description' => 'Late fee', 'amount' => 200, 'discount_amount' => 0, 'net_amount' => 200, 'source_type' => 'manual', 'source_module' => 'fee',
    ]);

    $this->get(route('fee.vouchers.show', $this->partial->id))->assertInertia(fn ($page) => $page
        ->where('voucher.items', fn ($items) => collect($items)->where('description', 'Late fee')->isEmpty()));
});

it('follows the carry-forward chain to the open voucher', function () {
    $this->carried->update(['notes' => 'Balance carried forward to voucher '.$this->overdueOld->voucher_no.'.']);
    $this->overdueOld->update(['status' => VoucherStatus::ADJUSTED, 'notes' => 'Balance carried forward to voucher '.$this->partial->voucher_no.'.']);

    $this->get(route('fee.payments.create', ['student_id' => $this->student->id, 'voucher_id' => $this->carried->id]))
        ->assertInertia(fn ($page) => $page->where('carriedForwardTo.voucher_no', $this->partial->voucher_no));
});

it('shows school name, items and remaining balance on the receipt', function () {
    $item = $this->partial->items()->first();
    $payment = app(FeePaymentService::class)->record([
        'student_id' => $this->student->id, 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        'received_amount' => 1000,
        'charges' => [['voucher_id' => $this->partial->id, 'fee_voucher_item_id' => $item->id, 'student_account_charge_id' => $item->student_account_charge_id, 'amount' => 1000]],
    ]);

    $this->get(route('fee.payments.receipt', $payment->id))->assertInertia(fn ($page) => $page
        ->has('schoolName')
        ->has('payment.items_summary')
        ->where('payment.remaining_balance', fn ($v) => (float) $v > 0));
});
