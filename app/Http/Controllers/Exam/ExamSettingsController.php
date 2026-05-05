<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\ExamType;
use App\Models\Exam\GradeSystem;
use App\Models\Exam\GradeSystemItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamSettingsController extends Controller
{
    /**
     * Display exam settings page.
     */
    public function indexPage()
    {
        $gradeSystems = GradeSystem::with('gradeSystemItems')
            ->orderBy('name')
            ->get();

        $examTypes = ExamType::orderBy('id', 'desc')->paginate(10);

        return Inertia::render('Exam/Settings/Index', [
            'gradeSystems' => $gradeSystems,
            'examTypes' => $examTypes,
        ]);
    }

    /**
     * Display grade scales page.
     */
    public function gradeScalesPage()
    {
        $gradeScales = GradeSystem::with('gradeSystemItems')
            ->orderBy('name')
            ->get();

        return Inertia::render('Exam/Settings/GradeScales/Index', [
            'gradeScales' => $gradeScales,
        ]);
    }

    /**
     * Display create grade scale page.
     */
    public function createGradeScalePage()
    {
        return Inertia::render('Exam/Settings/GradeScales/Create');
    }

    /**
     * Display edit grade scale page.
     */
    public function editGradeScalePage($id)
    {
        $gradeScale = GradeSystem::with('gradeSystemItems')
            ->findOrFail($id);

        return Inertia::render('Exam/Settings/GradeScales/Edit', [
            'gradeScale' => $gradeScale,
        ]);
    }

    /**
     * Get all grade scales.
     */
    public function getGradeScales()
    {
        $gradeScales = GradeSystem::with('gradeSystemItems')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $gradeScales]);
    }

    /**
     * Store a new grade scale.
     */
    public function storeGradeScale(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:grade_systems,name',
            'rounding_mode' => 'nullable|in:round,floor,ceil,half_up,half_down',
            'precision' => 'nullable|integer|min:0|max:2',
            'is_default' => 'nullable|boolean',
        ]);

        $gradeScale = GradeSystem::create($validated);

        return response()->json(['data' => $gradeScale], 201);
    }

    /**
     * Update a grade scale.
     */
    public function updateGradeScale(Request $request, $id)
    {
        $gradeScale = GradeSystem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:grade_systems,name,'.$id,
            'rounding_mode' => 'nullable|in:round,floor,ceil,half_up,half_down',
            'precision' => 'nullable|integer|min:0|max:2',
            'is_default' => 'nullable|boolean',
        ]);

        $gradeScale->update($validated);

        return response()->json(['data' => $gradeScale]);
    }

    /**
     * Delete a grade scale.
     */
    public function deleteGradeScale($id)
    {
        $gradeScale = GradeSystem::findOrFail($id);
        $gradeScale->delete();

        return response()->json(['message' => 'Grade scale deleted successfully']);
    }

    /**
     * Set a grade scale as default.
     */
    public function setDefaultGradeScale(Request $request, $id)
    {
        $gradeScale = GradeSystem::findOrFail($id);

        // Unset default from all other grade scales
        GradeSystem::where('id', '!=', $id)->update(['is_default' => false]);

        // Set this one as default
        $gradeScale->update(['is_default' => true]);

        return response()->json(['data' => $gradeScale->fresh()]);
    }

    /**
     * Set a grade scale as active.
     */
    public function setActiveGradeScale(Request $request, $id)
    {
        $gradeScale = GradeSystem::findOrFail($id);

        // Deactivate all other grade scales
        GradeSystem::where('id', '!=', $id)->update(['is_active' => false]);

        // Activate the selected one
        $gradeScale->update(['is_active' => true]);

        return response()->json(['data' => $gradeScale->fresh()]);
    }

    /**
     * Get grade scale items.
     */
    public function getGradeScaleItems($id)
    {
        $gradeScale = GradeSystem::findOrFail($id);
        $items = $gradeScale->gradeSystemItems()->orderBy('min_percentage', 'desc')->get();

        return response()->json(['data' => $items]);
    }

    /**
     * Store a grade scale item.
     */
    public function storeGradeScaleItem(Request $request, $id)
    {
        $gradeScale = GradeSystem::findOrFail($id);

        $validated = $request->validate([
            'grade_letter' => 'required|string|max:255|unique:grade_system_items,grade_letter,NULL,id,grade_system_id,'.$id,
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'nullable|numeric|min:0|max:100|gte:min_percentage',
            'grade_point' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_pass' => 'nullable|boolean',
        ]);

        $item = $gradeScale->gradeSystemItems()->create($validated);

        return response()->json(['data' => $item], 201);
    }

    /**
     * Update a grade scale item.
     */
    public function updateGradeScaleItem(Request $request, $id, $itemId)
    {
        $gradeScale = GradeSystem::findOrFail($id);
        $item = GradeSystemItem::where('grade_system_id', $id)->findOrFail($itemId);

        $validated = $request->validate([
            'grade_letter' => 'required|string|max:255|unique:grade_system_items,grade_letter,'.$itemId.',id,grade_system_id,'.$id,
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'nullable|numeric|min:0|max:100|gte:min_percentage',
            'grade_point' => 'nullable|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_pass' => 'nullable|boolean',
        ]);

        $item->update($validated);

        return response()->json(['data' => $item]);
    }

    /**
     * Delete a grade scale item.
     */
    public function deleteGradeScaleItem($id, $itemId)
    {
        $gradeScale = GradeSystem::findOrFail($id);
        $item = GradeSystemItem::where('grade_system_id', $id)->findOrFail($itemId);

        $item->delete();

        return response()->json(['message' => 'Grade scale item deleted successfully']);
    }
}
