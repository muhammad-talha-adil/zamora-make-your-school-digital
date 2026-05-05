<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\FineType;
use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeHead;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FineRuleController extends Controller
{
    /**
     * Display a listing of fine rules.
     */
    public function index(Request $request)
    {
        $transformedRules = $this->getTransformedRules($request);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'fineRules' => $transformedRules,
            ]);
        }

        return Inertia::render('Fee/Settings/FineRules', [
            'fineRules' => $transformedRules,
            'campuses' => Campus::select('id', 'name')->orderBy('name')->get(),
            'sessions' => Session::select('id', 'name')->orderBy('start_date', 'desc')->get(),
            'classes' => SchoolClass::select('id', 'name')->orderBy('name')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->orderBy('name')->get(),
            'feeHeads' => FeeHead::active()->select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only(['campus_id', 'session_id', 'class_id', 'is_active', 'search']),
        ]);
    }

    protected function getTransformedRules(Request $request)
    {
        $query = FeeFineRule::with(['campus', 'session', 'schoolClass', 'section', 'feeHead']);

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $fineRules = $query->latest()->get();

        // Transform for frontend
        return $fineRules->map(function ($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'campus_id' => $rule->campus_id,
                'session_id' => $rule->session_id,
                'class_id' => $rule->class_id,
                'section_id' => $rule->section_id,
                'fee_head_id' => $rule->fee_head_id,
                'grace_days' => $rule->grace_days,
                'fine_type' => $rule->fine_type instanceof FineType
                    ? $rule->fine_type->value
                    : $rule->fine_type,
                'fine_value' => (float) $rule->fine_value,
                'effective_from' => $rule->effective_from?->toDateString(),
                'effective_to' => $rule->effective_to?->toDateString(),
                'is_active' => $rule->is_active,
                'campus' => $rule->campus ? ['id' => $rule->campus->id, 'name' => $rule->campus->name] : null,
                'session' => $rule->session ? ['id' => $rule->session->id, 'name' => $rule->session->name] : null,
                'schoolClass' => $rule->schoolClass ? ['id' => $rule->schoolClass->id, 'name' => $rule->schoolClass->name] : null,
                'section' => $rule->section ? ['id' => $rule->section->id, 'name' => $rule->section->name] : null,
                'feeHead' => $rule->feeHead ? ['id' => $rule->feeHead->id, 'name' => $rule->feeHead->name] : null,
            ];
        });
    }

    /**
     * Store a newly created fine rule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'campus_id' => 'required|exists:campuses,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'fee_head_id' => 'nullable|exists:fee_heads,id',
            'grace_days' => 'required|integer|min:0',
            'fine_type' => 'required|in:fixed_per_day,fixed_once,percent',
            'fine_value' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $rule = FeeFineRule::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fine rule created successfully.',
                'fineRule' => $rule->fresh(),
            ]);
        }

        return back()->with('success', 'Fine rule created successfully.');
    }

    /**
     * Update the specified fine rule.
     */
    public function update(Request $request, FeeFineRule $fineRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'campus_id' => 'required|exists:campuses,id',
            'session_id' => 'required|exists:academic_sessions,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'fee_head_id' => 'nullable|exists:fee_heads,id',
            'grace_days' => 'required|integer|min:0',
            'fine_type' => 'required|in:fixed_per_day,fixed_once,percent',
            'fine_value' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
        ]);

        $fineRule->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fine rule updated successfully.',
                'fineRule' => $fineRule->fresh(),
            ]);
        }

        return back()->with('success', 'Fine rule updated successfully.');
    }

    /**
     * Remove the specified fine rule.
     */
    public function destroy(Request $request, FeeFineRule $fineRule)
    {
        $fineRule->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fine rule deleted successfully.',
            ]);
        }

        return back()->with('success', 'Fine rule deleted successfully.');
    }

    /**
     * Toggle fine rule status.
     */
    public function toggleStatus(Request $request, FeeFineRule $fineRule): JsonResponse
    {
        $fineRule->update(['is_active' => ! $fineRule->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Fine rule status updated successfully.',
            'fineRule' => $fineRule->fresh(),
        ]);
    }
}
