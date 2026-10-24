<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSchoolWebsiteContentRequest;
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
            'contact_email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'website_enabled' => 'boolean',
            'theme_colors' => 'nullable|string',
        ]);

        // Not a School column - handled separately below, and must not reach
        // School::update()/create() or it fails as an unknown attribute.
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
            $this->saveAutoTheme($themeColors, $request->user());
        }

        return back()->with('success', 'School information updated successfully.');
    }

    /**
     * The client extracts a light/dark palette from the uploaded logo and
     * posts it as a `theme_colors` JSON string alongside the profile form.
     * Persisted into the same `theme_settings` rows the Appearance screen
     * manages, so it applies app-wide through the existing theming pipeline.
     *
     * Silently skipped (not a validation failure) for malformed JSON or a
     * caller without `school.theme.manage` - this is a convenience on top
     * of the profile save, not a required part of it.
     */
    private function saveAutoTheme(string $themeColors, $user): void
    {
        if (! $user?->hasPermission('school.theme.manage')) {
            return;
        }

        $decoded = json_decode($themeColors, true);

        if (! is_array($decoded)) {
            return;
        }

        foreach (['light', 'dark'] as $mode) {
            if (! isset($decoded[$mode]) || ! is_array($decoded[$mode])) {
                continue;
            }

            ThemeSetting::updateOrCreate(
                ['mode' => $mode],
                ['colors_json' => $decoded[$mode], 'updated_by' => $user->id]
            );
        }
    }

    /**
     * Updates the JSON/text-driven public website content (hero, about,
     * contact, academics and admissions page copy) shared globally via
     * HandleInertiaRequests. Kept separate from the core profile `update`
     * so each form only validates and submits what it owns.
     */
    public function updateWebsiteContent(UpdateSchoolWebsiteContentRequest $request): RedirectResponse
    {
        $school = School::first();

        if (! $school) {
            $school = School::create($request->validated());
        } else {
            $school->update($request->validated());
        }

        return back()->with('success', 'Website content updated successfully.');
    }
}
