<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
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
}
