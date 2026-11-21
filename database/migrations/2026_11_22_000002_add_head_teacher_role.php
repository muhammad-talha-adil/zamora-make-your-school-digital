<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Adds the Head Teacher role to a school that is already running, without
     * reseeding: a teacher's abilities plus campus-wide viewing and marks
     * verification. Safe to run twice; permissions missing from the database
     * are skipped.
     */
    public function up(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'head_teacher', 'guard_name' => 'web'],
            ['label' => 'Head Teacher', 'scope_level' => Role::SCOPE_CAMPUS, 'is_active' => true]
        );

        $permissions = Permission::whereIn('name', [
            'portal.teacher.access',
            'students.view',
            'attendance.view', 'attendance.mark', 'attendance.edit', 'attendance.reports',
            'exam.view', 'exam.paper.view', 'exam.paper.manage',
            'exam.marks.enter', 'exam.result.view',
            'academics.timetable.view',
            'staff.view.own',
            'attendance.view.campus-wide',
            'exam.marks.verify',
        ])->get();

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'head_teacher')->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
