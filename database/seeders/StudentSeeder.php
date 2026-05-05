<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Gender;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Generates 25+ students per class per section per campus.
     * Students are linked to campus, class, and section via enrollment records.
     * Uses Faker with Pakistani locale for realistic data.
     */
    public function run(): void
    {
        // Get required data
        $studentRole = Role::where('slug', 'student')->first();
        $maleGender = Gender::where('name', 'Male')->first();
        $femaleGender = Gender::where('name', 'Female')->first();
        $activeStatus = StudentStatus::where('name', 'Active')->first();
        $campuses = Campus::where('is_active', true)->get();
        $currentSession = Session::where('is_active', true)->first();

        if (! $maleGender || ! $femaleGender || ! $activeStatus || $campuses->isEmpty() || ! $currentSession) {
            $this->command->warn('Required data missing (genders, status, campuses, or session). Skipping student seeding.');

            return;
        }

        $classes = SchoolClass::where('is_active', true)->get();

        if ($classes->isEmpty()) {
            $this->command->warn('No classes found. Skipping student seeding.');

            return;
        }

        // Student counter for unique admission numbers
        $studentCounter = Student::max('id') ?? 0;
        $studentsPerSection = 10; // Minimum students per section

        $this->command->info('Starting student seeding...');

        foreach ($classes as $class) {
            $sections = Section::where('class_id', $class->id)->where('is_active', true)->get();

            if ($sections->isEmpty()) {
                $this->command->warn("No sections found for class {$class->name}. Skipping.");

                continue;
            }

            foreach ($campuses as $campus) {
                foreach ($sections as $section) {
                    $this->seedStudentsForSection(
                        $class,
                        $section,
                        $campus,
                        $currentSession,
                        $maleGender,
                        $femaleGender,
                        $activeStatus,
                        $studentRole,
                        $studentsPerSection,
                        $studentCounter
                    );

                    $studentCounter += $studentsPerSection;
                }
            }

            $this->command->info("Completed seeding for class {$class->name}");
        }

        $this->command->info('Student seeding completed!');
    }

    /**
     * Seed students for a specific section
     */
    private function seedStudentsForSection(
        $class,
        $section,
        $campus,
        $session,
        $maleGender,
        $femaleGender,
        $activeStatus,
        $studentRole,
        int $count,
        int &$counter
    ): void {
        $studentsToCreate = [];
        $userIds = [];

        for ($i = 1; $i <= $count; $i++) {
            $counter++;
            $gender = rand(0, 1) ? $maleGender : $femaleGender;
            $isMale = $gender->name === 'Male';

            // Generate unique student code and admission number
            $studentCode = 'STU-'.str_pad($counter, 6, '0', STR_PAD_LEFT);
            $admissionNo = 'ADM-'.date('Y').'-'.str_pad($counter, 5, '0', STR_PAD_LEFT);

            // Generate realistic Pakistani names
            $firstName = $this->getRandomFirstName($isMale);
            $lastName = $this->getRandomLastName();
            $fullName = $firstName.' '.$lastName;
            $email = strtolower($firstName.'.'.$lastName.$counter.'@student.com');

            // Create user data
            $userData = [
                'name' => $fullName,
                'email' => $email,
                'username' => strtolower($firstName.'_'.$lastName.$counter),
                'password' => Hash::make('123456'),
                'is_active' => true,
                'email_verified_at' => now(),
            ];

            // Create user
            $user = User::firstOrCreate(
                ['email' => $email],
                $userData
            );
            $userIds[] = $user->id;

            // Calculate date of birth based on class (assuming 5-17 years old)
            $ageRange = $this->getAgeRangeForClass($class->name);
            $dob = now()->subYears(rand($ageRange['min'], $ageRange['max']))->subDays(rand(0, 365));

            // Student data
            $studentData = [
                'user_id' => $user->id,
                'student_code' => $studentCode,
                'admission_no' => $admissionNo,
                'dob' => $dob->format('Y-m-d'),
                'gender_id' => $gender->id,
                'b_form' => $this->generateBForm(),
                'student_status_id' => $activeStatus->id,
                'admission_date' => now()->subMonths(rand(1, 12))->format('Y-m-d'),
            ];

            $studentsToCreate[] = $studentData;

            // Assign student role
            if ($studentRole) {
                UserRole::firstOrCreate(
                    ['user_id' => $user->id, 'role_id' => $studentRole->id, 'campus_id' => $campus->id],
                    ['is_active' => true]
                );
            }
        }

        // Bulk create students
        $createdStudents = Student::upsert(
            $studentsToCreate,
            ['admission_no'],
            ['user_id', 'student_code', 'dob', 'gender_id', 'b_form', 'student_status_id', 'admission_date']
        );

        // Create enrollment records for each student
        $students = Student::whereIn('user_id', $userIds)->get();

        /** @var Student $student */
        foreach ($students as $student) {
            $student->enrollmentRecords()->create([
                'session_id' => $session->id,
                'class_id' => $class->id,
                'section_id' => $section->id,
                'campus_id' => $campus->id,
                'admission_date' => $student->admission_date,
                'student_status_id' => $activeStatus->id,
                'monthly_fee' => $this->getFeeForClass($class->name),
                'annual_fee' => $this->getFeeForClass($class->name) * 12,
            ]);
        }
    }

    /**
     * Get age range for a specific class
     */
    private function getAgeRangeForClass(string $className): array
    {
        return match ($className) {
            'PG' => ['min' => 2, 'max' => 3],
            'KG-I' => ['min' => 3, 'max' => 4],
            'KG-II' => ['min' => 4, 'max' => 5],
            'One' => ['min' => 5, 'max' => 6],
            'Two' => ['min' => 6, 'max' => 7],
            'Three' => ['min' => 7, 'max' => 8],
            'Four' => ['min' => 8, 'max' => 9],
            'Five' => ['min' => 9, 'max' => 10],
            'Six' => ['min' => 10, 'max' => 11],
            'Seven' => ['min' => 11, 'max' => 12],
            'Eight' => ['min' => 12, 'max' => 13],
            'Nine' => ['min' => 13, 'max' => 14],
            'Ten' => ['min' => 14, 'max' => 16],
            default => ['min' => 5, 'max' => 16],
        };
    }

    /**
     * Get monthly fee for a class
     */
    private function getFeeForClass(string $className): float
    {
        return match ($className) {
            'PG', 'KG-I', 'KG-II' => 3000.00,
            'One', 'Two', 'Three' => 4000.00,
            'Four', 'Five' => 5000.00,
            'Six', 'Seven', 'Eight' => 6000.00,
            'Nine', 'Ten' => 8000.00,
            default => 5000.00,
        };
    }

    /**
     * Generate random Pakistani first name
     */
    private function getRandomFirstName(bool $isMale): string
    {
        $maleNames = [
            'Muhammad', 'Ahmed', 'Ali', 'Hassan', 'Hussain', 'Omar', 'Farhan', 'Bilal',
            'Saad', 'Hamza', 'Imran', 'Kashif', 'Naveed', 'Rashid', 'Tariq', 'Zahid',
            'Akram', 'Asad', 'Faisal', 'Haroon', 'Junaid', 'Kamran', 'Liaquat', 'Majid',
            'Noman', 'Osama', 'Qamar', 'Rizwan', 'Saeed', 'Umer', 'Waqas', 'Yousuf', 'Zubair',
        ];

        $femaleNames = [
            'Ayesha', 'Fatima', 'Mariam', 'Hira', 'Sana', 'Zainab', 'Amna', 'Sofia',
            'Maryam', 'Iqra', 'Sara', 'Kainat', 'Nida', 'Rabia', 'Saima', 'Sumaira',
            'Tehreem', 'Urooj', 'Wajeeha', 'Yusra', 'Zara', 'Alina', 'Bushra', 'Fizza',
            'Hafsa', 'Javeria', 'Kinza', 'Laraib', 'Minahil', 'Nimra', 'Maryam', 'Warda',
        ];

        return $isMale ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)];
    }

    /**
     * Generate random Pakistani last name
     */
    private function getRandomLastName(): string
    {
        $lastNames = [
            'Khan', 'Ali', 'Hussain', 'Mahmood', 'Rashid', 'Malik', 'Sheikh', 'Butt',
            'Hassan', 'Ahmed', 'Saeed', 'Naeem', 'Akhtar', 'Qadir', 'Sattar', 'Haider',
            'Shah', 'Qureshi', 'Abbas', 'Baig', 'Chaudhry', 'Dar', 'Gill', 'Hussaini',
            'Iqbal', 'Jaffar', 'Kiani', 'Lodhi', 'Mughal', 'Nawaz', 'Osmani', 'Pirzada',
        ];

        return $lastNames[array_rand($lastNames)];
    }

    /**
     * Generate a valid B-Form number
     */
    private function generateBForm(): string
    {
        $prefix = rand(1, 99);
        $middle = rand(1000000, 9999999);
        $suffix = rand(0, 9);

        return sprintf('%02d-%07d-%d', $prefix, $middle, $suffix);
    }
}
