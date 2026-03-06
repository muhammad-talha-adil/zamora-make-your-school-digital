<?php

namespace Database\Seeders;

use App\Models\Gender;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates guardians that can be linked to multiple students (siblings).
     * Each guardian is assigned to 2-3 students from the same family.
     */
    public function run(): void
    {
        $guardianRole = Role::where('slug', 'guardian')->first();
        
        // Get all students to assign guardians to
        $students = \App\Models\Student::with('user')->get();
        
        if ($students->isEmpty()) {
            $this->command->warn('No students found. Please run StudentSeeder first.');
            return;
        }

        // Group students by their last name to identify siblings
        $studentsByFamily = $students->groupBy(function ($student) {
            $nameParts = explode(' ', $student->user->name ?? '');
            return end($nameParts); // Use last name as family identifier
        });

        $guardianCounter = 0;
        $createdGuardians = [];

        foreach ($studentsByFamily as $familyName => $familyStudents) {
            // Take up to 3 students from each family as siblings
            $siblings = $familyStudents->take(3);
            
            if ($siblings->count() < 2) {
                continue; // Skip single students
            }

            $guardianCounter++;
            
            // Create father guardian
            $fatherName = $this->getFatherName($familyName);
            $fatherEmail = 'father.' . strtolower($familyName) . $guardianCounter . '@guardian.com';
            
            $fatherUser = User::firstOrCreate(
                ['email' => $fatherEmail],
                [
                    'name' => $fatherName,
                    'username' => 'father_' . strtolower($familyName) . $guardianCounter,
                    'password' => Hash::make('123456'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $fatherGuardian = Guardian::firstOrCreate(
                ['user_id' => $fatherUser->id],
                [
                    'cnic' => $this->generateCNIC(),
                    'phone' => $this->generatePhone(),
                ]
            );
            $createdGuardians[] = $fatherGuardian;

            // Assign guardian role
            if ($guardianRole) {
                UserRole::firstOrCreate(
                    ['user_id' => $fatherUser->id, 'role_id' => $guardianRole->id],
                    ['is_active' => true]
                );
            }

            // Create mother guardian
            $motherName = $this->getMotherName();
            $motherEmail = 'mother.' . strtolower($familyName) . $guardianCounter . '@guardian.com';
            
            $motherUser = User::firstOrCreate(
                ['email' => $motherEmail],
                [
                    'name' => $motherName,
                    'username' => 'mother_' . strtolower($familyName) . $guardianCounter,
                    'password' => Hash::make('123456'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $motherGuardian = Guardian::firstOrCreate(
                ['user_id' => $motherUser->id],
                [
                    'cnic' => $this->generateCNIC(),
                    'phone' => $this->generatePhone(),
                ]
            );
            $createdGuardians[] = $motherGuardian;

            // Assign guardian role
            if ($guardianRole) {
                UserRole::firstOrCreate(
                    ['user_id' => $motherUser->id, 'role_id' => $guardianRole->id],
                    ['is_active' => true]
                );
            }

            // Store family info for StudentGuardianSeeder
            $siblingIds = $siblings->pluck('id')->toArray();
            $createdGuardians[] = [
                'father' => $fatherGuardian,
                'mother' => $motherGuardian,
                'students' => $siblingIds,
            ];
        }

        // Store guardian family data for use in StudentGuardianSeeder
        if (!empty($createdGuardians)) {
            // Filter to only keep array entries (family data)
            $familyData = array_filter($createdGuardians, function ($item) {
                return is_array($item);
            });
            
            // Save to a temporary file or use cache for cross-seeder communication
            file_put_contents(
                storage_path('app/guardian_families.json'),
                json_encode(array_values($familyData))
            );
        }

        $this->command->info('Guardian seeding completed! Created ' . count($createdGuardians) . ' guardian records.');
    }

    /**
     * Generate father name based on family name
     */
    private function getFatherName(string $familyName): string
    {
        $prefixes = ['Mr.', 'Muhammad', ' Haji '];
        return $prefixes[array_rand($prefixes)] . ' ' . $familyName;
    }

    /**
     * Generate random mother name
     */
    private function getMotherName(): string
    {
        $names = [
            'Mrs.', 'Ms.', 
            'Fatima', 'Aisha', 'Mariam', 'Sadia', 'Nadia', 'Rashida', 
            'Shabana', 'Nasreen', 'Parveen', '抄Saima', 'Zeenat'
        ];
        return $names[array_rand($names)];
    }

    /**
     * Generate a valid CNIC number
     */
    private function generateCNIC(): string
    {
        $prefix = rand(1, 99);
        $middle = rand(1000000, 9999999);
        $suffix = rand(0, 9);
        return sprintf('%02d-%07d-%d', $prefix, $middle, $suffix);
    }

    /**
     * Generate Pakistani phone number
     */
    private function generatePhone(): string
    {
        $prefixes = ['300', '301', '302', '303', '304', '305', '306', '307', '308', '309'];
        $number = rand(1000000, 9999999);
        return '+92-' . $prefixes[array_rand($prefixes)] . '-' . $number;
    }
}
