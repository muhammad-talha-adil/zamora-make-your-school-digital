<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCampusTypeRequest;
use App\Http\Requests\Settings\UpdateCampusTypeRequest;
use App\Models\CampusType;
use Inertia\Response;

class CampusTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return inertia('Settings/School', [
            'campusTypes' => CampusType::withCount('campuses')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCampusTypeRequest $request)
    {
        $campusType = CampusType::create($request->validated());

        return back()->with([
            'success' => 'Campus type created successfully.',
            'campusType' => $campusType->loadCount('campuses'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CampusType $campusType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CampusType $campusType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCampusTypeRequest $request, CampusType $campusType)
    {
        $campusType->update($request->validated());

        return back()->with([
            'success' => 'Campus type updated successfully.',
            'campusType' => $campusType->loadCount('campuses'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CampusType $campusType)
    {
        $campusType->delete();

        return back()->with('success', 'Campus type deleted successfully.');
    }

    /**
     * Get all campus types with optional search and trashed filter
     * Used by ComboboxInput for search functionality and CampusTypeModal for listing
     */
    public function getAll()
    {
        $query = CampusType::withCount('campuses');

        // Handle trashed filter: 1 = only trashed, 0 or null = only non-trashed
        $trashed = request()->get('trashed');
        if ($trashed === '1') {
            $query->onlyTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        $search = trim(request()->get('q', ''));
        if ($search !== '') {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']);
        }

        $campusTypes = $query->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'campuses_count' => $type->campuses_count,
                ];
            });

        return response()->json($campusTypes);
    }

    /**
     * Restore the specified campus type from trash.
     */
    public function restore(int $id)
    {
        $campusType = CampusType::onlyTrashed()->findOrFail($id);
        $campusType->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Campus type restored successfully.');
    }

    /**
     * Permanently delete the specified campus type.
     */
    public function forceDelete(int $id)
    {
        $campusType = CampusType::onlyTrashed()->findOrFail($id);
        $campusType->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Campus type permanently deleted.');
    }
}
