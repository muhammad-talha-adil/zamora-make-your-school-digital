<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSchoolClassRequest;
use App\Http\Requests\Settings\UpdateSchoolClassRequest;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $schoolClasses = SchoolClass::orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('settings/SchoolClasses/Index', [
            'tableSchoolClasses' => $schoolClasses,
        ]);
    }

    /**
     * Handle API requests for class listing with filtering.
     */
    public function apiIndex()
    {
        $query = SchoolClass::query();

        // Handle status filter
        $status = request()->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Handle trashed filter
        if (request()->get('trashed') === '1') {
            $query->onlyTrashed();
        }

        $perPage = request()->get('per_page', 10);
        $page = request()->get('page', 1);

        $schoolClasses = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($schoolClasses);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('settings/SchoolClasses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolClassRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        SchoolClass::create($validated);

        return redirect()->route('school-classes.index')->with('success', 'School class created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolClass $schoolClass): Response
    {
        return Inertia::render('settings/SchoolClasses/Edit', [
            'schoolClass' => $schoolClass,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $validated = $request->validated();

        $schoolClass->update($validated);

        return redirect()->route('school-classes.index')->with('success', 'School class updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->delete();

        return redirect()->route('school-classes.index')->with('success', 'School class deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->update(['is_active' => false]);

        return redirect()->route('school-classes.index')->with('success', 'School class inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(SchoolClass $schoolClass): RedirectResponse
    {
        $schoolClass->update(['is_active' => true]);

        return redirect()->route('school-classes.index')->with('success', 'School class activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $schoolClass = SchoolClass::onlyTrashed()->findOrFail($id);
        $schoolClass->restore();

        return redirect()->route('school-classes.index')->with('success', 'School class restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $schoolClass = SchoolClass::onlyTrashed()->findOrFail($id);
        $schoolClass->forceDelete();

        return redirect()->route('school-classes.index')->with('success', 'School class permanently deleted.');
    }
}
