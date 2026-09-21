<?php

/**
 * Case 02 — `print-voucher.*` used to be a bare public route: anyone who
 * could guess or increment a voucher id, with no account at all, could read a
 * child's name, class and full fee breakdown. It is now signed instead —
 * reachable without logging in, but only with a link the app itself made.
 */

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();

    // Setup happens through the service directly, as an authenticated actor
    // would — the tests themselves act as a guest, which is the point.
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->world->generate(4);
    $this->voucher = $this->world->voucherFor(4);
    $this->app['auth']->guard()->logout();
});

it('refuses an unsigned request from a guest', function () {
    $this->get(route('fee.print-voucher.single', $this->voucher->id))->assertForbidden();
});

it('lets a guest through on a validly signed link', function () {
    $signed = URL::signedRoute('fee.print-voucher.single', $this->voucher->id);

    $this->get($signed)->assertOk();
});

it('refuses a link signed for a different voucher', function () {
    // A second, real voucher — so pointing the first voucher's signed link at
    // it exercises the signature check itself, not just route-model-binding
    // failing to find a nonexistent id.
    $this->world->generate(5);
    $otherVoucher = $this->world->voucherFor(5);

    $signed = URL::signedRoute('fee.print-voucher.single', $this->voucher->id);
    $tampered = str_replace((string) $this->voucher->id, (string) $otherVoucher->id, $signed);

    $this->get($tampered)->assertForbidden();
});

it('lets a logged-in owner open the plain (unsigned) print-voucher URL directly', function () {
    // Issue #112: the `print-voucher.*` group used to run the `signed`
    // middleware for every request, so even an authenticated owner clicking
    // (or typing) the bare URL was rejected before the controller ever ran
    // its own `auth()->check()` branch. The route now only strips `auth`,
    // leaving `FeeVoucherController::authorizePrint()` to decide: a signed-in
    // user goes through the policy, an anonymous one still needs a valid
    // signature.
    $this->actingAs($this->world->school->actor);

    $this->get(route('fee.print-voucher.single', $this->voucher->id))->assertOk();
});

it('lets an owner (with no campus of their own) open the print-voucher URL for any campus', function () {
    $role = Role::firstOrCreate(
        ['name' => 'owner', 'guard_name' => 'web'],
        ['label' => 'School Owner', 'scope_level' => Role::SCOPE_SCHOOL, 'is_active' => true]
    );

    $owner = User::factory()->create();
    $owner->assignRole($role);

    $this->actingAs($owner);

    $this->get(route('fee.print-voucher.single', $this->voucher->id))->assertOk();
});

it('prints the same voucher three times on one sheet, cut apart by copy', function () {
    $signed = URL::signedRoute('fee.print-voucher.single', $this->voucher->id);

    $response = $this->get($signed);

    $response->assertOk();
    $response->assertSeeInOrder(['Bank Copy', 'School Copy', 'Student Copy']);
    // The voucher number appears in the <title> plus once per copy.
    expect(substr_count($response->getContent(), $this->voucher->voucher_no))->toBe(4);
});

it('groups batch-printed vouchers three to a sheet', function () {
    $this->actingAs($this->world->school->actor);

    $this->world->generate(5);
    $this->world->generate(6);
    $this->world->generate(7);
    $voucherIds = [
        $this->voucher->id,
        $this->world->voucherFor(5)->id,
        $this->world->voucherFor(6)->id,
        $this->world->voucherFor(7)->id,
    ];

    $response = $this->get(route('fee.print-voucher.batch', ['voucher_ids' => implode(',', $voucherIds)]));

    $response->assertOk();
    // 4 vouchers chunked by 3 -> two "sheet" groups (3 + 1).
    expect(substr_count($response->getContent(), 'class="sheet"'))->toBe(2);
    expect(substr_count($response->getContent(), 'class="copy"'))->toBe(4);
});
