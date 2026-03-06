<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds subjects exactly as specified:
     * Mathamtics, English, Science, Urdu, Islamiyat, Pakistan Studies, Physics, 
     * Chemistry, Biology, Computer Science, Drawing, Social Studies, History, 
     * Islamiyat (Elective), Tarjma Quran, Psychology
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Mathematics', 'short_name' => 'Math', 'description' => 'Mathematics subject', 'is_active' => true],
            ['name' => 'English', 'short_name' => 'Eng', 'description' => 'English language and literature', 'is_active' => true],
            ['name' => 'Science', 'short_name' => 'Sci', 'description' => 'General science subject', 'is_active' => true],
            ['name' => 'Urdu', 'short_name' => 'Urdu', 'description' => 'Urdu language subject', 'is_active' => true],
            ['name' => 'Islamiyat', 'short_name' => 'Islam', 'description' => 'Islamic studies', 'is_active' => true],
            ['name' => 'Pakistan Studies', 'short_name' => 'P.St', 'description' => 'Pakistan studies and civics', 'is_active' => true],
            ['name' => 'Physics', 'short_name' => 'Phy', 'description' => 'Physics subject', 'is_active' => true],
            ['name' => 'Chemistry', 'short_name' => 'Chem', 'description' => 'Chemistry subject', 'is_active' => true],
            ['name' => 'Biology', 'short_name' => 'Bio', 'description' => 'Biology subject', 'is_active' => true],
            ['name' => 'Computer Science', 'short_name' => 'CS', 'description' => 'Computer science and IT', 'is_active' => true],
            ['name' => 'Drawing', 'short_name' => 'Art', 'description' => 'Art and drawing', 'is_active' => true],
            ['name' => 'Social Studies', 'short_name' => 'S.St', 'description' => 'Social studies and geography', 'is_active' => true],
            ['name' => 'History', 'short_name' => 'Hist', 'description' => 'History subject', 'is_active' => true],
            ['name' => 'Islamiyat (Elective)', 'short_name' => 'Islam(E)', 'description' => 'Islamic studies elective', 'is_active' => true],
            ['name' => 'Tarjma Quran', 'short_name' => 'TQ', 'description' => 'Translation of Holy Quran', 'is_active' => true],
            ['name' => 'Psychology', 'short_name' => 'Psy', 'description' => 'Psychology subject', 'is_active' => true],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['name' => $subject['name']],
                $subject
            );
        }

        $this->command->info('Subjects seeded successfully!');
    }
}
