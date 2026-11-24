<?php

/**
 * Round 01q — payment-method report totals, the reversed and printed receipt,
 * campus reach on student pages, transport dues merged into an open voucher,
 * phone normalisation, fee root redirect and the QA inventory seeder.
 */

use App\Console\Commands\NormalizePhones;
use App\Enums\Fee\VoucherItemSource;
use App\Enums\Fee\VoucherStatus;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Guardian;
use App\Models\InventoryStock;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TransportRoute;
use App\Models\TransportStudentAssignment;
use App\Models\User;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->world->generate(4);
    $this->campusA = $this->world->school->campus;
    $this->campusB = $this->world->school->otherCampus;
});

function round01qAssignTransport(object $test): TransportStudentAssignment
{
    $route = TransportRoute::create(['campus_id' => $test->campusA->id, 'name' => 'Route-Q', 'monthly_fee' => 1000, 'is_active' => true]);

    return TransportStudentAssignment::create([
        'student_id' => $test->world->student->id,
        'student_enrollment_record_id' => $test->world->enrollment->id,
        'campus_id' => $test->campusA->id,
        'transport_route_id' => $route->id,
        'monthly_fee' => 1000,
        'effective_from' => now()->subDay()->toDateString(),
        'status' => 'active',
        'generate_dues' => true,
    ]);
}

function round01qPayment(object $test, string $method, float $amount, string $receipt): FeePayment
{
    return FeePayment::create([
        'receipt_no' => $receipt,
        'student_id' => $test->world->student->id,
        'student_enrollment_record_id' => $test->world->enrollment->id,
        'campus_id' => $test->campusA->id,
        'payment_date' => '2026-04-10',
        'payment_method' => $method,
        'received_amount' => $amount,
        'allocated_amount' => $amount,
        'excess_amount' => 0,
        'remaining_unallocated_amount' => 0,
        'status' => 'posted',
        'received_by' => $test->world->school->actor->id,
    ]);
}

it('sends the payment-method report numbers, not strings, so totals add up', function () {
    round01qPayment($this, 'cash', 37730, 'R-1');
    round01qPayment($this, 'bank', 5400, 'R-2');
    round01qPayment($this, 'cheque', 9000, 'R-3');

    $rows = $this->get(route('fee.reports.payment-methods'))->viewData('page')['props']['byMethod'];

    expect(collect($rows)->every(fn ($row) => is_float($row['total']) && is_int($row['count'])))->toBeTrue()
        ->and(collect($rows)->sum('total'))->toBe(52130.0);
});

it('lists each voucher once on a receipt, shows only what was paid, and keeps the balance after reversal', function () {
    $voucher = $this->world->voucherFor(4);
    $items = FeeVoucherItem::where('fee_voucher_id', $voucher->id)->orderBy('id')->get();
    $first = $items->first();

    $lateFee = FeeVoucherItem::create([
        'fee_voucher_id' => $voucher->id,
        'description' => 'Late fee',
        'source_type' => VoucherItemSource::MANUAL,
        'amount' => 200,
        'discount_amount' => 0,
        'fine_amount' => 0,
        'net_amount' => 200,
    ]);

    $payment = round01qPayment($this, 'cash', 1200, 'R-4');
    $payment->allocations()->create(['fee_voucher_id' => $voucher->id, 'fee_voucher_item_id' => $first->id, 'allocated_amount' => 1000, 'allocation_date' => '2026-04-10']);
    $payment->allocations()->create(['fee_voucher_id' => $voucher->id, 'fee_voucher_item_id' => $lateFee->id, 'allocated_amount' => 200, 'allocation_date' => '2026-04-10']);

    foreach (['fee.payments.receipt', 'fee.payments.print-receipt', 'fee.payments.show'] as $routeName) {
        $this->get(route($routeName, $payment))->assertInertia(fn ($page) => $page
            ->has('payment.allocations', 1)
            ->where('payment.allocations.0.allocated_amount', 1000)
            ->has('payment.items_summary', 1)
            ->where('payment.items_summary.0.amount', 1000));
    }

    $voucher->update(['paid_amount' => 1000, 'balance_amount' => 9000, 'status' => VoucherStatus::PARTIAL]);

    $this->post(route('fee.payments.reverse', $payment), ['reason' => 'wrong'])->assertRedirect();

    $voucher->refresh();
    $expected = (float) $voucher->balance_amount;
    expect($expected)->toBeGreaterThan(0);

    $this->get(route('fee.payments.receipt', $payment))->assertInertia(fn ($page) => $page
        ->where('payment.status', 'reversed')
        ->where('payment.remaining_balance', fn ($balance) => (float) $balance === $expected)
        ->has('payment.allocations', 1)
        ->has('payment.items_summary', 1));
});

