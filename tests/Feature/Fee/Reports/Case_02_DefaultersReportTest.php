<?php

/**
 * Case 02 — the defaulters report's `min_days_overdue` filter.
 *
 * `whereRaw('DATEDIFF(NOW(), due_date) >= ?', ...)` is MySQL-only syntax;
 * SQLite (this suite's database) has no such function and would raise a SQL
 * error the moment the filter was used.
 */

use Tests\Support\FeeWorld;

beforeEach(function () {
    $this->world = FeeWorld::make();
    $this->actingAs($this->world->school->actor);
    $this->world->structureWithAllFrequencies();
    $this->items = $this->world->generate(4);

    $this->voucher = $this->world->voucherFor(4);
    $this->voucher->update(['status' => 'overdue', 'due_date' => now()->subDays(10)->toDateString()]);
});

it('loads without a SQL error', function () {
    $this->get(route('fee.reports.defaulters'))->assertOk();
});

it('filters by minimum days overdue without a database-specific function', function () {
    $response = $this->get(route('fee.reports.defaulters', ['min_days_overdue' => 5]));

    $response->assertOk();
    $ids = collect($response->viewData('page')['props']['defaulters']['data'])->pluck('id');
    expect($ids)->toContain($this->voucher->id);
});

it('excludes a voucher not yet overdue by the threshold', function () {
    $response = $this->get(route('fee.reports.defaulters', ['min_days_overdue' => 30]));

    $ids = collect($response->viewData('page')['props']['defaulters']['data'])->pluck('id');
    expect($ids)->not->toContain($this->voucher->id);
});
