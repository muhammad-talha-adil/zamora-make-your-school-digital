<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSectionRequest;
use App\Http\Requests\Settings\UpdateSectionRequest;
use App\Models\SchoolClass;
use App\Models\Section;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $sections = Section::with('schoolClass')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('settings/Sections/Index', [
            'tableSections' => $sections,
        ]);
    }

    /**
     * Handle API requests for section listing with filtering.
     */
    public function apiIndex()
    {
        $query = Section::with('schoolClass');

        // Handle status filter
        $status = request()->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Handle class_id filter
        $classId = request()->get('class_id');
        if ($classId) {
            $query->where('class_id', $classId);
        }

        // Handle trashed filter
        if (request()->get('trashed') === '1') {
            $query->onlyTrashed();
        }

        $perPage = request()->get('per_page', 10);
        $page = request()->get('page', 1);

        $sections = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($sections);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $schoolClasses = SchoolClass::orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Sections/Create', [
            'schoolClasses' => $schoolClasses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request)
    {
        $validated = $request->validated();

        Section::create($validated);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Section created successfully.',
            ]);
        }

        return redirect()->route('sections.index')->with('success', 'Section created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section)
    {
        $validated = $request->validated();

        $section->update($validated);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully.',
            ]);
        }

        return redirect()->route('sections.index')->with('success', 'Section updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Section $section)
    {
        $section->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sections.index')->with('success', 'Section deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Section $section)
    {
        $section->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sections.index')->with('success', 'Section inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Section $section)
    {
        $section->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sections.index')->with('success', 'Section activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id)
    {
        $section = Section::onlyTrashed()->findOrFail($id);
        $section->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sections.index')->with('success', 'Section restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id)
    {
        $section = Section::onlyTrashed()->findOrFail($id);
        $section->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sections.index')->with('success', 'Section permanently deleted.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Section $section): Response
    {
        $schoolClasses = SchoolClass::orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Sections/Edit', [
            'section' => $section->load('schoolClass'),
            'schoolClasses' => $schoolClasses,
        ]);
    }
}