it('shows a reversed receipt with no snapshot the student open balance', function () {
    $voucher = $this->world->voucherFor(4);
    $voucher->update(['balance_amount' => 9000, 'status' => VoucherStatus::UNPAID]);
    $payment = round01qPayment($this, 'cash', 500, 'R-5');
    $payment->update(['status' => 'reversed']);

    $this->get(route('fee.payments.show', $payment))->assertInertia(fn ($page) => $page
        ->where('payment.remaining_balance', fn ($balance) => (float) $balance >= 9000));
});

it('blocks student, ledger and edit pages of another campus even for the owner viewing a campus', function () {
    $owner = $this->world->school->actor;
    $owner->syncRoles([Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web'], ['label' => 'Owner', 'scope_level' => Role::SCOPE_SYSTEM, 'is_active' => true])]);
    $owner->givePermissionTo(Permission::whereIn('name', ['students.view', 'students.edit', 'fee.view'])->get());
    $this->actingAs($owner->fresh());

    $student = $this->world->student;
    $student->update(['user_id' => User::create(['name' => 'Kid', 'username' => 'kid.q', 'email' => 'kid.q@test.test', 'password' => bcrypt('x'), 'is_active' => true])->id]);

    $this->withSession(['viewing_campus_id' => $this->campusA->id]);
    $this->get(route('students.show', $student))->assertOk();
    $this->get(route('fee.ledger.show', $student))->assertOk();

    $this->withSession(['viewing_campus_id' => $this->campusB->id]);
    $this->get(route('students.show', $student))->assertForbidden();
    $this->get(route('students.edit', $student))->assertForbidden();
    $this->get(route('fee.ledger.show', $student))->assertForbidden();
});

it('adds transport to the month voucher that already exists, once', function () {
    round01qAssignTransport($this);
    $this->world->school->actor->givePermissionTo(Permission::firstOrCreate(['name' => 'transport.assignment.manage', 'guard_name' => 'web']));
    $voucher = $this->world->voucherFor(4);
    $before = (float) $voucher->net_amount;
    $payload = ['month_id' => $this->world->monthId(4), 'year' => 2026];

    $this->postJson(route('transport.generate-dues'), $payload)
        ->assertOk()->assertJsonPath('merged_count', 1);

    $voucher->refresh();
    expect((float) $voucher->net_amount)->toBe($before + 1000.0)
        ->and($voucher->items()->where('source_module', 'transport')->count())->toBe(1);

    $this->postJson(route('transport.generate-dues'), $payload)->assertOk()->assertJsonPath('merged_count', 0);

    expect($voucher->fresh()->items()->where('source_module', 'transport')->count())->toBe(1)
        ->and(TransportStudentAssignment::count())->toBe(1);
});

it('creates no transport due when that month voucher is already paid', function () {
    round01qAssignTransport($this);
    $this->world->school->actor->givePermissionTo(Permission::firstOrCreate(['name' => 'transport.assignment.manage', 'guard_name' => 'web']));
    $this->world->voucherFor(4)->update(['status' => VoucherStatus::PAID]);

    $this->postJson(route('transport.generate-dues'), ['month_id' => $this->world->monthId(4), 'year' => 2026])
        ->assertOk()
        ->assertJsonPath('blocked_count', 1)
        ->assertJsonPath('generated_count', 0);
});

it('normalises stored phone numbers and is safe to repeat', function () {
    $guardian = Guardian::create(['phone' => '923331112233']);

    $this->artisan('phones:normalize')->assertSuccessful();
    expect($guardian->fresh()->phone)->toBe('03331112233');

    $this->artisan('phones:normalize')->expectsOutputToContain('already uniform')->assertSuccessful();
    expect(NormalizePhones::normalise('+92 333 1112233'))->toBe('0333-1112233')
        ->and(NormalizePhones::normalise('12345'))->toBeNull();
});

it('redirects /fee to the fee dashboard', function () {
    $this->get('/fee')->assertRedirect('/fee/dashboard');
    $this->get(route('fee.dashboard'))->assertOk();
});

it('seeds the QA inventory chain once', function () {
    $this->artisan('qa:seed-inventory', ['--campus' => $this->campusA->id])->assertSuccessful();
    $this->artisan('qa:seed-inventory', ['--campus' => $this->campusA->id])->assertSuccessful();

    expect(InventoryStock::where('campus_id', $this->campusA->id)->sum('quantity'))->toBe(100);
});
