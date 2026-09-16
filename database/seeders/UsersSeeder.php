<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * A fresh launch starts with exactly two accounts — the vendor
     * (developer) and the school's own owner. Every other role is created
     * by the owner themselves once they're signing in for real, through the
     * screens this app already has for that (Staff, Settings → Users, etc.).
     */
    public function run(): void
    {
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

        $this->command->info('Users seeded successfully (developer + owner only).');
    }
}
