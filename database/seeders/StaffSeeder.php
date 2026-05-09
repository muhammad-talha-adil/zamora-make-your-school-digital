<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Role;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $campuses = Campus::where('is_active', true)->get();
        $roles = Role::get()->keyBy('slug');
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
            ],
        ];

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

            StaffProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_no' => $seed['employee_no'],
                    'campus_id' => $campus?->id,
                    'department_id' => $departments[$seed['department']]->id ?? null,
                    'designation_id' => $designations[$seed['designation']]->id ?? null,
                    'employment_type' => $seed['employment_type'],
                    'hire_date' => now()->subMonths(12 + ($index * 3))->toDateString(),
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
                UserRole::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'role_id' => $roles[$seed['role']]->id,
                        'campus_id' => $campus?->id,
                    ],
                    ['is_active' => true]
                );
            }
        }

        $this->command?->info('Staff records seeded successfully.');
    }
}
