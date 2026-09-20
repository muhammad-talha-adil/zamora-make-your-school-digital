<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Exam\ExamType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExamTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('exam.settings');

        $examTypes = ExamType::orderBy('id', 'desc')
            ->paginate(10);

        return Inertia::render('settings/ExamTypes/Index', [
            'tableExamTypes' => $examTypes,
        ]);
    }

    /**
     * API: Display a listing of exam types (for filtering).
     */
    public function apiIndex(): JsonResponse
    {
        $this->authorize('exam.settings');

        $query = ExamType::query();

        if (request()->has('status')) {
            $status = request('status');
            if ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'active') {
                $query->where('is_active', true);
            }
        }

        $examTypes = $query->orderBy('id', 'desc')
            ->paginate((int) request('per_page', 10));

        return response()->json($examTypes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('exam.settings');

        return Inertia::render('settings/ExamTypes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse
    {
        $this->authorize('exam.settings');

        request()->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $isActive = request()->boolean('is_active', true);

        // Only one exam type may be active at a time: the same rule
        // activate() enforces, applied here so this form cannot create a
        // second one.
        if ($isActive) {
            ExamType::query()->update(['is_active' => false]);
        }

        ExamType::create([
            'name' => request('name'),
            'short_name' => request('short_name'),
            'is_active' => $isActive,
        ]);

        return redirect()->back()->with('success', 'Exam type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamType $examType): Response
    {
        $this->authorize('exam.settings');

        return Inertia::render('settings/ExamTypes/Edit', [
            'examType' => $examType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExamType $examType): RedirectResponse
    {
        $this->authorize('exam.settings');

        request()->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $isActive = request()->boolean('is_active', true);

        // Same single-active-exam-type rule as store(): edit-in-place must
        // not be able to leave two exam types active at once.
        if ($isActive) {
            ExamType::where('id', '!=', $examType->id)->update(['is_active' => false]);
        }

        $examType->update([
            'name' => request('name'),
            'short_name' => request('short_name'),
            'is_active' => $isActive,
        ]);

        return redirect()->back()->with('success', 'Exam type updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(ExamType $examType): RedirectResponse
    {
        $this->authorize('exam.settings');

        try {
            $examType->delete();
        } catch (QueryException $exception) {
            return redirect()->back()
                ->with('error', 'This exam type cannot be deleted because it is already in use by an exam.');
        }

        return redirect()->back()->with('success', 'Exam type deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(ExamType $examType): RedirectResponse
    {
        $this->authorize('exam.settings');

        // A school always needs exactly one current exam type for exam/
        // marking screens that resolve "the" active exam type — so the last
        // one standing cannot be switched off.
        if ($examType->is_active && ExamType::where('is_active', true)->count() <= 1) {
            return redirect()->back()
                ->with('error', 'This is the only active exam type, so it cannot be deactivated. Activate another exam type first.');
        }

        $examType->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Exam type inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(ExamType $examType): RedirectResponse
    {
        $this->authorize('exam.settings');

        // Deactivate all other exam types first
        ExamType::where('id', '!=', $examType->id)->update(['is_active' => false]);

        $examType->update(['is_active' => true]);

        return redirect()->back()->with('success', 'Exam type activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $this->authorize('exam.settings');

        $examType = ExamType::onlyTrashed()->findOrFail($id);
        $examType->restore();

        return redirect()->back()->with('success', 'Exam type restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $this->authorize('exam.settings');

        $examType = ExamType::onlyTrashed()->findOrFail($id);

        try {
            $examType->forceDelete();
        } catch (QueryException $exception) {
            return redirect()->back()
                ->with('error', 'This exam type cannot be permanently deleted because it is already in use by an exam.');
        }

        return redirect()->back()->with('success', 'Exam type permanently deleted.');
    }
}
