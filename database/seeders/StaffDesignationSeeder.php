<?php

namespace Database\Seeders;

use App\Models\StaffDesignation;
use Illuminate\Database\Seeder;

class StaffDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            ['name' => 'Principal', 'description' => 'Head of school operations'],
            ['name' => 'Accounts Officer', 'description' => 'Handles fee and finance records'],
            ['name' => 'Teacher', 'description' => 'Academic teaching staff'],
            ['name' => 'Transport Supervisor', 'description' => 'Coordinates route and vehicle operations'],
            ['name' => 'Driver', 'description' => 'Assigned school vehicle driver'],
            ['name' => 'Receptionist', 'description' => 'Front desk and visitor coordination'],
            ['name' => 'Clerk', 'description' => 'Office and records support'],
        ];

        foreach ($designations as $designation) {
            StaffDesignation::updateOrCreate(
                ['name' => $designation['name']],
                $designation + ['is_active' => true]
            );
        }
    }
}
