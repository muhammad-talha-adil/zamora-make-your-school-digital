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
            ['name' => 'PG',  'code' => 'CLS-001', 'description' => 'Pre-Primary / Play Group', 'is_active' => true],
            ['name' => 'KG-I', 'code' => 'CLS-002', 'description' => 'Kindergarten I', 'is_active' => true],
            ['name' => 'KG-II', 'code' => 'CLS-003', 'description' => 'Kindergarten II', 'is_active' => true],
            ['name' => 'One',  'code' => 'CLS-004', 'description' => 'Class One', 'is_active' => true],
            ['name' => 'Two',  'code' => 'CLS-005', 'description' => 'Class Two', 'is_active' => true],
            ['name' => 'Three', 'code' => 'CLS-006', 'description' => 'Class Three', 'is_active' => true],
            ['name' => 'Four', 'code' => 'CLS-007', 'description' => 'Class Four', 'is_active' => true],
            ['name' => 'Five', 'code' => 'CLS-008', 'description' => 'Class Five', 'is_active' => true],
            ['name' => 'Six',  'code' => 'CLS-009', 'description' => 'Class Six', 'is_active' => true],
            ['name' => 'Seven', 'code' => 'CLS-010', 'description' => 'Class Seven', 'is_active' => true],
            ['name' => 'Eight', 'code' => 'CLS-011', 'description' => 'Class Eight', 'is_active' => true],
            ['name' => 'Nine', 'code' => 'CLS-012', 'description' => 'Class Nine', 'is_active' => true],
            ['name' => 'Ten',  'code' => 'CLS-013', 'description' => 'Class Ten', 'is_active' => true],
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
