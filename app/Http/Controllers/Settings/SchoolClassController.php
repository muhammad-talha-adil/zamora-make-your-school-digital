<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSchoolClassRequest;
use App\Http\Requests\Settings\UpdateSchoolClassRequest;
use App\Models\SchoolClass;
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
    public function store(StoreSchoolClassRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = true; // New classes are always active

        SchoolClass::create($validated);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Class created successfully.']);
        }

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
    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass)
    {
        $validated = $request->validated();
        // is_active should not be changed through form; preserve existing value
        // (if somehow sent, ignore it)
        unset($validated['is_active']);

        $schoolClass->update($validated);

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Class updated successfully.']);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(SchoolClass $schoolClass)
    {
        $schoolClass->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(SchoolClass $schoolClass)
    {
        $schoolClass->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(SchoolClass $schoolClass)
    {
        $schoolClass->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id)
    {
        $schoolClass = SchoolClass::onlyTrashed()->findOrFail($id);
        $schoolClass->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id)
    {
        $schoolClass = SchoolClass::onlyTrashed()->findOrFail($id);
        $schoolClass->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class permanently deleted.');
    }
}
