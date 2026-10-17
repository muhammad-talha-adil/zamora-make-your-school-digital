<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSchoolWebsiteContentRequest;
use App\Models\School;
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
        ]);

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

        return back()->with('success', 'School information updated successfully.');
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
