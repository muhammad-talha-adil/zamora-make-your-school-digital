<?php

/**
 * Case 02 — the full campus → staff → student → inventory → payroll → sale
 * cycle, entirely through the real HTTP routes (never a raw insert), with
 * the Finance dashboard's own numbers checked against the exact figures
 * that cycle should produce. This is the end-to-end reconciliation the
 * manual QA pass for this feature set did by hand, kept as a repeatable
 * regression test.
 */

use App\Models\Month;
use App\Models\PayrollRunItem;
use App\Models\Permission;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\InventoryWorld;

beforeEach(function () {
    $this->world = InventoryWorld::make();
    $this->actor = $this->world->school->actor;

    // The actor already carries inventory.*/fee.*/finance.* abilities via
    // AdmissionWorld's constructor; payroll needs its own two.
    foreach (['staff.payroll.run', 'staff.payroll.approve'] as $ability) {
        Permission::firstOrCreate(['name' => $ability, 'guard_name' => 'web']);
    }
    $this->actor->givePermissionTo(['staff.payroll.run', 'staff.payroll.approve']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('reconciles payroll expense and inventory sale receivable exactly, through real routes', function () {
    // ---- Staff + payroll (salaries) ----
    $staff = StaffProfile::create([
        'user_id' => User::factory()->create()->id,
        'employee_no' => 'EMP-E2E-1',
        'campus_id' => $this->world->school->campus->id,
        'employment_type' => 'permanent',
        'hire_date' => '2026-04-01',
        'basic_salary' => 60000,
        'allowance_amount' => 5000,
        'deduction_amount' => 1000,
        'payment_method' => 'bank',
        'is_active' => true,
    ]);
    $expectedNetSalary = 60000 + 5000 - 1000; // 64,000

    $april = Month::firstOrCreate(['month_number' => 4], ['name' => 'April']);

    $this->actingAs($this->actor)->postJson(route('staff.payroll.generate'), [
        'payroll_month_id' => $april->id,
        'payroll_year' => 2026,
    ])->assertSuccessful();

    $item = PayrollRunItem::where('staff_profile_id', $staff->id)->firstOrFail();
    expect((float) $item->net_salary)->toBe((float) $expectedNetSalary);

    $this->actingAs($this->actor)->postJson(route('staff.payroll.items.pay', $item), [
        'payment_method' => 'bank',
    ])->assertSuccessful();

    // ---- Inventory buy (purchase) ----
    $this->actingAs($this->actor)->postJson(route('inventory.purchases.store'), [
        'campus_id' => $this->world->school->campus->id,
        'supplier_id' => $this->world->supplier->id,
        'purchase_date' => now()->toDateString(),
        'purchase_items' => [
            ['inventory_item_id' => $this->world->item->id, 'quantity' => 20, 'purchase_rate' => 50, 'sale_rate' => 80],
        ],
    ])->assertSuccessful();

    // ---- Inventory sale (assign to a student) ----
    $student = Student::create([
        'user_id' => User::factory()->create()->id,
        'registration_no' => 'REG-E2E-1',
        'student_code' => 'STU-E2E-1',
        'admission_no' => 'ADM-E2E-1',
        'dob' => now()->subYears(10)->toDateString(),
        'gender_id' => $this->world->school->maleGender->id,
        'student_status_id' => $this->world->school->activeStatus->id,
        'admission_date' => now()->toDateString(),
    ]);

    StudentEnrollmentRecord::create([
        'student_id' => $student->id,
        'session_id' => $this->world->school->session->id,
        'class_id' => $this->world->school->class->id,
        'section_id' => $this->world->school->section->id,
        'campus_id' => $this->world->school->campus->id,
        'admission_date' => now()->toDateString(),
        'student_status_id' => $this->world->school->activeStatus->id,
        'monthly_fee' => 0,
        'annual_fee' => 0,
    ]);

    $this->actingAs($this->actor)->post(route('inventory.student-inventory.assign'), [
        'campus_id' => $this->world->school->campus->id,
        'student_id' => $student->id,
        'items' => [
            ['inventory_item_id' => $this->world->item->id, 'quantity' => 3],
        ],
        'assigned_date' => now()->toDateString(),
    ])->assertRedirect();

    $expectedSaleAmount = 3 * 80; // quantity x sale_rate = 240

    // ---- Finance dashboard: check the real numbers it reports ----
    $response = $this->actingAs($this->actor)
        ->get(route('finance.dashboard', ['campus_id' => $this->world->school->campus->id]));

    $response->assertOk();
    $props = $response->viewData('page')['props'];

    expect((float) $props['operations_summary']['pending_salary_payable'])->toBe(0.0)
        ->and((float) $props['month_summary']['expense'])->toBe((float) $expectedNetSalary)
        ->and((float) $props['student_receivables']['inventory_open'])->toBe((float) $expectedSaleAmount);
});
