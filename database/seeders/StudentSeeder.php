<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Gender;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

// Dev/demo data only — not part of the default production seed list.
class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Generates a moderate bulk of ~30-40 students spread across a handful
     * of classes/sections/campuses (not hundreds), with enrollment records.
     * Uses Faker-style Pakistani names for realistic data. A few students
     * get a "Left" status, and one is soft-deleted, to exercise filters and
     * reusing a soft-deleted admission number.
     */
    public function run(): void
    {
        // Get required data
        $maleGender = Gender::where('name', 'Male')->first();
        $femaleGender = Gender::where('name', 'Female')->first();
        $activeStatus = StudentStatus::where('name', 'Active')->first();
        $leftStatus = StudentStatus::where('name', 'Left')->first();
        $campuses = Campus::where('is_active', true)->take(3)->get();
        $currentSession = Session::where('is_active', true)->first();

        if (! $maleGender || ! $femaleGender || ! $activeStatus || $campuses->isEmpty() || ! $currentSession) {
            $this->command->warn('Required data missing (genders, status, campuses, or session). Skipping student seeding.');

            return;
        }

        // A handful of classes is enough demo bulk; seeding every class
        // times every section times every campus produced hundreds of rows.
        $classes = SchoolClass::where('is_active', true)->orderBy('level')->take(3)->get();

        if ($classes->isEmpty()) {
            $this->command->warn('No classes found. Skipping student seeding.');

            return;
        }

        // Student counter for unique admission numbers
        $studentCounter = Student::max('id') ?? 0;
        $studentsPerSection = 3;

        $this->command->info('Starting student seeding...');

        // One deterministic, campus-scoped "sample" student per campus with a
        // known email, so a demo login list can reference a real account.
        $this->seedSampleStudentPerCampus($campuses, $classes->first(), $currentSession, $maleGender, $activeStatus);

        foreach ($classes as $class) {
            $sections = Section::where('class_id', $class->id)->where('is_active', true)->take(1)->get();

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
                        $studentsPerSection,
                        $studentCounter
                    );

                    $studentCounter += $studentsPerSection;
                }
            }

            $this->command->info("Completed seeding for class {$class->name}");
        }

        $this->seedLeftAndRetiredStudents($classes->first(), $campuses->first(), $currentSession, $maleGender, $activeStatus, $leftStatus);

        $this->command->info('Student seeding completed!');
    }

    /**
     * One deterministic student per campus (e.g. `student.campus1@school.com`)
     * so a demo login list can point at a real, campus-scoped account.
     */
    private function seedSampleStudentPerCampus(
        Collection $campuses,
        SchoolClass $class,
        Session $session,
        Gender $gender,
        StudentStatus $activeStatus
    ): void {
        $sections = Section::where('class_id', $class->id)->where('is_active', true)->take(1)->get();
        $section = $sections->first();

        foreach ($campuses as $campus) {
            $email = "student.campus{$campus->id}@school.com";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "{$campus->name} Sample Student",
                    'username' => "studentcampus{$campus->id}",
                    'password' => Hash::make('123456'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles(['student']);

            $student = Student::firstOrCreate(
                ['admission_no' => "ADM-SAMPLE-{$campus->id}"],
                [
                    'user_id' => $user->id,
                    'student_code' => "STU-SAMPLE-{$campus->id}",
                    'dob' => now()->subYears(8)->format('Y-m-d'),
                    'gender_id' => $gender->id,
                    'student_status_id' => $activeStatus->id,
                    'admission_date' => now()->subMonths(6)->format('Y-m-d'),
                ]
            );

            if ($section) {
                $student->enrollmentRecords()->firstOrCreate(
                    ['session_id' => $session->id],
                    [
                        'class_id' => $class->id,
                        'section_id' => $section->id,
                        'campus_id' => $campus->id,
                        'admission_date' => $student->admission_date,
                        'student_status_id' => $activeStatus->id,
                        'monthly_fee' => $this->getFeeForClass($class->name),
                        'annual_fee' => $this->getFeeForClass($class->name) * 12,
                    ]
                );
            }
        }
    }

    /**
     * A couple of students with a "Left" status (filters/reports), and one
     * soft-deleted student to exercise reusing a soft-deleted admission
     * number.
     */
    private function seedLeftAndRetiredStudents(
        SchoolClass $class,
        Campus $campus,
        Session $session,
        Gender $gender,
        StudentStatus $activeStatus,
        ?StudentStatus $leftStatus
    ): void {
        if (! $leftStatus) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'left.student@school.com'],
            ['name' => 'Left Student', 'username' => 'leftstudent', 'password' => Hash::make('123456'), 'is_active' => true]
        );

        $student = Student::firstOrCreate(
            ['admission_no' => 'ADM-LEFT-0001'],
            [
                'user_id' => $user->id,
                'student_code' => 'STU-LEFT-0001',
                'dob' => now()->subYears(9)->format('Y-m-d'),
                'gender_id' => $gender->id,
                'student_status_id' => $leftStatus->id,
                'admission_date' => now()->subYear()->format('Y-m-d'),
            ]
        );

        $section = Section::where('class_id', $class->id)->where('is_active', true)->first();

        if ($section) {
            $student->enrollmentRecords()->firstOrCreate(
                ['session_id' => $session->id],
                [
                    'class_id' => $class->id,
                    'section_id' => $section->id,
                    'campus_id' => $campus->id,
                    'admission_date' => $student->admission_date,
                    'leave_date' => now()->subMonths(2)->format('Y-m-d'),
                    'student_status_id' => $leftStatus->id,
                    'monthly_fee' => $this->getFeeForClass($class->name),
                    'annual_fee' => $this->getFeeForClass($class->name) * 12,
                ]
            );
        }

        // Soft-deleted student, to exercise reusing a soft-deleted admission number.
        $retiredUser = User::firstOrCreate(
            ['email' => 'retired.student@school.com'],
            ['name' => 'Retired Student', 'username' => 'retiredstudent', 'password' => Hash::make('123456'), 'is_active' => false]
        );

        $retiredStudent = Student::firstOrCreate(
            ['admission_no' => 'ADM-RETIRED-0001'],
            [
                'user_id' => $retiredUser->id,
                'student_code' => 'STU-RETIRED-0001',
                'dob' => now()->subYears(10)->format('Y-m-d'),
                'gender_id' => $gender->id,
                'student_status_id' => $leftStatus->id,
                'admission_date' => now()->subYears(2)->format('Y-m-d'),
            ]
        );
        $retiredStudent->delete();
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
            $user->syncRoles(['student']);
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
