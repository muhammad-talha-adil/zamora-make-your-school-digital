<?php

namespace Tests\Support;

use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryType;
use App\Models\Permission;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\Supplier;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * A school with an inventory catalogue: one type, one item and a supplier,
 * ready for a purchase to bring stock in.
 *
 * Builds on AdmissionWorld so the campus and session are shared. The shared
 * actor already holds every `inventory.*` ability (see
 * `AdmissionWorld::grantInventoryAbilities()`), so cases that are not about
 * authorisation do not have to think about it.
 */
class InventoryWorld
{
    /**
     * The abilities the inventory module is gated on.
     *
     * @var array<int, string>
     */
    public const ABILITIES = [
        'inventory.view', 'inventory.item.manage', 'inventory.stock.manage',
        'inventory.purchase.view', 'inventory.purchase.manage', 'inventory.purchase.delete',
        'inventory.supplier.manage', 'inventory.return.manage',
        'inventory.student.issue', 'inventory.reports',
    ];

    public AdmissionWorld $school;

    public InventoryType $type;

    public InventoryItem $item;

    public Supplier $supplier;

    private ?StaffDepartment $department = null;

    private ?StaffDesignation $designation = null;

    public function __construct()
    {
        $this->school = AdmissionWorld::make();

        $this->type = InventoryType::create([
            'campus_id' => $this->school->campus->id,
            'name' => 'Stationery',
            'is_active' => true,
        ]);

        $this->item = InventoryItem::create([
            'campus_id' => $this->school->campus->id,
            'inventory_type_id' => $this->type->id,
            'name' => 'Notebook',
            'description' => 'A5 ruled notebook',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'campus_id' => $this->school->campus->id,
            'name' => 'City Stationers',
            'is_active' => true,
        ]);
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * The stock row for this world's item at a campus, creating an empty one
     * if a purchase has not been made yet.
     */
    public function stockAt(?Campus $campus = null): InventoryStock
    {
        return InventoryStock::firstOrCreate(
            [
                'campus_id' => ($campus ?? $this->school->campus)->id,
                'inventory_item_id' => $this->item->id,
            ],
            ['quantity' => 0, 'reserved_quantity' => 0]
        );
    }

    /**
     * A second item, type and supplier at `otherCampus` — for asserting that
     * one campus's inventory stays out of another's queries.
     */
    public function itemAtOtherCampus(): InventoryItem
    {
        $type = InventoryType::create([
            'campus_id' => $this->school->otherCampus->id,
            'name' => 'Stationery (Model Town)',
            'is_active' => true,
        ]);

        return InventoryItem::create([
            'campus_id' => $this->school->otherCampus->id,
            'inventory_type_id' => $type->id,
            'name' => 'Model Town Notebook',
            'is_active' => true,
        ]);
    }

    /**
     * A user with a staff record pinned to one campus — `User::campusId()`
     * reads it, and `isCampusRestricted()` is true for anyone without a
     * school-wide role, which this actor never holds.
     *
     * @param  array<int, string>  $abilities
     */
    public function restrictedActor(Campus $campus, array $abilities = self::ABILITIES): User
    {
        $this->department ??= StaffDepartment::create(['name' => 'Inventory Test Dept', 'is_active' => true]);
        $this->designation ??= StaffDesignation::create(['name' => 'Inventory Test Role', 'is_active' => true]);

        $user = User::create([
            'name' => 'Campus Store Keeper',
            'username' => 'store.keeper.'.uniqid(),
            'email' => 'store.keeper.'.uniqid().'@school.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        if ($abilities !== []) {
            $user->givePermissionTo($abilities);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        StaffProfile::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-'.$user->id,
            'campus_id' => $campus->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employment_type' => 'permanent',
            'hire_date' => '2026-04-01',
            'basic_salary' => 30000,
            'allowance_amount' => 0,
            'deduction_amount' => 0,
            'payment_method' => 'bank',
            'is_active' => true,
        ]);

        return $user->fresh();
    }

    /**
     * A user holding a role with none of the inventory abilities — for
     * `assertForbidden` cases. Requires `$this->school->withFullRoles()`.
     */
    public function outsider(): User
    {
        return $this->school->userWithRole('driver', 'inventory.outsider.'.uniqid().'@school.test');
    }

    public function ensurePermissionsExist(): void
    {
        foreach (self::ABILITIES as $ability) {
            Permission::firstOrCreate(['name' => $ability, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
