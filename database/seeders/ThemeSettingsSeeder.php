<?php

namespace Database\Seeders;

use App\Models\ThemePalette;
use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

class ThemeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modes = ['light', 'dark'];

        foreach ($modes as $mode) {
            $defaultPalette = ThemePalette::where('mode', $mode)->first();
            if ($defaultPalette) {
                $colors = $defaultPalette->colors->pluck('hex', 'slot')->toArray();

                ThemeSetting::create([
                    'mode' => $mode,
                    'selected_palette_id' => $defaultPalette->id,
                    'colors_json' => $colors,
                    'updated_by' => 1, // Assuming user ID 1 exists
                ]);
            }
        }
    }
}
