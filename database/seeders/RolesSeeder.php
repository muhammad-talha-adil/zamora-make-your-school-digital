<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Developer',
                'slug' => 'developer',
                'label' => 'Developer',
                'scope_level' => 'SYSTEM',
            ],
            [
                'name' => 'Owner',
                'slug' => 'owner',
                'label' => 'School Owner',
                'scope_level' => 'SCHOOL',
            ],
            [
                'name' => 'SuperAdmin',
                'slug' => 'super_admin',
                'label' => 'Super Admin',
                'scope_level' => 'SCHOOL',
            ],
            [
                'name' => 'CampusAdmin',
                'slug' => 'campus_admin',
                'label' => 'Campus Admin',
                'scope_level' => 'CAMPUS',
            ],
            [
                'name' => 'Teacher',
                'slug' => 'teacher',
                'label' => 'Teacher',
                'scope_level' => 'CAMPUS',
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'label' => 'Student',
                'scope_level' => 'SELF',
            ],
            [
                'name' => 'Guardian',
                'slug' => 'guardian',
                'label' => 'Guardian',
                'scope_level' => 'FAMILY',
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'label' => 'Accountant',
                'scope_level' => 'CAMPUS',
            ],
            [
                'name' => 'Driver',
                'slug' => 'driver',
                'label' => 'Driver',
                'scope_level' => 'CAMPUS',
            ],
            [
                'name' => 'Clerk',
                'slug' => 'clerk',
                'label' => 'Clerk',
                'scope_level' => 'CAMPUS',
            ],
            [
                'name' => 'Maid',
                'slug' => 'maid',
                'label' => 'Maid',
                'scope_level' => 'CAMPUS',
            ],
            [
                'name' => 'Receptionist',
                'slug' => 'receptionist',
                'label' => 'Receptionist',
                'scope_level' => 'CAMPUS',
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Attach permissions based on role
            match ($role->slug) {
                'developer' => $role->permissions()->sync(Permission::pluck('id')->toArray()),
                'owner', 'super_admin' => $role->permissions()->sync(Permission::where('key', '!=', 'menus.manage')->pluck('id')->toArray()),
                'campus_admin' => $role->permissions()->sync(Permission::whereIn('key', [
                    'students.view', 'students.edit', 'fees.view', 'fees.collect',
                    'attendance.view', 'attendance.mark', 'exams.view', 'exams.manage',
                    'settings.manage',
                ])->pluck('id')->toArray()),
                'accountant' => $role->permissions()->sync(Permission::whereIn('key', ['fees.view', 'fees.collect'])->pluck('id')->toArray()),
                'teacher' => $role->permissions()->sync(Permission::whereIn('key', ['attendance.view', 'attendance.mark', 'exams.view'])->pluck('id')->toArray()),
                'guardian', 'student' => $role->permissions()->sync(Permission::whereIn('key', ['students.view', 'attendance.view', 'exams.view'])->pluck('id')->toArray()),
                default => null,
            };
        }
    }
}
