<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCampusRequest;
use App\Http\Requests\Settings\UpdateCampusRequest;
use App\Models\Campus;
use App\Models\CampusType;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class CampusController extends Controller
{
    /**
     * API endpoint to get campuses for data table with filtering.
     */
    public function apiIndex(): JsonResponse
    {
        $query = Campus::with('campusType');

        $status = request()->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Pagination: if per_page is present, paginate; else, return all (for dropdowns etc.)
        if (request()->has('per_page')) {
            $perPage = request()->get('per_page', 10);
            $page = request()->get('page', 1);
            $campuses = $query->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json($campuses);
        }

        // Non-paginated, used for dropdowns
        $campuses = $query->orderBy('name', 'asc')->get();

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
    public function destroy(Campus $campus)
    {
        $campus->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Campus $campus)
    {
        $campus->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Campus inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Campus $campus)
    {
        $campus->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Campus activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id)
    {
        $campus = Campus::onlyTrashed()->findOrFail($id);
        $campus->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('campuses.index')->with('success', 'Campus restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id)
    {
        $campus = Campus::onlyTrashed()->findOrFail($id);
        $campus->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('campuses.index')->with('success', 'Campus permanently deleted.');
    }
}
