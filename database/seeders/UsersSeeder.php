<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates admin and system users.
     * Does NOT create student/guardian accounts - those are created by their respective seeders.
     */
    public function run(): void
    {
        // Create developer user
        $developer = User::firstOrCreate(
            ['email' => 'developer@web.com'],
            [
                'name' => 'Developer',
                'username' => 'developer',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $developer->syncRoles(['developer']);

        // Create Owner user
        $owner = User::firstOrCreate(
            ['email' => 'owner@school.com'],
            [
                'name' => 'School Owner',
                'username' => 'owner',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $owner->syncRoles(['owner']);

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $superAdmin->syncRoles(['super_admin']);

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin2@school.com'],
            [
                'name' => 'Administrator',
                'username' => 'administrator',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // The fourth seeded account is a campus-level administrator; there is
        // no separate `admin` role, which is why this previously assigned
        // nothing at all.
        $admin->syncRoles(['campus_admin']);

        // Fixed, memorable fallback logins for student/guardian — real ones
        // with real linked data are seeded by `TestLoginFixturesSeeder` at
        // the very end of the chain (after students/guardians exist to
        // repoint); these bare rows exist purely so the emails resolve to
        // *something* even if that later step is ever skipped.
        // head_teacher/clerk/maid get real staff profiles from `StaffSeeder`
        // instead — no bare account needed for those anymore.
        $roleOnlyAccounts = [
            ['email' => 'student.test@school.com', 'username' => 'student_test', 'name' => 'Test Student Login', 'role' => 'student'],
            ['email' => 'guardian.test@school.com', 'username' => 'guardian_test', 'name' => 'Test Guardian Login', 'role' => 'guardian'],
        ];

        foreach ($roleOnlyAccounts as $seed) {
            $user = User::firstOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'username' => $seed['username'],
                    'password' => Hash::make('123456'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$seed['role']]);
        }

        $this->command->info('Users seeded successfully!');
    }
}
