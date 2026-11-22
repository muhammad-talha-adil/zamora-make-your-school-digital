<?php

/**
 * Case 01 — fixes from the browser QA pass: payment search, reversal journal,
 * required reversal reason, structure errors as form errors, and billing that
 * refuses to pick between several matching structures.
 */

use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Finance\JournalEntry;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Support\Facades\Gate;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
});

function payFirstItem(): FeePayment
{
    $item = test()->world->generate(4)->first();

    test()->post(route('fee.payments.store'), [
        'student_id' => test()->world->student->id,
        'payment_date' => '2026-04-10',
        'payment_method' => 'cash',
        'received_amount' => (float) $item->net_amount,
        'charges' => [[
            'voucher_id' => $item->fee_voucher_id,
            'fee_voucher_item_id' => $item->id,
            'student_account_charge_id' => $item->student_account_charge_id,
            'amount' => (float) $item->net_amount,
        ]],
    ])->assertRedirect();

    return FeePayment::firstOrFail();
}

it('shows the student name and class in payment search', function () {
    $this->world->structureWithAllFrequencies();

    $name = $this->world->student->user->name;

    $this->getJson(route('fee.payments.search-students', ['q' => $this->world->student->registration_no]))
        ->assertOk()
        ->assertJsonPath('0.name', $name)
        ->assertJsonStructure([['id', 'name', 'registration_number', 'admission_no', 'class_name']]);
});

it('keeps the original journal and adds a reversal entry when a payment is reversed', function () {
    $this->world->structureWithAllFrequencies();
    $payment = payFirstItem();

    $this->post(route('fee.payments.reverse', $payment->id), ['reason' => 'Wrong student'])->assertRedirect();

    expect(JournalEntry::where('source_type', 'fee_payment')->where('source_id', $payment->id)->exists())->toBeTrue()
        ->and(JournalEntry::where('source_type', 'fee_payment_reversal')->where('source_id', $payment->id)->count())->toBe(1);
});

it('needs a reason to reverse a payment', function () {
    $this->world->structureWithAllFrequencies();
    $payment = payFirstItem();

    $this->post(route('fee.payments.reverse', $payment->id), [])->assertSessionHasErrors('reason');
    expect($payment->fresh()->status->value)->not->toBe('reversed');
});

it('returns a structure overlap as a form error, not raw JSON', function () {
    $this->world->structureWithAllFrequencies();

    $response = $this->from(route('fee.structures.create'))->post(route('fee.structures.store'), [
        'title' => 'Another',
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'status' => 'active',
        'items' => [['fee_head_id' => $this->world->school->monthlyHead->id, 'amount' => 100]],
    ]);

    $response->assertRedirect(route('fee.structures.create'))->assertSessionHasErrors('title');
});

it('does not bill silently when two structures match the class', function () {
    $this->world->structureWithAllFrequencies();
    $this->world->school->feeStructure(['title' => 'Duplicate', 'is_default' => false]);

    app(VoucherGenerationService::class)->generateMonthlyVouchers(4, 2026);

    expect(FeeVoucher::count())->toBe(0);
});

it('bills with the chosen structure when one is picked', function () {
    $this->world->structureWithAllFrequencies();
    $second = $this->world->school->feeStructure(['title' => 'Duplicate', 'is_default' => false]);

    $result = app(VoucherGenerationService::class)->generateMonthlyVouchers(4, 2026, ['fee_structure_id' => $second->id]);

    expect($result['generated'])->toBe(1);
});

it('lets a school-wide user generate vouchers for any campus', function () {
    expect(Gate::forUser($this->world->school->actor)->allows('generate', [FeeVoucher::class, 987654]))->toBeTrue();
});
