<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
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
            ['name' => 'Mathematics', 'short_name' => 'Math', 'code' => 'MATH', 'is_active' => true],
            ['name' => 'English', 'short_name' => 'Eng', 'code' => 'ENG', 'is_active' => true],
            ['name' => 'Science', 'short_name' => 'Sci', 'code' => 'SCI', 'is_active' => true],
            ['name' => 'Urdu', 'short_name' => 'Urdu', 'code' => 'URD', 'is_active' => true],
            ['name' => 'Islamiyat', 'short_name' => 'Islam', 'code' => 'ISL', 'is_active' => true],
            ['name' => 'Pakistan Studies', 'short_name' => 'P.St', 'code' => 'PST', 'is_active' => true],
            ['name' => 'Physics', 'short_name' => 'Phy', 'code' => 'PHY', 'is_active' => true],
            ['name' => 'Chemistry', 'short_name' => 'Chem', 'code' => 'CHEM', 'is_active' => true],
            ['name' => 'Biology', 'short_name' => 'Bio', 'code' => 'BIO', 'is_active' => true],
            ['name' => 'Computer Science', 'short_name' => 'CS', 'code' => 'CS', 'is_active' => true],
            ['name' => 'Drawing', 'short_name' => 'Art', 'code' => 'ART', 'is_active' => true],
            ['name' => 'Social Studies', 'short_name' => 'S.St', 'code' => 'SST', 'is_active' => true],
            ['name' => 'History', 'short_name' => 'Hist', 'code' => 'HIST', 'is_active' => true],
            ['name' => 'Islamiyat (Elective)', 'short_name' => 'Islam(E)', 'code' => 'ISLE', 'is_active' => true],
            ['name' => 'Tarjma Quran', 'short_name' => 'TQ', 'code' => 'TQ', 'is_active' => true],
            ['name' => 'Psychology', 'short_name' => 'Psy', 'code' => 'PSY', 'is_active' => true],
            ['name' => 'Home Economics', 'short_name' => 'H.Eco', 'code' => 'HECO', 'is_active' => false],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['name' => $subject['name']],
                $subject
            );
        }

        // A soft-deleted subject, to exercise reusing a soft-deleted name.
        $retired = Subject::firstOrCreate(
            ['name' => 'Discontinued Subject'],
            ['short_name' => 'Disc', 'code' => 'DISC', 'is_active' => false]
        );
        $retired->delete();

        $this->command->info('Subjects seeded successfully!');
    }
}
