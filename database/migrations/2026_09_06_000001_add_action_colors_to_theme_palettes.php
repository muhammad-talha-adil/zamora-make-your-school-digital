<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the action colour slots to every palette.
 *
 * Palettes previously stored only the chrome (sidebar, header, content, card),
 * so buttons, badges and status text were hardcoded in the Vue components and
 * ignored the selected theme. These slots give those states a home in the
 * database alongside the surface colours.
 */
return new class extends Migration
{
    /**
     * Slot => [light hex, dark hex].
     *
     * Dark-mode variants are lighter so they hold contrast against a dark
     * card, and their foregrounds flip to a dark ink where the fill is bright.
     */
    private const SLOTS = [
        'primary' => ['#2563EB', '#3B82F6'],
        'primary_text' => ['#FFFFFF', '#0B1220'],
        'success' => ['#16A34A', '#22C55E'],
        'success_text' => ['#FFFFFF', '#0B1220'],
        'danger' => ['#DC2626', '#EF4444'],
        'danger_text' => ['#FFFFFF', '#FFFFFF'],
        'warning' => ['#D97706', '#F59E0B'],
        'warning_text' => ['#FFFFFF', '#0B1220'],
        'info' => ['#0891B2', '#06B6D4'],
        'info_text' => ['#FFFFFF', '#0B1220'],
    ];

    public function up(): void
    {
        $now = now();

        $palettes = DB::table('theme_palettes')->get(['id', 'mode']);

        foreach ($palettes as $palette) {
            $existing = DB::table('theme_palette_colors')
                ->where('theme_palette_id', $palette->id)
                ->pluck('slot')
                ->all();

            $rows = [];

            foreach (self::SLOTS as $slot => [$light, $dark]) {
                if (in_array($slot, $existing, true)) {
                    continue;
                }

                $rows[] = [
                    'theme_palette_id' => $palette->id,
                    'slot' => $slot,
                    'hex' => $palette->mode === 'dark' ? $dark : $light,
                    'label' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows) {
                DB::table('theme_palette_colors')->insert($rows);
            }
        }

        // Backfill the two live settings rows so the running theme picks the
        // slots up without the admin having to re-save a palette.
        foreach (DB::table('theme_settings')->get(['id', 'mode', 'colors_json']) as $setting) {
            $colors = json_decode($setting->colors_json ?? '[]', true) ?: [];

            foreach (self::SLOTS as $slot => [$light, $dark]) {
                if (! array_key_exists($slot, $colors)) {
                    $colors[$slot] = $setting->mode === 'dark' ? $dark : $light;
                }
            }

            DB::table('theme_settings')
                ->where('id', $setting->id)
                ->update([
                    'colors_json' => json_encode($colors),
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('theme_palette_colors')
            ->whereIn('slot', array_keys(self::SLOTS))
            ->delete();

        foreach (DB::table('theme_settings')->get(['id', 'colors_json']) as $setting) {
            $colors = json_decode($setting->colors_json ?? '[]', true) ?: [];

            DB::table('theme_settings')
                ->where('id', $setting->id)
                ->update([
                    'colors_json' => json_encode(
                        array_diff_key($colors, array_flip(array_keys(self::SLOTS)))
                    ),
                ]);
        }
    }
};
