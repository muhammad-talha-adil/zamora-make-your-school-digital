<?php

namespace Database\Seeders;

use App\Models\Session;
use Illuminate\Database\Seeder;

// Dev/demo data only — not part of the default production seed list.
class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = [
            [
                'name' => '2023-2024',
                'description' => 'Academic session for year 2023-2024',
                'is_active' => false,
                'start_date' => '2023-04-01',
                'end_date' => '2024-03-31',
            ],
            [
                'name' => '2024-2025',
                'description' => 'Academic session for year 2024-2025',
                'is_active' => false,
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31',
            ],
            [
                'name' => '2025-2026',
                'description' => 'Academic session for year 2025-2026',
                'is_active' => true,
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
            ],
        ];

        foreach ($sessions as $session) {
            Session::firstOrCreate(
                ['name' => $session['name']],
                $session
            );
        }

        // A soft-deleted session, to exercise reusing a soft-deleted name.
        $retired = Session::firstOrCreate(
            ['name' => '2022-2023'],
            ['description' => 'Retired session', 'is_active' => false, 'start_date' => '2022-04-01', 'end_date' => '2023-03-31']
        );
        $retired->delete();
    }
}
