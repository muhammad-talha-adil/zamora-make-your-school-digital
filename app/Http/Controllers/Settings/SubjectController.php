<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSubjectRequest;
use App\Http\Requests\Settings\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $subjects = Subject::orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('settings/Subjects/Index', [
            'tableSubjects' => $subjects,
        ]);
    }

    /**
     * API: Display a listing of subjects (for filtering).
     */
    public function apiIndex(): JsonResponse
    {
        $query = Subject::query();

        if (request()->has('status')) {
            $status = request('status');
            if ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'active') {
                $query->where('is_active', true);
            }
        }

        $subjects = $query->orderBy('id', 'desc')
            ->paginate((int) request('per_page', 10));

        return response()->json($subjects);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('settings/Subjects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Subject::create($validated);

        return redirect()->route('subjects.index')->with('success', 'Subject created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subject $subject): Response
    {
        return Inertia::render('settings/Subjects/Edit', [
            'subject' => $subject,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validated();

        $subject->update($validated);

        return redirect()->route('subjects.index')->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('subjects.index')->with('success', 'Subject deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(Subject $subject): RedirectResponse
    {
        $subject->update(['is_active' => false]);

        return redirect()->route('subjects.index')->with('success', 'Subject inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(Subject $subject): RedirectResponse
    {
        $subject->update(['is_active' => true]);

        return redirect()->route('subjects.index')->with('success', 'Subject activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $subject = Subject::onlyTrashed()->findOrFail($id);
        $subject->restore();

        return redirect()->route('subjects.index')->with('success', 'Subject restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $subject = Subject::onlyTrashed()->findOrFail($id);
        $subject->forceDelete();

        return redirect()->route('subjects.index')->with('success', 'Subject permanently deleted.');
    }
}
