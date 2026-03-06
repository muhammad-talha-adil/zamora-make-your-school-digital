<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates 2-4 sections per class (A, B, C, D).
     * Sections will be displayed as Class-Section (e.g., Two-A, Eight-C)
     * by combining class name with section name in the UI or accessor.
     */
    public function run(): void
    {
        $classes = SchoolClass::all();

        if ($classes->isEmpty()) {
            $this->command->warn('No classes found. Please run SchoolClassSeeder first.');
            return;
        }

        // Define section names (up to 4 per class)
        $sectionNames = ['A', 'B', 'C', 'D'];

        foreach ($classes as $class) {
            // Determine number of sections based on class
            // PG, KG-I, KG-II: 2 sections
            // Class 1-5: 3 sections
            // Class 6-10: 4 sections
            $numSections = match ($class->name) {
                'PG', 'KG-I', 'KG-II' => 2,
                'One', 'Two', 'Three', 'Four', 'Five' => 3,
                default => 4,
            };

            for ($i = 0; $i < $numSections; $i++) {
                $sectionName = $sectionNames[$i];
                
                Section::updateOrCreate(
                    [
                        'name' => $sectionName,
                        'class_id' => $class->id,
                    ],
                    [
                        'description' => "Section {$sectionName} for Class {$class->name}",
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Sections seeded successfully!');
    }
}
