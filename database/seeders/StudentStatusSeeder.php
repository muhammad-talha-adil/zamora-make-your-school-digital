<?php

namespace Database\Seeders;

use App\Models\StudentStatus;
use Illuminate\Database\Seeder;

class StudentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Graduated', 'description' => 'Student has graduated from the institution'],
            ['name' => 'Left', 'description' => 'Student has left the institution'],
            ['name' => 'Suspended', 'description' => 'Student is temporarily suspended'],
            ['name' => 'Active', 'description' => 'Student is currently active'],
            // ['name' => 'Pending', 'description' => 'Student admission is pending'],
        ];

        foreach ($statuses as $status) {
            StudentStatus::firstOrCreate(['name' => $status['name']], $status);
        }
    }
}
