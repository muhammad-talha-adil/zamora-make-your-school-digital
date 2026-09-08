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

        $this->command->info('Users seeded successfully!');
    }
}
