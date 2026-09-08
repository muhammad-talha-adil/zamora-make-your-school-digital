<?php

namespace Database\Seeders;

use App\Models\AttendanceStatus;
use Illuminate\Database\Seeder;

class AttendanceStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * `weight` is the share of a day the status counts as present, and it is
     * what every report now does its arithmetic with. Late is a full day — the
     * child was in class; the lateness is recorded so it can be chased, not so
     * the attendance is docked.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Present',
                'code' => 'P',
                'description' => 'Student was present in class',
                'weight' => 1,
                'counts_as_expected' => true,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Absent',
                'code' => 'A',
                'description' => 'Student was absent from class',
                'weight' => 0,
                'counts_as_expected' => true,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Leave',
                'code' => 'L',
                'description' => 'Student was on approved leave',
                'weight' => 0,
                'counts_as_expected' => true,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Late',
                'code' => 'LT',
                'description' => 'Student arrived late to class',
                'weight' => 1,
                'counts_as_expected' => true,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                // Marked routinely here: a child leaves after break for a family
                // reason and the day is neither present nor absent.
                'name' => 'Half Day',
                'code' => 'HD',
                'description' => 'Student attended part of the day',
                'weight' => 0.5,
                'counts_as_expected' => true,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            AttendanceStatus::updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
