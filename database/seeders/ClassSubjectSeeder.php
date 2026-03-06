<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Assigns subjects by class level. Each section of a class receives the same subject set.
     * 
     * Curriculum mapping:
     * - PG/KG: English, Urdu, Mathematics, Drawing, Islamiyat
     * - Class 1-3: add Science, Social Studies
     * - Class 4-5: add Pakistan Studies
     * - Class 6-8: add Computer Science + History
     * - Class 9-10: Mathematics, English, Urdu, Physics, Chemistry, Biology, Pakistan Studies, Islamiyat, Computer Science
     */
    public function run(): void
    {
        $classes = SchoolClass::all();
        $subjects = Subject::all();

        if ($classes->isEmpty() || $subjects->isEmpty()) {
            $this->command->warn('Classes or subjects not found. Skipping class_subject seeding.');
            return;
        }

        // Define subjects per class level
        $classSubjects = [
            // PG and KG levels
            'PG' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat'],
            'KG-I' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat'],
            'KG-II' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat'],
            
            // Class 1-3 (Primary)
            'One' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat', 'Science', 'Social Studies'],
            'Two' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat', 'Science', 'Social Studies'],
            'Three' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat', 'Science', 'Social Studies'],
            
            // Class 4-5 (Upper Primary)
            'Four' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat', 'Science', 'Social Studies', 'Pakistan Studies'],
            'Five' => ['English', 'Urdu', 'Mathematics', 'Drawing', 'Islamiyat', 'Science', 'Social Studies', 'Pakistan Studies'],
            
            // Class 6-8 (Middle School)
            'Six' => ['English', 'Urdu', 'Mathematics', 'Islamiyat', 'Science', 'Social Studies', 'Pakistan Studies', 'Computer Science', 'History'],
            'Seven' => ['English', 'Urdu', 'Mathematics', 'Islamiyat', 'Science', 'Social Studies', 'Pakistan Studies', 'Computer Science', 'History'],
            'Eight' => ['English', 'Urdu', 'Mathematics', 'Islamiyat', 'Science', 'Social Studies', 'Pakistan Studies', 'Computer Science', 'History'],
            
            // Class 9-10 (Secondary School)
            'Nine' => ['English', 'Urdu', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Pakistan Studies', 'Islamiyat', 'Computer Science'],
            'Ten' => ['English', 'Urdu', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Pakistan Studies', 'Islamiyat', 'Computer Science'],
        ];

        foreach ($classes as $class) {
            $subjectNames = $classSubjects[$class->name] ?? ['English', 'Urdu', 'Mathematics', 'Islamiyat'];

            foreach ($subjectNames as $subjectName) {
                $subject = $subjects->where('name', $subjectName)->first();

                if ($subject) {
                    DB::table('class_subject')->updateOrInsert(
                        [
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                } else {
                    $this->command->warn("Subject '{$subjectName}' not found in database.");
                }
            }
        }

        $this->command->info('Class subjects seeded successfully!');
    }
}
