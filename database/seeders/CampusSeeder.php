<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\CampusType;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds a mix of campuses: active, inactive, and one soft-deleted (to
     * exercise reusing a soft-deleted name).
     */
    public function run(): void
    {
        // Get or create campus types if they don't exist
        $mainType = CampusType::firstOrCreate(
            ['name' => 'Main'],
            ['is_active' => true]
        );

        $branchType = CampusType::firstOrCreate(
            ['name' => 'Branch'],
            ['is_active' => true]
        );

        $campuses = [
            [
                'name' => 'Main Branch',
                'address' => '123 Main Road, City Center, Pakistan',
                'is_active' => true,
                'campus_type_id' => $mainType->id,
            ],
            [
                'name' => 'Second Branch',
                'address' => '456 Secondary Street, District Area, Pakistan',
                'is_active' => true,
                'campus_type_id' => $branchType->id,
            ],
            [
                'name' => 'North Branch',
                'address' => '789 North Avenue, Suburb Town, Pakistan',
                'is_active' => true,
                'campus_type_id' => $branchType->id,
            ],
            [
                'name' => 'Old Branch',
                'address' => '12 Old Street, Industrial Area, Pakistan',
                'is_active' => false,
                'campus_type_id' => $branchType->id,
            ],
        ];

        foreach ($campuses as $campus) {
            Campus::updateOrCreate(
                ['name' => $campus['name']],
                $campus
            );
        }

        // A soft-deleted campus, to exercise reusing a soft-deleted name.
        $retired = Campus::firstOrCreate(
            ['name' => 'Closed Branch'],
            ['address' => '1 Closed Lane, Pakistan', 'is_active' => false, 'campus_type_id' => $branchType->id]
        );
        $retired->delete();

        $this->command->info('Campuses seeded successfully!');
    }
}
