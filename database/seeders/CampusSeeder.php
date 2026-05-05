<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\CampusType;
use Illuminate\Database\Seeder;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds exactly 2 campuses: Main Branch, Second Branch
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
        ];

        foreach ($campuses as $campus) {
            Campus::updateOrCreate(
                ['name' => $campus['name']],
                $campus
            );
        }

        $this->command->info('Campuses seeded successfully!');
    }
}
