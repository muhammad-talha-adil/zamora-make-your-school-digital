<?php

namespace Database\Seeders;

use App\Models\Exam\GradeSystem;
use App\Models\Exam\GradeSystemItem;
use Illuminate\Database\Seeder;

class GradeSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default global grade system
        $gradeSystem = GradeSystem::create([
            'name' => 'Default Grade System',
            'campus_id' => null, // Global - applies to all campuses
            'session_id' => null, // Global - applies to all sessions
            'rounding_mode' => 'half_up',
            'precision' => 2,
            'is_default' => true,
        ]);

        // Create grade items (A+ to F)
        $gradeItems = [
            [
                'min_percentage' => 90.00,
                'max_percentage' => 100.00,
                'grade_letter' => 'A+',
                'grade_point' => 4.00,
                'sort_order' => 1,
            ],
            [
                'min_percentage' => 80.00,
                'max_percentage' => 89.99,
                'grade_letter' => 'A',
                'grade_point' => 3.70,
                'sort_order' => 2,
            ],
            [
                'min_percentage' => 70.00,
                'max_percentage' => 79.99,
                'grade_letter' => 'B',
                'grade_point' => 3.00,
                'sort_order' => 3,
            ],
            [
                'min_percentage' => 60.00,
                'max_percentage' => 69.99,
                'grade_letter' => 'C',
                'grade_point' => 2.00,
                'sort_order' => 4,
            ],
            [
                'min_percentage' => 50.00,
                'max_percentage' => 59.99,
                'grade_letter' => 'D',
                'grade_point' => 1.00,
                'sort_order' => 5,
            ],
            [
                'min_percentage' => 0.00,
                'max_percentage' => 49.99,
                'grade_letter' => 'F',
                'grade_point' => 0.00,
                'sort_order' => 6,
            ],
        ];

        foreach ($gradeItems as $item) {
            GradeSystemItem::create([
                'grade_system_id' => $gradeSystem->id,
                'min_percentage' => $item['min_percentage'],
                'max_percentage' => $item['max_percentage'],
                'grade_letter' => $item['grade_letter'],
                'grade_point' => $item['grade_point'],
                'sort_order' => $item['sort_order'],
            ]);
        }
    }
}
