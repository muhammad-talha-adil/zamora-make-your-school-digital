<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\Relation;
use App\Models\Student;
use App\Models\StudentGuardian;
use Illuminate\Database\Seeder;

class StudentGuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Links students to guardians with proper relationships.
     * Each student is linked to father (primary) and mother.
     * Siblings share the same guardians.
     */
    public function run(): void
    {
        $fatherRelation = Relation::where('name', 'Father')->first();
        $motherRelation = Relation::where('name', 'Mother')->first();

        if (!$fatherRelation || !$motherRelation) {
            $this->command->warn('Relation types not found. Skipping student_guardian seeding.');
            return;
        }

        // Get all students
        $students = Student::with('user')->get();
        $guardians = Guardian::with('user')->get();

        if ($students->isEmpty() || $guardians->isEmpty()) {
            $this->command->warn('Students or guardians not found. Skipping student_guardian seeding.');
            return;
        }

        // Group students by last name to identify siblings
        $studentsByFamily = $students->groupBy(function ($student) {
            $nameParts = explode(' ', $student->user->name ?? '');
            return end($nameParts);
        });

        $linkedCount = 0;

        foreach ($studentsByFamily as $familyName => $familyStudents) {
            // Find guardians for this family
            $familyGuardians = $guardians->filter(function ($guardian) use ($familyName) {
                return stripos($guardian->user->name ?? '', $familyName) !== false;
            });

            if ($familyGuardians->isEmpty()) {
                continue;
            }

            $fatherGuardian = $familyGuardians->first();
            $motherGuardian = $familyGuardians->count() > 1 ? $familyGuardians->last() : $fatherGuardian;

            foreach ($familyStudents as $index => $student) {
                $isFatherPrimary = $index === 0;
                
                StudentGuardian::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'guardian_id' => $fatherGuardian->id,
                    ],
                    [
                        'relation_id' => $fatherRelation->id,
                        'is_primary' => $isFatherPrimary,
                    ]
                );
                $linkedCount++;

                if ($motherGuardian->id !== $fatherGuardian->id) {
                    $isMotherPrimary = $index === 1;
                    
                    StudentGuardian::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'guardian_id' => $motherGuardian->id,
                        ],
                        [
                            'relation_id' => $motherRelation->id,
                            'is_primary' => $isMotherPrimary,
                        ]
                    );
                    $linkedCount++;
                }
            }
        }

        // Handle remaining students without guardians
        $linkedStudentIds = StudentGuardian::pluck('student_id')->unique()->toArray();
        $unlinkedStudents = $students->whereNotIn('id', $linkedStudentIds);

        if ($unlinkedStudents->isNotEmpty()) {
            $defaultFather = $guardians->first();
            $defaultMother = $guardians->count() > 1 ? $guardians->get(1) : $defaultFather;

            foreach ($unlinkedStudents as $index => $student) {
                if ($defaultFather) {
                    StudentGuardian::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'guardian_id' => $defaultFather->id,
                        ],
                        [
                            'relation_id' => $fatherRelation->id,
                            'is_primary' => true,
                        ]
                    );
                    $linkedCount++;
                }

                if ($defaultMother && $defaultMother->id !== ($defaultFather?->id ?? null)) {
                    StudentGuardian::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'guardian_id' => $defaultMother->id,
                        ],
                        [
                            'relation_id' => $motherRelation->id,
                            'is_primary' => false,
                        ]
                    );
                    $linkedCount++;
                }
            }
        }

        $this->command->info("Student-guardian relationships seeded successfully! {$linkedCount} relationships created.");
    }
}
