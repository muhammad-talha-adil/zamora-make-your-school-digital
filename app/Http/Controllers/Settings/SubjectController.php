<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BulkStoreSubjectRequest;
use App\Http\Requests\Settings\StoreSubjectRequest;
use App\Http\Requests\Settings\UpdateSubjectRequest;
use App\Models\Campus;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Subject::class);

        $subjects = $this->scopedQuery()
            ->orderBy('name', 'asc')
            ->paginate(10);

        $user = request()->user();

        return Inertia::render('settings/Subjects/Index', [
            'tableSubjects' => $subjects,
            'isSuperAdmin' => $user?->isSuperAdmin() ?? false,
            'campuses' => $user?->isSuperAdmin()
                ? Campus::query()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    /**
     * A subject's campus is applied once here rather than in every method
     * below that lists subjects, so a non-super-admin never sees (or can
     * accidentally edit) a subject outside their own campus.
     */
    private function scopedQuery(): Builder
    {
        $query = Subject::query()->with('campuses:id,name');

        $campusId = request()->user()?->campusId();
        if ($campusId) {
            $query->whereHas('campuses', fn ($q) => $q->where('campuses.id', $campusId));
        }

        return $query;
    }

    /**
     * API: Display a listing of subjects (for filtering).
     */
    public function apiIndex(): JsonResponse
    {
        Gate::authorize('viewAny', Subject::class);

        $query = $this->scopedQuery();

        if (request()->has('status')) {
            $status = request('status');
            if ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'active') {
                $query->where('is_active', true);
            }
        }

        $subjects = $query->orderBy('name', 'asc')
            ->paginate((int) request('per_page', 10));

        return response()->json($subjects);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Subject::class);

        return Inertia::render('settings/Subjects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubjectRequest $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Subject::class);

        $validated = $request->validated();
        $campusIds = $this->resolveCampusIds($request, $validated);
        unset($validated['campus_ids']);

        $subject = Subject::create($validated);
        $subject->campuses()->sync($campusIds);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully.',
            ]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject created successfully.');
    }

    /**
     * Creates every subject name at once, each synced to the picked
     * campuses. A name already in use (subject names are unique school-wide)
     * is skipped rather than failing the whole batch.
     */
    public function bulkStore(BulkStoreSubjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Subject::class);

        $validated = $request->validated();
        $user = $request->user();
        $campusIds = $this->resolveCampusIds($request, $validated);

        $names = collect($validated['subject_names'])->map(fn ($n) => trim($n))->filter()->unique();
        $existingNames = Subject::query()->whereIn('name', $names)->pluck('name');

        $created = 0;
        $skipped = [];

        foreach ($names as $name) {
            if ($existingNames->contains($name)) {
                $skipped[] = $name;

                continue;
            }

            $subject = Subject::create(['name' => $name, 'is_active' => true]);
            $subject->campuses()->sync($campusIds);
            $created++;
        }

        $message = "Created {$created} subject".($created === 1 ? '' : 's').'.';
        if (! empty($skipped)) {
            $message .= ' Already existed, skipped: '.implode(', ', $skipped).'.';
        }

        return response()->json(['success' => true, 'message' => $message, 'created' => $created, 'skipped' => $skipped]);
    }

    /**
     * The campuses a subject should be attached to on save. Only an
     * owner/developer may choose; everyone else is scoped to their own
     * campus regardless of what (if anything) was submitted.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, int>
     */
    private function resolveCampusIds(Request $request, array $validated): array
    {
        $user = $request->user();

        if (! $user?->isSuperAdmin()) {
            $campusId = $user?->campusId();

            return $campusId ? [$campusId] : [];
        }

        return $validated['campus_ids'] ?? [];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject): Response
    {
        Gate::authorize('update', $subject);

        $subject->load('campuses:id');
        $user = request()->user();

        return Inertia::render('settings/Subjects/Edit', [
            'subject' => $subject,
            'isSuperAdmin' => $user?->isSuperAdmin() ?? false,
            'campuses' => $user?->isSuperAdmin()
                ? Campus::query()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $subject);

        $validated = $request->validated();
        $campusIds = $this->resolveCampusIds($request, $validated);
        unset($validated['campus_ids']);

        $subject->update($validated);
        $subject->campuses()->sync($campusIds);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully.',
            ]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Subject $subject): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $subject);

        $subject->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Subject $subject): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $subject);

        $subject->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Subject $subject): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $subject);

        $subject->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Subject::class);

        $subject = Subject::onlyTrashed()->findOrFail($id);
        $subject->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse|JsonResponse
    {
        $subject = Subject::onlyTrashed()->findOrFail($id);

        Gate::authorize('delete', $subject);

        $subject->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('subjects.index')->with('success', 'Subject permanently deleted.');
    }
}
