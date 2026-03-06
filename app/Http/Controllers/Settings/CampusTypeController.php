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
     * Get all campus types with optional search
     * Used by ComboboxInput for search functionality
     */
    public function getAll()
    {
        $query = trim(request()->get('q', ''));

        $campusTypes = CampusType::withCount('campuses')
            ->when($query, function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%']);
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            });

        return response()->json($campusTypes);
    }
}
