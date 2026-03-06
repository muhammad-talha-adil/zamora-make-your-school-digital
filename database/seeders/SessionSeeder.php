<?php

namespace Database\Seeders;

use App\Models\Session;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = [
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
    }
}
