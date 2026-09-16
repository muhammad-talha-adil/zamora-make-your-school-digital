<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for a fresh, empty launch.
     *
     * Only two kinds of thing are seeded here: pure system config (themes,
     * the sidebar menu, permissions/roles as *definitions*, the one School
     * settings row) and fixed reference lookups that have no management
     * screen anywhere in the app — a school could never add a new one
     * themselves even if they wanted to (Month, Gender, Relation, Student
     * Status, Attendance Status, Leave Types, Grade System, Reasons, Salary
     * Heads). Everything a real school enters for itself — campuses,
     * sessions, classes, sections, subjects, students, guardians, staff,
     * fee structures, holidays, inventory, transport, exam types, campus
     * types, staff departments/designations/document types — starts at
     * zero, because it has its own screen to add it through.
     *
     * Order matters due to foreign key dependencies!
     */
    public function run(): void
    {
        $this->call([
            // === Core Setup ===
            SchoolSeeder::class,
            MonthSeeder::class,

            // === Reference Data ===
            GenderSeeder::class,
            RelationSeeder::class,
            StudentStatusSeeder::class,

            // === User & Permissions (role definitions, not accounts) ===
            PermissionsSeeder::class,
            RolesSeeder::class,
            UsersSeeder::class,

            // === Attendance reference lookups ===
            AttendanceStatusesSeeder::class,
            LeaveTypesSeeder::class,

            // === Exam reference lookups ===
            GradeSystemSeeder::class,

            // === Staff reference lookups ===
            StaffLeaveTypeSeeder::class,
            SalaryHeadSeeder::class,

            // === Theme & UI ===
            ThemePalettesSeeder::class,
            ThemeSettingsSeeder::class,
            MenuSeeder::class,

            // === Other reference lookups ===
            ReasonSeeder::class,
        ]);
    }
}
