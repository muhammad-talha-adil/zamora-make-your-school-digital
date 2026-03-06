<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds classes exactly as: PG, KG-I, KG-II, One, Two, Three, Four, Five, Six, Seven, Eight, Nine, Ten
     */
    public function run(): void
    {
        $classes = [
            ['name' => 'PG', 'description' => 'Pre-Primary / Play Group', 'is_active' => true],
            ['name' => 'KG-I', 'description' => 'Kindergarten I', 'is_active' => true],
            ['name' => 'KG-II', 'description' => 'Kindergarten II', 'is_active' => true],
            ['name' => 'One', 'description' => 'Class One', 'is_active' => true],
            ['name' => 'Two', 'description' => 'Class Two', 'is_active' => true],
            ['name' => 'Three', 'description' => 'Class Three', 'is_active' => true],
            ['name' => 'Four', 'description' => 'Class Four', 'is_active' => true],
            ['name' => 'Five', 'description' => 'Class Five', 'is_active' => true],
            ['name' => 'Six', 'description' => 'Class Six', 'is_active' => true],
            ['name' => 'Seven', 'description' => 'Class Seven', 'is_active' => true],
            ['name' => 'Eight', 'description' => 'Class Eight', 'is_active' => true],
            ['name' => 'Nine', 'description' => 'Class Nine', 'is_active' => true],
            ['name' => 'Ten', 'description' => 'Class Ten', 'is_active' => true],
        ];

        foreach ($classes as $class) {
            SchoolClass::updateOrCreate(
                ['name' => $class['name']],
                $class
            );
        }

        $this->command->info('School classes seeded successfully!');
    }
}
