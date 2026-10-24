<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSchoolClassRequest;
use App\Http\Requests\Settings\UpdateSchoolClassRequest;
use App\Models\Campus;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', SchoolClass::class);

        $schoolClasses = SchoolClass::with('campus:id,name')
            ->visibleTo(request()->user())
            ->orderBy('id', 'desc')
            ->paginate(10);

        $user = request()->user();

        return Inertia::render('settings/SchoolClasses/Index', [
            'tableSchoolClasses' => $schoolClasses,
            'isSuperAdmin' => $user?->isSuperAdmin() ?? false,
            'campuses' => $user?->isSuperAdmin()
                ? Campus::query()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    /**
     * Handle API requests for class listing with filtering.
     */
    public function apiIndex()
    {
        Gate::authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::query()->visibleTo(request()->user());

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
        Gate::authorize('create', SchoolClass::class);

        return Inertia::render('settings/SchoolClasses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSchoolClassRequest $request)
    {
        Gate::authorize('create', SchoolClass::class);

        $validated = $request->validated();
        $user = $request->user();

        // A class is one row per campus (Sections, enrollment, fee and
        // attendance all key off a class's single campus_id) - an
        // owner/developer picking several campuses gets one identical class
        // created in each, rather than one row shared across them.
        $campusIds = ($user->isSuperAdmin() && ! empty($validated['campus_ids']))
            ? $validated['campus_ids']
            : [$user->campusId()];

        $conflictingCampusIds = SchoolClass::query()
            ->whereIn('campus_id', $campusIds)
            ->where('name', $validated['name'])
            ->pluck('campus_id');

        $duplicateCampusNames = Campus::query()->whereIn('id', $conflictingCampusIds)->pluck('name');

        if ($duplicateCampusNames->isNotEmpty()) {
            $message = 'A class named "'.$validated['name'].'" already exists in: '.$duplicateCampusNames->implode(', ').'.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'errors' => ['name' => [$message]]], 422);
            }

            return redirect()->back()->withErrors(['name' => $message]);
        }

        $schoolClasses = collect($campusIds)->map(fn ($campusId) => SchoolClass::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'campus_id' => $campusId,
        ]));

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $schoolClasses->count() > 1
                    ? 'Class created in '.$schoolClasses->count().' campuses.'
                    : 'Class created successfully.',
                'schoolClass' => $schoolClasses->first(),
            ]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolClass $schoolClass): Response
    {
        Gate::authorize('update', $schoolClass);

        $user = request()->user();

        return Inertia::render('settings/SchoolClasses/Edit', [
            'schoolClass' => $schoolClass,
            'isSuperAdmin' => $user?->isSuperAdmin() ?? false,
            'campuses' => $user?->isSuperAdmin()
                ? Campus::query()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass)
    {
        Gate::authorize('update', $schoolClass);

        $validated = $request->validated();
        // is_active should not be changed through form; preserve existing value
        // (if somehow sent, ignore it)
        unset($validated['is_active']);

        // Only an owner/developer may move a class to a different campus -
        // everyone else keeps it where it already is regardless of what was
        // sent (they never see the control to begin with).
        if (! $request->user()->isSuperAdmin() || empty($validated['campus_id'])) {
            unset($validated['campus_id']);
        }

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
        Gate::authorize('delete', $schoolClass);

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
        Gate::authorize('update', $schoolClass);

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
        Gate::authorize('update', $schoolClass);

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
        Gate::authorize('create', SchoolClass::class);

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

        Gate::authorize('delete', $schoolClass);

        $schoolClass->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'School class permanently deleted.');
    }
}
