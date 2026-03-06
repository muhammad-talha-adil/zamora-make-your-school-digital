<?php

namespace Database\Seeders;

use App\Models\AttendanceStatus;
use Illuminate\Database\Seeder;

class AttendanceStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Present',
                'code' => 'P',
                'description' => 'Student was present in class',
            ],
            [
                'name' => 'Absent',
                'code' => 'A',
                'description' => 'Student was absent from class',
            ],
            [
                'name' => 'Leave',
                'code' => 'L',
                'description' => 'Student was on approved leave',
            ],
            [
                'name' => 'Late',
                'code' => 'LT',
                'description' => 'Student arrived late to class',
            ],
        ];

        foreach ($statuses as $status) {
            AttendanceStatus::firstOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
