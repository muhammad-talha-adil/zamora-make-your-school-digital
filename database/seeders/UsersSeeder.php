<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
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

        $developerRole = Role::where('slug', 'developer')->first();
        if ($developerRole) {
            UserRole::firstOrCreate(
                ['user_id' => $developer->id, 'role_id' => $developerRole->id, 'campus_id' => null],
                ['is_active' => true]
            );
        }

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

        $ownerRole = Role::where('slug', 'owner')->first();
        if ($ownerRole) {
            UserRole::firstOrCreate(
                ['user_id' => $owner->id, 'role_id' => $ownerRole->id, 'campus_id' => null],
                ['is_active' => true]
            );
        }

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

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            UserRole::firstOrCreate(
                ['user_id' => $superAdmin->id, 'role_id' => $superAdminRole->id, 'campus_id' => null],
                ['is_active' => true]
            );
        }

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

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            UserRole::firstOrCreate(
                ['user_id' => $admin->id, 'role_id' => $adminRole->id, 'campus_id' => null],
                ['is_active' => true]
            );
        }

        $this->command->info('Users seeded successfully!');
    }
}
