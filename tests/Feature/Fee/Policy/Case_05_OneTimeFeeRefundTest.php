<?php

/**
 * Case 05 — giving back an admission fee when a child leaves at once.
 *
 * The paid record said the charge must never be billed again, which is right,
 * but there was no way to say the money went back. A child who left a fortnight
 * after admission kept a paid admission fee on record for ever, and the office
 * had nothing to reconcile the cash against.
 */

use App\Models\Fee\FeePolicy;
use App\Models\Fee\StudentPaidOneTimeFee;
use App\Services\Fee\OneTimeFeeRefundService;
use Illuminate\Support\Carbon;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies(['monthly' => 5000, 'once' => 20000]);
    $this->service = app(OneTimeFeeRefundService::class);
    $this->admissionHead = $this->world->admissionHead();
});

/** Sets how long the campus gives a family to change their mind. */
function refundWindow(FeeWorld $world, int $days): FeePolicy
{
    return FeePolicy::create([
        'campus_id' => $world->school->campus->id,
        'session_id' => $world->school->session->id,
        'one_time_refund_days' => $days,
    ]);
}

/** Bills and records the admission fee, as the fee run does. */
function payAdmissionFee(FeeWorld $world): StudentPaidOneTimeFee
{
    $world->generate(4);

    return StudentPaidOneTimeFee::where('student_id', $world->student->id)
        ->where('fee_head_id', $world->admissionHead()->id)
        ->firstOrFail();
}

it('refunds nothing when the campus has set no window', function () {
    payAdmissionFee($this->world);

    $result = $this->service->refundAll($this->world->enrollment, 'Left immediately');

    expect($result['refunded'])->toBe(0)
        ->and($result['amount'])->toBe(0.0);
});

it('refunds a charge when the child leaves inside the window', function () {
    refundWindow($this->world, 30);
    payAdmissionFee($this->world);

    $result = $this->service->refundAll(
        $this->world->enrollment,
        'Left within the month',
        null,
        Carbon::parse('2026-04-14')
    );

    expect($result['refunded'])->toBe(1)
        ->and($result['amount'])->toBe(20000.0);
});

it('refuses once the window has passed', function () {
    refundWindow($this->world, 30);
    payAdmissionFee($this->world);

    $result = $this->service->refundAll(
        $this->world->enrollment,
        'Left months later',
        null,
        Carbon::parse('2026-09-01')
    );

    expect($result['refunded'])->toBe(0);
});

it('counts the window from admission, not from the day the fee was paid', function () {
    refundWindow($this->world, 10);
    $payment = payAdmissionFee($this->world);

    // The family paid late; that must not buy them a longer window.
    $payment->update(['payment_date' => '2026-04-20']);

    $result = $this->service->refundAll(
        $this->world->enrollment,
        'Left',
        null,
        Carbon::parse('2026-04-25')
    );

    expect($result['refunded'])->toBe(0);
});

it('records who refunded it, when, and why', function () {
    refundWindow($this->world, 30);
    $payment = payAdmissionFee($this->world);

    $this->service->refundAll(
        $this->world->enrollment,
        'Family moved city',
        $this->world->school->actor->id,
        Carbon::parse('2026-04-10')
    );

    $payment->refresh();

    expect((float) $payment->refunded_amount)->toBe(20000.0)
        ->and($payment->refund_reason)->toBe('Family moved city')
        ->and($payment->refunded_by)->toBe($this->world->school->actor->id)
        ->and($payment->refunded_at->toDateString())->toBe('2026-04-10');
});

it('keeps the payment record after refunding it', function () {
    refundWindow($this->world, 30);
    $payment = payAdmissionFee($this->world);

    $this->service->refundAll($this->world->enrollment, 'Left', null, Carbon::parse('2026-04-10'));

    // The row is the record of what was taken; the refund sits beside it.
    expect(StudentPaidOneTimeFee::find($payment->id))->not->toBeNull()
        ->and((float) $payment->fresh()->amount_paid)->toBe(20000.0);
});

it('charges the admission fee again if the child returns after a refund', function () {
    refundWindow($this->world, 30);
    payAdmissionFee($this->world);

    $this->service->refundAll($this->world->enrollment, 'Left', null, Carbon::parse('2026-04-10'));

    // Readmitted: a refunded charge is no longer settled, or the refund would
    // quietly buy them a free readmission.
    expect(StudentPaidOneTimeFee::hasPaid($this->world->student->id, $this->admissionHead->id))
        ->toBeFalse();
});

it('does not charge again while the fee is still held', function () {
    refundWindow($this->world, 30);
    payAdmissionFee($this->world);

    expect(StudentPaidOneTimeFee::hasPaid($this->world->student->id, $this->admissionHead->id))
        ->toBeTrue();
});

it('refunds only part of a charge when asked to', function () {
    refundWindow($this->world, 30);
    $payment = payAdmissionFee($this->world);

    $returned = $this->service->refund($payment, 5000, 'Partial, admission processing retained');

    expect($returned)->toBe(5000.0)
        ->and($payment->fresh()->amountHeld())->toBe(15000.0)
        // Still partly held, so the charge stays settled.
        ->and(StudentPaidOneTimeFee::hasPaid($this->world->student->id, $this->admissionHead->id))
        ->toBeTrue();
});

it('never gives back more than is held', function () {
    refundWindow($this->world, 30);
    $payment = payAdmissionFee($this->world);

    $this->service->refund($payment, 20000, 'Full');
    $again = $this->service->refund($payment, 5000, 'Second attempt');

    expect($again)->toBe(0.0)
        ->and((float) $payment->fresh()->refunded_amount)->toBe(20000.0);
});

it('lists what could be given back without giving it back', function () {
    refundWindow($this->world, 30);
    payAdmissionFee($this->world);

    $refundable = $this->service->refundableFor(
        $this->world->enrollment,
        Carbon::parse('2026-04-10')
    );

    expect($refundable)->toHaveCount(1)
        ->and((float) $refundable->first()->refunded_amount)->toBe(0.0);
});
