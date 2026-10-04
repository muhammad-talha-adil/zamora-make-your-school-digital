<?php

namespace Database\Seeders;

use App\Models\StaffDepartment;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
class StaffDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Administration', 'description' => 'School administration and operations'],
            ['name' => 'Accounts', 'description' => 'Finance, billing, and audit support'],
            ['name' => 'Academics', 'description' => 'Teaching and academic coordination'],
            ['name' => 'Transport', 'description' => 'Transport routes, vehicles, and drivers'],
            ['name' => 'Support', 'description' => 'Reception, office support, and facility operations'],
            ['name' => 'Discontinued Department', 'description' => 'No longer in use', 'is_active' => false],
        ];

        foreach ($departments as $department) {
            StaffDepartment::updateOrCreate(
                ['name' => $department['name']],
                $department + ['is_active' => true]
            );
        }
    }
}
