<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Exam\ExamType;
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
        return Inertia::render('settings/ExamTypes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse
    {
        request()->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        ExamType::create([
            'name' => request('name'),
            'short_name' => request('short_name'),
            'is_active' => request('is_active', true),
        ]);

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamType $examType): Response
    {
        return Inertia::render('settings/ExamTypes/Edit', [
            'examType' => $examType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExamType $examType): RedirectResponse
    {
        request()->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $examType->update([
            'name' => request('name'),
            'short_name' => request('short_name'),
            'is_active' => request('is_active', true),
        ]);

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(ExamType $examType): RedirectResponse
    {
        $examType->delete();

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate(ExamType $examType): RedirectResponse
    {
        $examType->update(['is_active' => false]);

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type inactivated successfully.');
    }

    /**
     * Activate the specified resource.
     */
    public function activate(ExamType $examType): RedirectResponse
    {
        $examType->update(['is_active' => true]);

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type activated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $examType = ExamType::onlyTrashed()->findOrFail($id);
        $examType->restore();

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $examType = ExamType::onlyTrashed()->findOrFail($id);
        $examType->forceDelete();

        return redirect()->route('settings.exam-types.index')->with('success', 'Exam type permanently deleted.');
    }
}
