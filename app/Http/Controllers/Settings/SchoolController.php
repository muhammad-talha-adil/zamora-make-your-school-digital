<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    /**
     * Campuses, classes, sections, sessions and subjects moved to their own
     * pages under the School Setting hub (#101) - this now only renders the
     * school-info form.
     */
    public function show(): Response
    {
        $this->authorize('school.profile.manage');

        $school = School::first(); // Assuming single school

        return Inertia::render('settings/SchoolProfile', [
            'school' => $school,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('school.profile.manage');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'website_enabled' => 'boolean',
            'theme_colors' => 'nullable|string',
        ]);

        $themeColors = $validated['theme_colors'] ?? null;
        unset($validated['theme_colors']);

        $school = School::first();

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoName = time().'_'.$logoFile->getClientOriginalName();
            $logoFile->move(public_path('uploads/logo'), $logoName);
            $validated['logo_path'] = '/uploads/logo/'.$logoName;
        }

        if ($school) {
            $school->update($validated);
        } else {
            School::create($validated);
        }

        if ($themeColors !== null) {
            $this->saveAutoTheme($themeColors, $request->user()->id);
        }

        return back()->with('success', 'School information updated successfully.');
    }

    /**
     * Persists an auto-generated light/dark palette (derived client-side from
     * the uploaded logo) into the same `theme_settings` rows the Appearance
     * screen manages, so it applies app-wide through the existing theming
     * pipeline without any extra plumbing.
     *
     * Silently ignored when the payload is malformed or the user lacks
     * `school.theme.manage` — a bad/unauthorized auto-theme must never break
     * the school-profile save it rode in on.
     */
    private function saveAutoTheme(string $themeColorsJson, int $userId): void
    {
        if (! auth()->user()?->hasPermission('school.theme.manage')) {
            return;
        }

        $decoded = json_decode($themeColorsJson, true);

        if (! is_array($decoded)) {
            return;
        }

        foreach (['light', 'dark'] as $mode) {
            $colors = $decoded[$mode] ?? null;

            if (! is_array($colors) || $colors === []) {
                continue;
            }

            $colors = array_filter($colors, fn ($value) => is_string($value) && $value !== '');

            if ($colors === []) {
                continue;
            }

            ThemeSetting::updateOrCreate(
                ['mode' => $mode],
                [
                    'selected_palette_id' => null,
                    'colors_json' => $colors,
                    'updated_by' => $userId,
                ]
            );
        }
    }
}
