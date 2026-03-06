<?php

namespace Database\Seeders;

use App\Models\CampusType;
use Illuminate\Database\Seeder;

class CampusTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Main'],
            ['name' => 'Branch'],
        ];

        foreach ($items as $item) {
            CampusType::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
