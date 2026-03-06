<?php

namespace Database\Seeders;

use App\Models\Exam\ExamType;
use Illuminate\Database\Seeder;

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
                'short_name' => 'Midterm',
                'is_active' => true,
            ],
            [
                'name' => 'Final Exam',
                'short_name' => 'Final',
                'is_active' => true,
            ],
            [
                'name' => 'Quiz',
                'short_name' => 'Quiz',
                'is_active' => true,
            ],
            [
                'name' => 'Unit Test',
                'short_name' => 'Unit',
                'is_active' => true,
            ],
            [
                'name' => 'Practical Exam',
                'short_name' => 'Practical',
                'is_active' => true,
            ],
            [
                'name' => 'Supplementary Exam',
                'short_name' => 'Supp',
                'is_active' => true,
            ],
        ];

        foreach ($examTypes as $type) {
            ExamType::create($type);
        }
    }
}
