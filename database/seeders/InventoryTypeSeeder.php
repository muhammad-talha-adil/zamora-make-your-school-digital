<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\InventoryType;
use Illuminate\Database\Seeder;

class InventoryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campuses = Campus::all();

        if ($campuses->isEmpty()) {
            return;
        }

        foreach ($campuses as $index => $campus) {
            $inventoryTypes = $this->getInventoryTypes();

            foreach ($inventoryTypes as $type) {
                InventoryType::firstOrCreate(
                    [
                        'campus_id' => $campus->id,
                        'name' => $type['name'],
                    ],
                    [
                        'is_active' => $type['is_active'],
                    ]
                );
            }
        }
    }

    private function getInventoryTypes(): array
    {
        return [
            [
                'name' => 'Uniform',
                'is_active' => true,
            ],
            [
                'name' => 'Books',
                'is_active' => true,
            ],
            [
                'name' => 'Stationery',
                'is_active' => true,
            ],
            [
                'name' => 'Sports Equipment',
                'is_active' => true,
            ],
            [
                'name' => 'Lab Equipment',
                'is_active' => true,
            ],
            [
                'name' => 'Electronics',
                'is_active' => true,
            ],
            [
                'name' => 'Furniture',
                'is_active' => true,
            ],
            [
                'name' => 'Cleaning Supplies',
                'is_active' => true,
            ],
            [
                'name' => 'First Aid',
                'is_active' => true,
            ],
            [
                'name' => 'Miscellaneous',
                'is_active' => true,
            ],
        ];
    }
}
