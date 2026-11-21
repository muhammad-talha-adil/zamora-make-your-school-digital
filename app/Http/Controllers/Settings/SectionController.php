<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BulkStoreSectionRequest;
use App\Http\Requests\Settings\StoreSectionRequest;
use App\Http\Requests\Settings\UpdateSectionRequest;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Section::class);

        $sections = Section::with('schoolClass')
            ->whereHas('schoolClass', fn ($q) => $q->visibleTo(request()->user()))
            ->orderBy('id', 'desc')
            ->paginate(10);

        $schoolClasses = SchoolClass::visibleTo(request()->user())->orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Sections/Index', [
            'tableSections' => $sections,
            'schoolClasses' => $schoolClasses,
        ]);
    }

    /**
     * Handle API requests for section listing with filtering.
     */
    public function apiIndex()
    {
        Gate::authorize('viewAny', Section::class);

        $query = Section::with('schoolClass')
            ->whereHas('schoolClass', fn ($q) => $q->visibleTo(request()->user()));

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
        Gate::authorize('create', Section::class);

        $schoolClasses = SchoolClass::visibleTo(request()->user())->orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Sections/Create', [
            'schoolClasses' => $schoolClasses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionRequest $request)
    {
        Gate::authorize('create', Section::class);

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
     * Creates every (class, section name) pair at once - e.g. "Boys" and
     * "Girls" across 8 classes in one submit, instead of 16 trips through
     * the single-section form. A pair that already exists is silently
     * skipped rather than failing the whole batch.
     */
    public function bulkStore(BulkStoreSectionRequest $request): JsonResponse
    {
        Gate::authorize('create', Section::class);

        $validated = $request->validated();

        // Defense in depth: only ever create inside classes this user can
        // actually see, even if a class id was tampered with client-side.
        $classIds = SchoolClass::query()
            ->visibleTo($request->user())
            ->whereIn('id', $validated['class_ids'])
            ->pluck('id');

        $names = collect($validated['section_names'])
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        $existingPairs = Section::query()
            ->whereIn('class_id', $classIds)
            ->whereIn('name', $names)
            ->get(['class_id', 'name'])
            ->map(fn ($section) => $section->class_id.'|'.$section->name);

        $classNames = SchoolClass::query()->whereIn('id', $classIds)->pluck('name', 'id');

        $created = 0;
        $skipped = [];

        foreach ($classIds as $classId) {
            foreach ($names as $name) {
                if ($existingPairs->contains($classId.'|'.$name)) {
                    $skipped[] = "\"{$name}\" in {$classNames[$classId]}";

                    continue;
                }

                Section::create([
                    'name' => $name,
                    'class_id' => $classId,
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        $message = "Created {$created} section".($created === 1 ? '' : 's').'.';
        if (! empty($skipped)) {
            $message .= ' Already existed, skipped: '.implode(', ', $skipped).'.';
        }

        return response()->json(['success' => true, 'message' => $message, 'created' => $created, 'skipped' => $skipped]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, Section $section)
    {
        Gate::authorize('update', $section);

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
        Gate::authorize('delete', $section);

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
        Gate::authorize('update', $section);

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
        Gate::authorize('update', $section);

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
        Gate::authorize('create', Section::class);

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

        Gate::authorize('delete', $section);

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
        Gate::authorize('update', $section);

        $schoolClasses = SchoolClass::visibleTo(request()->user())->orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('settings/Sections/Edit', [
            'section' => $section->load('schoolClass'),
            'schoolClasses' => $schoolClasses,
        ]);
    }
}
