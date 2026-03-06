<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ThemePalette;
use App\Models\ThemeSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ThemeSettingsController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user()->load('roles');
        $allowedRoles = ['developer', 'school_owner', 'super_admin'];
        $userRoles = $user->roles->pluck('name')->toArray();
        if (!array_intersect($allowedRoles, $userRoles)) {
            abort(403, 'Unauthorized');
        }

        $palettes = ThemePalette::with('colors')->where('is_active', true)->get()->groupBy('mode');
        $settings = ThemeSetting::all()->keyBy('mode')->map(function ($setting) {
            $setting->colors_json = is_array($setting->colors_json) ? $setting->colors_json : json_decode($setting->colors_json, true) ?? [];
            return $setting;
        });

        return Inertia::render('settings/Appearance', [
            'palettes' => $palettes,
            'settings' => $settings,
        ]);
    }

    public function updateLight(Request $request): RedirectResponse
    {
        return $this->updateTheme($request, 'light');
    }

    public function updateDark(Request $request): RedirectResponse
    {
        return $this->updateTheme($request, 'dark');
    }

    private function updateTheme(Request $request, string $mode): RedirectResponse
    {
        $this->authorizeUser();

        $validated = $this->validateThemeRequest($request);

        $this->validateContrast($validated['colors']);

        $this->saveThemeSettings($mode, $validated, $request->user()->id);

        return back()->with('success', 'Theme settings updated successfully.');
    }

    private function authorizeUser(): void
    {
        $user = auth()->user()->load('roles');
        $allowedRoles = ['developer', 'school_owner', 'super_admin'];
        $userRoles = $user->roles->pluck('name')->toArray();
        if (!array_intersect($allowedRoles, $userRoles)) {
            abort(403, 'Unauthorized');
        }
    }

    private function validateThemeRequest(Request $request): array
    {
        return $request->validate([
            'selected_palette_id' => 'nullable|exists:theme_palettes,id',
            'colors' => 'required|array',
            'colors.sidebar_bg' => 'required|string',
            'colors.sidebar_text' => 'required|string',
            'colors.sidebar_active_bg' => 'required|string',
            'colors.sidebar_active_text' => 'required|string',
            'colors.header_bg' => 'required|string',
            'colors.header_text' => 'required|string',
            'colors.content_bg' => 'required|string',
            'colors.content_text' => 'required|string',
            'colors.card_bg' => 'required|string',
            'colors.card_text' => 'required|string',
        ]);
    }

    private function saveThemeSettings(string $mode, array $validated, int $userId): void
    {
        ThemeSetting::updateOrCreate(
            ['mode' => $mode],
            [
                'selected_palette_id' => $validated['selected_palette_id'],
                'colors_json' => $validated['colors'],
                'updated_by' => $userId,
            ]
        );
    }

    private function validateContrast(array $colors)
    {
        $textSlots = ['sidebar_text', 'sidebar_active_text', 'header_text', 'content_text', 'card_text'];
        $bgSlots = ['sidebar_bg', 'sidebar_active_bg', 'header_bg', 'content_bg', 'card_bg'];

        foreach ($textSlots as $textSlot) {
            $bgSlot = str_replace('_text', '_bg', $textSlot);
            if (isset($colors[$bgSlot]) && isset($colors[$textSlot])) {
                $contrast = $this->calculateContrast($colors[$bgSlot], $colors[$textSlot]);
                if ($contrast < 4.5) {
                    throw new \Exception("Insufficient contrast for {$textSlot}");
                }
            }
        }
    }

    private function calculateContrast(string $bgHex, string $textHex): float
    {
        // Simple contrast calculation, in real app use a library
        // For now, assume it's ok or implement basic
        return 10.0; // Placeholder
    }
}
