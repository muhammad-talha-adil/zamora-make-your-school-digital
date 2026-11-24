<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BulkStoreSchoolClassRequest;
use App\Http\Requests\Settings\StoreSchoolClassRequest;
use App\Http\Requests\Settings\UpdateSchoolClassRequest;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
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
            ->orderBy('id', 'asc')
            ->paginate(10);

        $this->attachExistingCampusIds($schoolClasses);

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

        $query = SchoolClass::query()->with('campus:id,name')->visibleTo(request()->user());

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

        $schoolClasses = $query->orderBy('id', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $this->attachExistingCampusIds($schoolClasses);

        return response()->json($schoolClasses);
    }

    /**
     * For each class on the page, the campus_ids every class sharing its
     * name is already in (including its own) - so the edit modal's campus
     * checklist can lock the ones already taken and only offer to add it to
     * the rest, rather than risking a silent duplicate or an accidental
     * removal of a campus's own class row.
     */
    private function attachExistingCampusIds(LengthAwarePaginator $paginated): void
    {
        $names = $paginated->getCollection()->pluck('name')->unique();

        $byName = SchoolClass::query()
            ->whereIn('name', $names)
            ->get(['id', 'name', 'campus_id'])
            ->groupBy('name');

        $paginated->getCollection()->transform(function (SchoolClass $class) use ($byName) {
            $class->existing_campus_ids = $byName->get($class->name, collect())->pluck('campus_id')->values();

            return $class;
        });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('school-classes.index');
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
            $message = 'This class already exists in: '.$duplicateCampusNames->implode(', ').'.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message, 'errors' => ['name' => [$message]]], 422);
            }

            return redirect()->back()->withErrors(['name' => $message]);
        }

        $schoolClasses = collect($campusIds)->map(fn ($campusId) => SchoolClass::create([
            'name' => $validated['name'],
            'is_active' => true,
            'campus_id' => $campusId,
        ]));

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $schoolClasses->count() > 1
                    ? 'Class created in '.$schoolClasses->count().' campuses.'
                    : 'Class added.',
                'schoolClass' => $schoolClasses->first(),
            ]);
        }

        return redirect()->route('school-classes.index')->with('success', 'Class added.');
    }

    /**
     * Creates every (class name, campus) pair at once - e.g. five class
     * names across three campuses in one submit. A pair that already exists
     * (e.g. "Class 3" already in Campus 3) is skipped rather than
     * overwritten or failing the whole batch; the response names exactly
     * which ones were skipped so the office knows what to check manually.
     */
    public function bulkStore(BulkStoreSchoolClassRequest $request): JsonResponse
    {
        Gate::authorize('create', SchoolClass::class);

        $validated = $request->validated();
        $user = $request->user();

        $campusIds = ($user->isSuperAdmin() && ! empty($validated['campus_ids']))
            ? $validated['campus_ids']
            : [$user->campusId()];

        $names = collect($validated['class_names'])
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique();

        $existingPairs = SchoolClass::query()
            ->whereIn('campus_id', $campusIds)
            ->whereIn('name', $names)
            ->get(['campus_id', 'name'])
            ->map(fn ($class) => $class->campus_id.'|'.$class->name);

        $campusNames = Campus::query()->whereIn('id', $campusIds)->pluck('name', 'id');

        $created = 0;
        $skipped = [];

        foreach ($campusIds as $campusId) {
            foreach ($names as $name) {
                if ($existingPairs->contains($campusId.'|'.$name)) {
                    $skipped[] = "\"{$name}\" in {$campusNames[$campusId]}";

                    continue;
                }

                SchoolClass::create([
                    'name' => $name,
                    'is_active' => true,
                    'campus_id' => $campusId,
                ]);
                $created++;
            }
        }

        $message = "Added {$created} class".($created === 1 ? '' : 'es').'.';
        if (! empty($skipped)) {
            $message .= ' Already existed, skipped: '.implode(', ', $skipped).'.';
        }

        return response()->json(['success' => true, 'message' => $message, 'created' => $created, 'skipped' => $skipped]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolClass $schoolClass): RedirectResponse
    {
        return redirect()->route('school-classes.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSchoolClassRequest $request, SchoolClass $schoolClass)
    {
        Gate::authorize('update', $schoolClass);

        $validated = $request->validated();
        $campusIds = $validated['campus_ids'] ?? [];
        unset($validated['is_active'], $validated['campus_ids']);

        $schoolClass->update($validated);

        // Checking a campus this class wasn't already in adds it there too -
        // one new row per newly-checked campus, same name.
        // Unchecking an already-present one does nothing here; removing a
        // campus's class is done by deleting that campus's own row, not by
        // silently discarding it from someone else's edit.
        if ($request->user()->isSuperAdmin() && ! empty($campusIds)) {
            $alreadyPresentCampusIds = SchoolClass::query()
                ->where('name', $schoolClass->name)
                ->pluck('campus_id');

            $newCampusIds = collect($campusIds)->diff($alreadyPresentCampusIds);

            foreach ($newCampusIds as $campusId) {
                SchoolClass::create([
                    'name' => $schoolClass->name,
                    'is_active' => true,
                    'campus_id' => $campusId,
                ]);
            }
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Class saved.']);
        }

        return redirect()->route('school-classes.index')->with('success', 'Class saved.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(SchoolClass $schoolClass)
    {
        Gate::authorize('delete', $schoolClass);

        $students = StudentEnrollmentRecord::where('class_id', $schoolClass->id)->count();

        if ($students > 0) {
            $message = "{$schoolClass->name} cannot be deleted because {$students} student record".($students === 1 ? '' : 's').' use it. Move them to another class first.';

            return request()->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->route('school-classes.index')->with('error', $message);
        }

        $schoolClass->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('school-classes.index')->with('success', 'Class deleted.');
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

        return redirect()->route('school-classes.index')->with('success', 'Class inactivated.');
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

        return redirect()->route('school-classes.index')->with('success', 'Class activated.');
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

        return redirect()->route('school-classes.index')->with('success', 'Class restored.');
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

        return redirect()->route('school-classes.index')->with('success', 'Class permanently deleted.');
    }
}
