<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed permissions
        $permissions = [
            [
                'key' => 'students.view',
                'module' => 'students',
                'label' => 'View Students',
                'description' => 'Can view student information',
            ],
            ['key' => 'students.edit', 'module' => 'students', 'label' => 'Edit Students', 'description' => 'Can edit student information'],
            ['key' => 'fees.view', 'module' => 'fees', 'label' => 'View Fees', 'description' => 'Can view fee records'],
            ['key' => 'fees.collect', 'module' => 'fees', 'label' => 'Collect Fees', 'description' => 'Can collect and manage fees'],
            ['key' => 'attendance.view', 'module' => 'attendance', 'label' => 'View Attendance', 'description' => 'Can view attendance records'],
            ['key' => 'attendance.create', 'module' => 'attendance', 'label' => 'Create Attendance', 'description' => 'Can create attendance records'],
            ['key' => 'attendance.edit', 'module' => 'attendance', 'label' => 'Edit Attendance', 'description' => 'Can edit attendance records'],
            ['key' => 'attendance.delete', 'module' => 'attendance', 'label' => 'Delete Attendance', 'description' => 'Can delete attendance records'],
            ['key' => 'attendance.mark', 'module' => 'attendance', 'label' => 'Mark Attendance', 'description' => 'Can mark attendance'],
            ['key' => 'attendance.lock', 'module' => 'attendance', 'label' => 'Lock Attendance', 'description' => 'Can lock attendance records'],
            ['key' => 'attendance.unlock', 'module' => 'attendance', 'label' => 'Unlock Attendance', 'description' => 'Can unlock attendance records'],
            ['key' => 'attendance.reports', 'module' => 'attendance', 'label' => 'View Reports', 'description' => 'Can view attendance reports'],
            ['key' => 'attendance.export', 'module' => 'attendance', 'label' => 'Export Attendance', 'description' => 'Can export attendance data'],
            ['key' => 'exams.view', 'module' => 'exams', 'label' => 'View Exams', 'description' => 'Can view exam results'],
            ['key' => 'exams.manage', 'module' => 'exams', 'label' => 'Manage Exams', 'description' => 'Can manage exams and results'],
            ['key' => 'settings.manage', 'module' => 'settings', 'label' => 'Manage Settings', 'description' => 'Can manage system settings'],
            ['key' => 'menus.manage', 'module' => 'menus', 'label' => 'Manage Menus', 'description' => 'Can create, edit, and delete menu items'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['key' => $perm['key']], $perm);
        }
    }
}
