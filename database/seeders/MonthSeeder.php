<?php

namespace Database\Seeders;

use App\Models\Month;
use Illuminate\Database\Seeder;

/**
 * Month Seeder
 *
 * Seeds the months table with all 12 months.
 * This is required for the fee module to work properly.
 */
class MonthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $months = [
            ['name' => 'January', 'month_number' => 1],
            ['name' => 'February', 'month_number' => 2],
            ['name' => 'March', 'month_number' => 3],
            ['name' => 'April', 'month_number' => 4],
            ['name' => 'May', 'month_number' => 5],
            ['name' => 'June', 'month_number' => 6],
            ['name' => 'July', 'month_number' => 7],
            ['name' => 'August', 'month_number' => 8],
            ['name' => 'September', 'month_number' => 9],
            ['name' => 'October', 'month_number' => 10],
            ['name' => 'November', 'month_number' => 11],
            ['name' => 'December', 'month_number' => 12],
        ];

        foreach ($months as $month) {
            Month::firstOrCreate(
                ['month_number' => $month['month_number']],
                ['name' => $month['name']]
            );
        }

        $this->command->info('Months table seeded successfully!');
    }
}
