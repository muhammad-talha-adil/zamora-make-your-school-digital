<?php

namespace Database\Seeders;

use App\Models\CampusType;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
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
            ['name' => 'Online Campus'],
        ];

        foreach ($items as $item) {
            CampusType::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }

        // A soft-deleted type, to exercise reusing a soft-deleted name.
        $retired = CampusType::firstOrCreate(['name' => 'Retired Campus Type']);
        $retired->delete();
    }
}
