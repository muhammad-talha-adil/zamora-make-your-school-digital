<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Role;
use App\Models\Staff\SalaryHead;
use App\Models\Staff\StaffDocument;
use App\Models\Staff\StaffDocumentType;
use App\Models\Staff\StaffEmploymentPeriod;
use App\Models\Staff\StaffQualification;
use App\Models\Staff\StaffSalaryComponent;
use App\Models\Staff\StaffSubject;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

// Dev/demo data only — not part of the default production seed list.
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = Campus::where('is_active', true)->get();
        $roles = Role::get()->keyBy('name');
        $departments = StaffDepartment::get()->keyBy('name');
        $designations = StaffDesignation::get()->keyBy('name');

        if ($campuses->isEmpty() || $departments->isEmpty() || $designations->isEmpty()) {
            $this->command?->warn('StaffSeeder skipped because campuses, departments, or designations are missing.');

            return;
        }

        $staffSeed = [
            [
                'name' => 'Adeel Hussain',
                'email' => 'principal@school.com',
                'username' => 'principal',
                'employee_no' => 'EMP-00001',
                'campus_index' => 0,
                'department' => 'Administration',
                'designation' => 'Principal',
                'role' => 'campus_admin',
                'employment_type' => 'permanent',
                'basic_salary' => 150000,
                'allowance_amount' => 25000,
                'deduction_amount' => 5000,
                'payment_method' => 'bank',
                'bank_name' => 'HBL',
                'account_no' => 'PK00HBL0000000001',
                'qualification' => ['title' => 'M.Ed Educational Leadership', 'institution' => 'University of Punjab', 'year_completed' => 2012, 'grade' => 'A'],
                'subjects' => [],
            ],
            [
                'name' => 'Sana Tariq',
                'email' => 'accounts@school.com',
                'username' => 'accounts',
                'employee_no' => 'EMP-00002',
                'campus_index' => 0,
                'department' => 'Accounts',
                'designation' => 'Accounts Officer',
                'role' => 'accountant',
                'employment_type' => 'permanent',
                'basic_salary' => 75000,
                'allowance_amount' => 12000,
                'deduction_amount' => 2500,
                'payment_method' => 'bank',
                'bank_name' => 'UBL',
                'account_no' => 'PK00UBL0000000002',
                'qualification' => ['title' => 'B.Com', 'institution' => 'Punjab College of Commerce', 'year_completed' => 2015, 'grade' => 'B+'],
                'subjects' => [],
            ],
            [
                'name' => 'Kashif Ali',
                'email' => 'teacher1@school.com',
                'username' => 'teacher1',
                'employee_no' => 'EMP-00003',
                'campus_index' => 0,
                'department' => 'Academics',
                'designation' => 'Teacher',
                'role' => 'teacher',
                'employment_type' => 'permanent',
                'basic_salary' => 60000,
                'allowance_amount' => 8000,
                'deduction_amount' => 2000,
                'payment_method' => 'bank',
                'bank_name' => 'Meezan Bank',
                'account_no' => 'PK00MZN0000000003',
                'qualification' => ['title' => 'B.Ed', 'institution' => 'Allama Iqbal Open University', 'year_completed' => 2018, 'grade' => 'A'],
                'subjects' => ['Mathematics', 'Science'],
            ],
            [
                'name' => 'Rashid Khan',
                'email' => 'driver1@school.com',
                'username' => 'driver1',
                'employee_no' => 'EMP-00004',
                'campus_index' => 0,
                'department' => 'Transport',
                'designation' => 'Driver',
                'role' => 'driver',
                'employment_type' => 'contract',
                'basic_salary' => 45000,
                'allowance_amount' => 5000,
                'deduction_amount' => 1000,
                'payment_method' => 'cash',
                'bank_name' => null,
                'account_no' => null,
                'qualification' => ['title' => 'Matriculation', 'institution' => 'Govt High School', 'year_completed' => 2005, 'grade' => 'C'],
                'subjects' => [],
            ],
            [
                'name' => 'Hina Malik',
                'email' => 'reception@school.com',
                'username' => 'reception',
                'employee_no' => 'EMP-00005',
                'campus_index' => min(1, max(0, $campuses->count() - 1)),
                'department' => 'Support',
                'designation' => 'Receptionist',
                'role' => 'receptionist',
                'employment_type' => 'permanent',
                'basic_salary' => 40000,
                'allowance_amount' => 4000,
                'deduction_amount' => 1000,
                'payment_method' => 'bank',
                'bank_name' => 'Bank Alfalah',
                'account_no' => 'PK00ALF0000000005',
                'qualification' => ['title' => 'Intermediate', 'institution' => 'Govt College for Women', 'year_completed' => 2016, 'grade' => 'B'],
                'subjects' => [],
            ],
            [
                'name' => 'Farhan Sheikh',
                'email' => 'headteacher@school.com',
                'username' => 'headteacher',
                'employee_no' => 'EMP-00006',
                'campus_index' => 0,
                'department' => 'Academics',
                'designation' => 'Head Teacher',
                'role' => 'head_teacher',
                'employment_type' => 'permanent',
                'basic_salary' => 85000,
                'allowance_amount' => 10000,
                'deduction_amount' => 2500,
                'payment_method' => 'bank',
                'bank_name' => 'HBL',
                'account_no' => 'PK00HBL0000000006',
                'qualification' => ['title' => 'M.A. Education', 'institution' => 'University of the Punjab', 'year_completed' => 2010, 'grade' => 'A'],
                'subjects' => ['Mathematics'],
            ],
            [
                'name' => 'Bilal Aslam',
                'email' => 'clerk@school.com',
                'username' => 'clerk',
                'employee_no' => 'EMP-00007',
                'campus_index' => 0,
                'department' => 'Administration',
                'designation' => 'Clerk',
                'role' => 'clerk',
                'employment_type' => 'permanent',
                'basic_salary' => 35000,
                'allowance_amount' => 3000,
                'deduction_amount' => 800,
                'payment_method' => 'cash',
                'bank_name' => null,
                'account_no' => null,
                'qualification' => ['title' => 'Intermediate', 'institution' => 'Govt College', 'year_completed' => 2017, 'grade' => 'B'],
                'subjects' => [],
            ],
            [
                'name' => 'Shabana Bibi',
                'email' => 'maid@school.com',
                'username' => 'maid',
                'employee_no' => 'EMP-00008',
                'campus_index' => 0,
                'department' => 'Support',
                'designation' => 'Support Staff',
                'role' => 'maid',
                'employment_type' => 'permanent',
                'basic_salary' => 25000,
                'allowance_amount' => 1500,
                'deduction_amount' => 500,
                'payment_method' => 'cash',
                'bank_name' => null,
                'account_no' => null,
                'qualification' => ['title' => 'Primary', 'institution' => 'Govt Primary School', 'year_completed' => 2000, 'grade' => 'C'],
                'subjects' => [],
            ],
        ];

        $subjects = Subject::get()->keyBy('name');
        $allowanceHeads = SalaryHead::where('type', SalaryHead::TYPE_ALLOWANCE)->orderBy('sort_order')->get();
        $deductionHeads = SalaryHead::where('type', SalaryHead::TYPE_DEDUCTION)->orderBy('sort_order')->get();

        foreach ($staffSeed as $index => $seed) {
            $campus = $campuses[$seed['campus_index']] ?? $campuses->first();

            $user = User::updateOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'username' => $seed['username'],
                    'password' => Hash::make('123456'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $hireDate = now()->subMonths(12 + ($index * 3))->toDateString();

            $staffProfile = StaffProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_no' => $seed['employee_no'],
                    'campus_id' => $campus?->id,
                    'department_id' => $departments[$seed['department']]->id ?? null,
                    'designation_id' => $designations[$seed['designation']]->id ?? null,
                    'employment_type' => $seed['employment_type'],
                    'hire_date' => $hireDate,
                    'basic_salary' => $seed['basic_salary'],
                    'allowance_amount' => $seed['allowance_amount'],
                    'deduction_amount' => $seed['deduction_amount'],
                    'payment_method' => $seed['payment_method'],
                    'bank_name' => $seed['bank_name'],
                    'account_no' => $seed['account_no'],
                    'is_active' => true,
                ]
            );

            if (isset($roles[$seed['role']])) {
                $user->syncRoles([$roles[$seed['role']]]);
            }

            $this->seedEmploymentPeriod($staffProfile, $hireDate);
            $this->seedQualification($staffProfile, $seed['qualification']);
            $this->seedSubjects($staffProfile, $seed['subjects'], $subjects);
            $this->seedSalaryComponents($staffProfile, $seed, $hireDate, $allowanceHeads, $deductionHeads);
            $this->seedDocuments($staffProfile, $user, $index);
        }

        $this->seedInactiveAndRetiredStaff($campuses, $departments, $designations, $roles);
        $this->seedPerCampusStaff($campuses, $departments, $designations, $roles);

        $this->command?->info('Staff records seeded successfully.');
    }

    /**
     * Every active campus gets its own campus_admin and two of its own
     * teachers, scoped via `campus_id` — not just the generic, mostly
     * campus-0 staff above. Emails are deterministic (`admin.campus{id}@...`)
     * so they can be documented as known demo logins.
     *
     * @param  Collection<int, Campus>  $campuses
     * @param  Collection<string, StaffDepartment>  $departments
     * @param  Collection<string, StaffDesignation>  $designations
     * @param  Collection<string, Role>  $roles
     */
    private function seedPerCampusStaff(Collection $campuses, Collection $departments, Collection $designations, Collection $roles): void
    {
        foreach ($campuses as $campus) {
            $adminUser = User::updateOrCreate(
                ['email' => "admin.campus{$campus->id}@school.com"],
                [
                    'name' => "{$campus->name} Admin",
                    'username' => "admincampus{$campus->id}",
                    'password' => Hash::make('123456'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $adminProfile = StaffProfile::updateOrCreate(
                ['user_id' => $adminUser->id],
                [
                    'employee_no' => "EMP-ADMIN-{$campus->id}",
                    'campus_id' => $campus->id,
                    'department_id' => $departments['Administration']->id ?? null,
                    'designation_id' => $designations['Principal']->id ?? null,
                    'employment_type' => 'permanent',
                    'hire_date' => now()->subYears(2)->toDateString(),
                    'basic_salary' => 120000,
                    'payment_method' => 'bank',
                    'is_active' => true,
                ]
            );

            if (isset($roles['campus_admin'])) {
                $adminUser->syncRoles([$roles['campus_admin']]);
            }

            $this->seedEmploymentPeriod($adminProfile, now()->subYears(2)->toDateString());

            for ($i = 1; $i <= 2; $i++) {
                $teacherUser = User::updateOrCreate(
                    ['email' => "teacher{$i}.campus{$campus->id}@school.com"],
                    [
                        'name' => "{$campus->name} Teacher {$i}",
                        'username' => "teacher{$i}campus{$campus->id}",
                        'password' => Hash::make('123456'),
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]
                );

                $teacherProfile = StaffProfile::updateOrCreate(
                    ['user_id' => $teacherUser->id],
                    [
                        'employee_no' => "EMP-T{$i}-{$campus->id}",
                        'campus_id' => $campus->id,
                        'department_id' => $departments['Academics']->id ?? null,
                        'designation_id' => $designations['Teacher']->id ?? null,
                        'employment_type' => 'permanent',
                        'hire_date' => now()->subYear()->toDateString(),
                        'basic_salary' => 55000,
                        'payment_method' => 'bank',
                        'is_active' => true,
                    ]
                );

                if (isset($roles['teacher'])) {
                    $teacherUser->syncRoles([$roles['teacher']]);
                }

                $this->seedEmploymentPeriod($teacherProfile, now()->subYear()->toDateString());
            }
        }
    }

    /**
     * Files a CNIC copy on every staff member, plus a Degree on every other
     * one, so the Staff Documents tab has something to show across the set.
     */
    private function seedDocuments(StaffProfile $staffProfile, User $user, int $index): void
    {
        $cnicType = StaffDocumentType::where('name', 'CNIC')->first();
        $degreeType = StaffDocumentType::where('name', 'Degree')->first();

        if ($cnicType) {
            StaffDocument::firstOrCreate(
                ['staff_profile_id' => $staffProfile->id, 'kind' => 'cnic'],
                ['title' => 'CNIC Copy', 'issued_on' => now()->subYears(2)->toDateString(), 'uploaded_by' => $user->id]
            );
        }

        if ($degreeType && $index % 2 === 0) {
            StaffDocument::firstOrCreate(
                ['staff_profile_id' => $staffProfile->id, 'kind' => 'degree'],
                ['title' => 'Degree Certificate', 'issued_on' => now()->subYears(5)->toDateString(), 'uploaded_by' => $user->id]
            );
        }

        // One staff member carries an already-expired document, to exercise
        // expiry alerts/filters.
        if ($index === 0) {
            StaffDocument::firstOrCreate(
                ['staff_profile_id' => $staffProfile->id, 'kind' => 'police_verification'],
                ['title' => 'Police Verification', 'issued_on' => now()->subYears(3)->toDateString(), 'expires_on' => now()->subMonth()->toDateString(), 'uploaded_by' => $user->id]
            );
        }
    }

    /**
     * One deactivated staff member (filters/toggles) and one soft-deleted
     * staff member (reusing a soft-deleted employee number/name).
     *
     * @param  Collection<string, Campus>  $campuses
     * @param  Collection<string, StaffDepartment>  $departments
     * @param  Collection<string, StaffDesignation>  $designations
     * @param  Collection<string, Role>  $roles
     */
    private function seedInactiveAndRetiredStaff(Collection $campuses, Collection $departments, Collection $designations, Collection $roles): void
    {
        $campus = $campuses->first();

        $inactiveUser = User::updateOrCreate(
            ['email' => 'inactive.staff@school.com'],
            ['name' => 'Inactive Staff Member', 'username' => 'inactivestaff', 'password' => Hash::make('123456'), 'is_active' => false]
        );

        $inactiveProfile = StaffProfile::updateOrCreate(
            ['user_id' => $inactiveUser->id],
            [
                'employee_no' => 'EMP-00009',
                'campus_id' => $campus?->id,
                'department_id' => $departments['Support']->id ?? null,
                'designation_id' => $designations['Support Staff']->id ?? null,
                'employment_type' => 'permanent',
                'hire_date' => now()->subYears(2)->toDateString(),
                'basic_salary' => 30000,
                'payment_method' => 'cash',
                'is_active' => false,
            ]
        );

        if (isset($roles['maid'])) {
            $inactiveUser->syncRoles([$roles['maid']]);
        }

        $retiredUser = User::updateOrCreate(
            ['email' => 'retired.staff@school.com'],
            ['name' => 'Retired Staff Member', 'username' => 'retiredstaff', 'password' => Hash::make('123456'), 'is_active' => false]
        );

        $retiredProfile = StaffProfile::updateOrCreate(
            ['user_id' => $retiredUser->id],
            [
                'employee_no' => 'EMP-00010',
                'campus_id' => $campus?->id,
                'employment_type' => 'permanent',
                'hire_date' => now()->subYears(5)->toDateString(),
                'basic_salary' => 28000,
                'payment_method' => 'cash',
                'is_active' => false,
            ]
        );

        // Soft-deleted, to exercise reusing a soft-deleted employee number.
        $retiredProfile->delete();
    }

    /**
     * One open employment period, joined the same day as the hire date.
     */
    private function seedEmploymentPeriod(StaffProfile $staffProfile, string $hireDate): void
    {
        StaffEmploymentPeriod::firstOrCreate(
            ['staff_profile_id' => $staffProfile->id],
            ['joined_on' => $hireDate]
        );
    }

    /**
     * @param  array{title: string, institution: string, year_completed: int, grade: string}  $qualification
     */
    private function seedQualification(StaffProfile $staffProfile, array $qualification): void
    {
        StaffQualification::firstOrCreate(
            ['staff_profile_id' => $staffProfile->id, 'title' => $qualification['title']],
            [
                'institution' => $qualification['institution'],
                'year_completed' => $qualification['year_completed'],
                'grade' => $qualification['grade'],
            ]
        );
    }

    /**
     * @param  array<int, string>  $subjectNames
     * @param  Collection<string, Subject>  $subjects
     */
    private function seedSubjects(StaffProfile $staffProfile, array $subjectNames, Collection $subjects): void
    {
        foreach ($subjectNames as $position => $subjectName) {
            $subject = $subjects[$subjectName] ?? null;

            if (! $subject) {
                continue;
            }

            StaffSubject::firstOrCreate(
                ['staff_profile_id' => $staffProfile->id, 'subject_id' => $subject->id],
                ['is_primary' => $position === 0]
            );
        }
    }

    /**
     * Splits the lump `allowance_amount`/`deduction_amount` into named heads so
     * the salary breakdown a payslip shows has something behind it.
     *
     * @param  array<string, mixed>  $seed
     * @param  Collection<int, SalaryHead>  $allowanceHeads
     * @param  Collection<int, SalaryHead>  $deductionHeads
     */
    private function seedSalaryComponents(
        StaffProfile $staffProfile,
        array $seed,
        string $effectiveFrom,
        Collection $allowanceHeads,
        Collection $deductionHeads
    ): void {
        $this->splitAcrossHeads($staffProfile, $allowanceHeads, (float) $seed['allowance_amount'], $effectiveFrom);
        $this->splitAcrossHeads($staffProfile, $deductionHeads, (float) $seed['deduction_amount'], $effectiveFrom);
    }

    /**
     * @param  Collection<int, SalaryHead>  $heads
     */
    private function splitAcrossHeads(StaffProfile $staffProfile, Collection $heads, float $total, string $effectiveFrom): void
    {
        if ($heads->isEmpty() || $total <= 0) {
            return;
        }

        $heads = $heads->take(2);
        $share = round($total / $heads->count(), 2);

        foreach ($heads as $head) {
            StaffSalaryComponent::firstOrCreate(
                [
                    'staff_profile_id' => $staffProfile->id,
                    'salary_head_id' => $head->id,
                ],
                [
                    'amount' => $share,
                    'effective_from' => $effectiveFrom,
                ]
            );
        }
    }
}
