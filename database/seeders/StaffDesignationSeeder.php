<?php

namespace Database\Seeders;

use App\Models\StaffDesignation;
use Illuminate\Database\Seeder;

class StaffDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            ['name' => 'Principal', 'description' => 'Head of school operations', 'role' => 'campus_admin'],
            ['name' => 'Accounts Officer', 'description' => 'Handles fee and finance records', 'role' => 'accountant'],
            ['name' => 'Teacher', 'description' => 'Academic teaching staff', 'role' => 'teacher'],
            ['name' => 'Transport Supervisor', 'description' => 'Coordinates route and vehicle operations', 'role' => null],
            ['name' => 'Driver', 'description' => 'Assigned school vehicle driver', 'role' => 'driver'],
            ['name' => 'Receptionist', 'description' => 'Front desk and visitor coordination', 'role' => 'receptionist'],
            ['name' => 'Clerk', 'description' => 'Office and records support', 'role' => 'clerk'],
            ['name' => 'Head Teacher', 'description' => 'Senior teacher who also verifies marks and manages the timetable', 'role' => 'head_teacher'],
            ['name' => 'Support Staff', 'description' => 'General facility and cleaning support', 'role' => 'maid'],
        ];

        foreach ($designations as $designation) {
            StaffDesignation::updateOrCreate(
                ['name' => $designation['name']],
                $designation + ['is_active' => true]
            );
        }
    }
}
