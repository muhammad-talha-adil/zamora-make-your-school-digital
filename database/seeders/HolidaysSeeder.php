<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds Pakistan official public/national holidays for 2026.
     * Note: Eid dates are approximate due to moon sighting and should be adjusted as needed.
     */
    public function run(): void
    {
        $holidays = [
            // === National Holidays ===
            [
                'title' => 'New Year\'s Day',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-01',
                'is_national' => true,
                'description' => 'First day of the Gregorian calendar year',
            ],
            [
                'title' => 'Kashmir Day',
                'start_date' => '2026-02-05',
                'end_date' => '2026-02-05',
                'is_national' => true,
                'description' => 'Commemorates the Kashmir liberation day',
            ],
            [
                'title' => 'Pakistan Day',
                'start_date' => '2026-03-23',
                'end_date' => '2026-03-23',
                'is_national' => true,
                'description' => 'National Day - commemorates the Lahore Resolution',
            ],
            [
                'title' => 'Eid-ul-Fitr (Ramadan Eid)',
                'start_date' => '2026-03-10',
                'end_date' => '2026-03-12',
                'is_national' => true,
                'description' => 'End of Ramadan - NOTE: Dates may vary based on moon sighting. Please verify closer to the date.',
            ],
            [
                'title' => 'Labour Day',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-01',
                'is_national' => true,
                'description' => 'International Workers\' Day',
            ],
            [
                'title' => 'Eid-ul-Azha (Qurbani Eid)',
                'start_date' => '2026-07-06',
                'end_date' => '2026-07-08',
                'is_national' => true,
                'description' => 'Festival of Sacrifice - NOTE: Dates may vary based on moon sighting.',
            ],
            [
                'title' => 'Independence Day',
                'start_date' => '2026-08-14',
                'end_date' => '2026-08-14',
                'is_national' => true,
                'description' => 'Pakistan\'s Independence Day',
            ],
            [
                'title' => 'Ashura',
                'start_date' => '2026-08-26',
                'end_date' => '2026-08-27',
                'is_national' => true,
                'description' => 'Islamic holy day - NOTE: Dates may vary based on moon sighting.',
            ],
            [
                'title' => 'Defence Day',
                'start_date' => '2026-09-06',
                'end_date' => '2026-09-06',
                'is_national' => true,
                'description' => 'Commemorates the 1965 war',
            ],
            [
                'title' => 'Eid Milad-un-Nabi',
                'start_date' => '2026-10-27',
                'end_date' => '2026-10-27',
                'is_national' => true,
                'description' => 'Birth of Prophet Muhammad (PBUH) - NOTE: Dates may vary based on moon sighting.',
            ],
            [
                'title' => 'Christmas Day',
                'start_date' => '2026-12-25',
                'end_date' => '2026-12-25',
                'is_national' => true,
                'description' => 'Christian holiday',
            ],

            // === School Holidays (Non-National) ===
            [
                'title' => 'Winter Vacation',
                'start_date' => '2025-12-25',
                'end_date' => '2026-01-05',
                'is_national' => false,
                'description' => 'Winter break for all campuses',
            ],
            [
                'title' => 'Spring Break',
                'start_date' => '2026-03-23',
                'end_date' => '2026-03-28',
                'is_national' => false,
                'description' => 'Spring vacation including Pakistan Day',
            ],
            [
                'title' => 'Summer Vacation',
                'start_date' => '2026-07-15',
                'end_date' => '2026-08-14',
                'is_national' => false,
                'description' => 'Summer break for all campuses',
            ],
            [
                'title' => 'Mid-Term Break',
                'start_date' => '2026-04-20',
                'end_date' => '2026-04-24',
                'is_national' => false,
                'description' => 'Mid-term examination break',
            ],
            [
                'title' => 'Muharram Holidays',
                'start_date' => '2026-08-26',
                'end_date' => '2026-08-28',
                'is_national' => false,
                'description' => 'Muharram observance period',
            ],
            [
                'title' => 'Annual Day Holiday',
                'start_date' => '2026-11-14',
                'end_date' => '2026-11-14',
                'is_national' => false,
                'description' => 'School annual day celebration',
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                [
                    'title' => $holiday['title'],
                    'start_date' => $holiday['start_date'],
                ],
                $holiday
            );
        }

        $this->command->info('Holidays seeded successfully!');
    }
}
