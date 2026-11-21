<?php

namespace Database\Seeders;

use App\Models\Exam\ExamType;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
class ExamTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $examTypes = [
            [
                'name' => 'Midterm Exam',
                'is_active' => true,
            ],
            [
                'name' => 'Final Exam',
                'is_active' => true,
            ],
            [
                'name' => 'Quiz',
                'is_active' => true,
            ],
            [
                'name' => 'Unit Test',
                'is_active' => true,
            ],
            [
                'name' => 'Practical Exam',
                'is_active' => true,
            ],
            [
                'name' => 'Supplementary Exam',
                'is_active' => true,
            ],
        ];

        foreach ($examTypes as $type) {
            ExamType::create($type);
        }
    }
}
