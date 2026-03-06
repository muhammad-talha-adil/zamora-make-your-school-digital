<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCampusRequest;
use App\Http\Requests\Settings\UpdateCampusRequest;
use App\Models\Campus;
use App\Models\CampusType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampusController extends Controller
{
    /**
     * API endpoint to get all campuses for dropdowns.
     */
    public function apiIndex(): JsonResponse
    {
        $campuses = Campus::orderBy('name')->get(['id', 'name', 'is_active']);
        return response()->json($campuses);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $campuses = Campus::with('campusType')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $campusTypes = CampusType::orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Campuses/Index', [
            'tableCampuses' => $campuses,
            'campusTypes' => $campusTypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $campusTypes = CampusType::orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Campuses/Create', [
            'campusTypes' => $campusTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampusRequest $request)
    {
        $validated = $request->validated();

        Campus::create($validated);

        // Check if request expects JSON (axios) or redirect (Inertia)
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Campus created successfully.']);
        }

        return redirect()->back()->with('success', 'Campus created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campus $campus): Response
    {
        $campusTypes = CampusType::orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Campuses/Edit', [
            'campus' => $campus->load('campusType'),
            'campusTypes' => $campusTypes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampusRequest $request, Campus $campus)
    {
        $validated = $request->validated();

        $campus->update($validated);

        // Check if request expects JSON (axios) or redirect (Inertia)
        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Campus updated successfully.']);
        }

        return redirect()->back()->with('success', 'Campus updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Campus $campus): RedirectResponse
    {
        $campus->delete();

        return redirect()->route('campuses.index')->with('success', 'Campus deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Campus $campus): RedirectResponse
    {
        $campus->update(['is_active' => false]);

        return redirect()->route('campuses.index')->with('success', 'Campus inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Campus $campus): RedirectResponse
    {
        $campus->update(['is_active' => true]);

        return redirect()->route('campuses.index')->with('success', 'Campus activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $campus = Campus::onlyTrashed()->findOrFail($id);
        $campus->restore();

        return redirect()->route('campuses.index')->with('success', 'Campus restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $campus = Campus::onlyTrashed()->findOrFail($id);
        $campus->forceDelete();

        return redirect()->route('campuses.index')->with('success', 'Campus permanently deleted.');
    }
}
