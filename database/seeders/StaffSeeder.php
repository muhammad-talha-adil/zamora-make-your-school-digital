<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Role;
use App\Models\Staff\SalaryHead;
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
        }

        $this->command?->info('Staff records seeded successfully.');
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
