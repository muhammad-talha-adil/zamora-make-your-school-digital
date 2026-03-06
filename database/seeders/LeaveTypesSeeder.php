<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Sick Leave',
                'is_active' => true,
                'description' => 'Leave due to illness or medical appointment',
            ],
            [
                'name' => 'Wedding Leave',
                'is_active' => true,
                'description' => 'Leave for student\'s own wedding',
            ],
            [
                'name' => 'Personal Leave',
                'is_active' => true,
                'description' => 'Personal or family matters',
            ],
            [
                'name' => 'Other',
                'is_active' => true,
                'description' => 'Other types of leave',
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['name' => $leaveType['name']],
                $leaveType
            );
        }
    }
}
