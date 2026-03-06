<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * Order matters due to foreign key dependencies!
     */
    public function run(): void
    {
        $this->call([
            // === Core Setup ===
            SchoolSeeder::class,
            CampusTypeSeeder::class,
            CampusSeeder::class,
            SessionSeeder::class,
            MonthSeeder::class,

            // === Reference Data (must be before students/guardians) ===
            GenderSeeder::class,
            RelationSeeder::class,
            StudentStatusSeeder::class,

            // === Academic Structure ===
            SchoolClassSeeder::class,
            SectionSeeder::class,

            // === Subjects & Curriculum ===
            SubjectSeeder::class,
            ClassSubjectSeeder::class,

            // === User & Permissions (must be before student/guardian creation) ===
            PermissionsSeeder::class,
            RolesSeeder::class,
            UsersSeeder::class,

            // === Students & Guardians ===
            // StudentSeeder creates users with 'student' role
            // GuardianSeeder creates users with 'guardian' role
            StudentSeeder::class,
            GuardianSeeder::class,
            StudentGuardianSeeder::class,

            // === Holidays ===
            HolidaysSeeder::class,

            // === Attendance ===
            AttendanceStatusesSeeder::class,
            LeaveTypesSeeder::class,

            // === Exam Management ===
            ExamTypeSeeder::class,
            GradeSystemSeeder::class,

            // === Theme & UI ===
            ThemePalettesSeeder::class,
            ThemeSettingsSeeder::class,
            MenuSeeder::class,

            // === Inventory ===
            InventorySeeder::class,



            // Other Seeders
            ReasonSeeder::class,
        ]);
    }
}
