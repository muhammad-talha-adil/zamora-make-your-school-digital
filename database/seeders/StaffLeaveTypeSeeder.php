<?php

namespace Database\Seeders;

use App\Models\Staff\StaffLeaveType;
use Illuminate\Database\Seeder;

class StaffLeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 12, 'is_paid' => true, 'requires_approval' => true],
            ['name' => 'Sick Leave', 'code' => 'SL', 'days_per_year' => 10, 'is_paid' => true, 'requires_approval' => true],
            ['name' => 'Earned Leave', 'code' => 'EL', 'days_per_year' => 15, 'is_paid' => true, 'requires_approval' => true],
            ['name' => 'Unpaid Leave', 'code' => 'UL', 'days_per_year' => null, 'is_paid' => false, 'requires_approval' => true],
        ];

        foreach ($types as $type) {
            StaffLeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type + ['is_active' => true]
            );
        }
    }
}
