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

        // One login per remaining role that had no fixed, memorable account
        // anywhere else in the seed data — `teacher`/`accountant`/`driver`/
        // `receptionist`/`campus_admin` already exist via StaffSeeder, and
        // `student`/`guardian` are created per-family by StudentSeeder/
        // GuardianSeeder with randomised credentials, so these fill the rest
        // of the role list with something a developer can actually log in
        // as. Bare accounts, same as developer/owner/super_admin above — no
        // deeper profile is required just to sign in and see a role's menu.
        $roleOnlyAccounts = [
            ['email' => 'headteacher@school.com', 'username' => 'headteacher', 'name' => 'Head Teacher', 'role' => 'head_teacher'],
            ['email' => 'clerk@school.com', 'username' => 'clerk', 'name' => 'Office Clerk', 'role' => 'clerk'],
            ['email' => 'maid@school.com', 'username' => 'maid', 'name' => 'Support Staff', 'role' => 'maid'],
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
