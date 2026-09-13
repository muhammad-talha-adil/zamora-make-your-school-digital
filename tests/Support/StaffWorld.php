<?php

namespace Tests\Support;

use App\Models\Campus;
use App\Models\Permission;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * A school with people working in it.
 *
 * Builds on AdmissionWorld so the campuses, classes and lookups are shared, and
 * adds only what the staff module needs.
 */
class StaffWorld
{
    public AdmissionWorld $school;

    public StaffDepartment $department;

    public StaffDesignation $teacherPost;

    public StaffDesignation $driverPost;

    /**
     * The abilities the staff module is gated on.
     *
     * @var array<int, string>
     */
    public const ABILITIES = [
        'staff.view', 'staff.view.own', 'staff.manage', 'staff.delete',
        'staff.department.manage', 'staff.salary.manage',
        'staff.payroll.run', 'staff.payroll.approve',
        'staff.attendance.view', 'staff.attendance.mark',
    ];

    public function __construct()
    {
        $this->school = AdmissionWorld::make();

        $this->department = StaffDepartment::create(['name' => 'Teaching', 'is_active' => true]);
        $this->teacherPost = StaffDesignation::create(['name' => 'Teacher', 'is_active' => true]);
        $this->driverPost = StaffDesignation::create(['name' => 'Driver', 'is_active' => true]);

        $this->ensureAbilities();

        // The shared actor holds every staff ability, so cases that are not
        // about authorisation do not have to think about it.
        $this->school->actor->givePermissionTo(self::ABILITIES);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function make(): self
    {
        return new self;
    }

    /**
     * Somebody who works here, with a staff record and a campus.
     *
     * The campus matters: `User::campusId()` reads it, and it is what every
     * other module's campus rule is built on.
     *
     * @param  array<int, string>  $abilities
     */
    public function person(
        string $name,
        array $abilities = [],
        ?Campus $campus = null,
        ?float $salary = 50000
    ): StaffProfile {
        $user = User::create([
            'name' => $name,
            'username' => strtolower(str_replace(' ', '', $name)).'.'.uniqid(),
            'email' => strtolower(str_replace(' ', '', $name)).'.'.uniqid().'@staff.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        if ($abilities !== []) {
            $user->givePermissionTo($abilities);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return StaffProfile::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-'.$user->id,
            'campus_id' => ($campus ?? $this->school->campus)->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->teacherPost->id,
            'employment_type' => 'permanent',
            'hire_date' => '2026-04-01',
            'basic_salary' => $salary,
            'allowance_amount' => 0,
            'deduction_amount' => 0,
            'payment_method' => 'bank',
            'is_active' => true,
        ]);
    }

    /**
     * A post that belongs to no campus — the principal, the accountant.
     */
    public function schoolWidePerson(string $name, array $abilities = []): StaffProfile
    {
        $profile = $this->person($name, $abilities);
        $profile->update(['campus_id' => null]);

        return $profile->fresh();
    }

    /**
     * Makes sure the abilities exist before anybody is given one.
     */
    private function ensureAbilities(): void
    {
        foreach (self::ABILITIES as $ability) {
            Permission::firstOrCreate(['name' => $ability, 'guard_name' => 'web']);
        }
    }
}
