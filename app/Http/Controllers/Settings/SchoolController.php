<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Campus;
use App\Models\CampusType;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function show(Request $request): Response
    {
        $this->authorize('settings.manage');

        $school = School::first(); // Assuming single school

        // Fetch all data for tabs
        $campuses = Campus::with('campusType')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $campusTypes = CampusType::orderBy('name', 'asc')->get(['id', 'name']);

        $classes = SchoolClass::withCount('sections')
            ->orderBy('id', 'asc')
            ->paginate(10);

        $sections = Section::with('schoolClass')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $sessions = Session::orderBy('id', 'desc')->paginate(10);

        $subjects = Subject::orderBy('id', 'asc')->paginate(10);

        return Inertia::render('settings/SchoolProfile', [
            'school' => $school,
            'campuses' => $campuses,
            'campusTypes' => $campusTypes,
            'classes' => $classes,
            'sections' => $sections,
            'sessions' => $sessions,
            'subjects' => $subjects,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        $school = School::first();

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoName = time() . '_' . $logoFile->getClientOriginalName();
            $logoFile->move(public_path('uploads/logo'), $logoName);
            $validated['logo_path'] = '/uploads/logo/' . $logoName;
        }

        if ($school) {
            $school->update($validated);
        } else {
            School::create($validated);
        }

        return back()->with('success', 'School information updated successfully.');
    }
}
