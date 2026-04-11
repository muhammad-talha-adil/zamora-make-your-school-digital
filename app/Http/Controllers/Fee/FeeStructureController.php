<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeHeadCategory;
use App\Enums\Fee\FeeStructureStatus;
use App\Http\Controllers\Controller;
use App\Models\Fee\FeeStructure;
use App\Models\Session;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Month;
use App\Models\Fee\FeeHead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeStructureController extends Controller
{
    /**
     * Display a listing of fee structures.
     */
    public function index(Request $request)
    {
        $query = FeeStructure::with(['session', 'campus', 'class', 'section'])
            ->withCount('items');

        // Apply filters
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $structures = $query->latest()->get();

        // Transform structures for frontend
        $transformedStructures = $structures->map(function ($structure) {
            return [
                'id' => $structure->id,
                'title' => $structure->title,
                'session' => $structure->session ? [
                    'id' => $structure->session->id,
                    'name' => $structure->session->name,
                ] : null,
                'campus' => $structure->campus ? [
                    'id' => $structure->campus->id,
                    'name' => $structure->campus->name,
                ] : null,
                'class' => $structure->class ? [
                    'id' => $structure->class->id,
                    'name' => $structure->class->name,
                ] : null,
                'section' => $structure->section ? [
                    'id' => $structure->section->id,
                    'name' => $structure->section->name,
                ] : null,
                'status' => $structure->status instanceof FeeStructureStatus ? $structure->status->value : $structure->status,
                'effective_from' => $structure->effective_from?->toDateString(),
                'effective_to' => $structure->effective_to?->toDateString(),
                'items_count' => $structure->items_count,
            ];
        });

        return Inertia::render('Fee/Structures/Index', [
            'structures' => $transformedStructures,
            'sessions' => Session::select('id', 'name')->get(),
            'campuses' => Campus::select('id', 'name')->get(),
            'classes' => SchoolClass::select('id', 'name')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->get(),
            'filters' => $request->only(['session_id', 'campus_id', 'class_id', 'section_id', 'status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new fee structure.
     */
    public function create()
    {
        // Get the currently active session for auto-selection
        $activeSession = Session::where('is_active', true)->first();

        return Inertia::render('Fee/Structures/Create', [
            'sessions' => Session::select('id', 'name', 'start_date', 'end_date')->get(),
            'active_session_id' => $activeSession?->id,
            'campuses' => Campus::select('id', 'name')->get(),
            'classes' => SchoolClass::select('id', 'name')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->get(),
            'months' => Month::select('id', 'name', 'month_number')->orderBy('month_number')->get(),
            'feeHeads' => FeeHead::active()->select('id', 'name', 'category', 'default_frequency')->ordered()->get(),
        ]);
    }

    /**
     * Store a newly created fee structure.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'session_id' => 'required|exists:academic_sessions,id',
            'campus_id' => 'required|exists:campuses,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_ids' => 'nullable|array', // Changed from section_id
            'section_ids.*' => 'exists:sections,id', // Validate each section ID
            'status' => 'required|in:draft,active,inactive',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.fee_head_id' => 'required_with:items|exists:fee_heads,id',
            'items.*.amount' => 'required_with:items|numeric|min:0',
        ]);

        // Get session to auto-fill effective dates
        $session = Session::find($validated['session_id']);
        $validated['effective_from'] = $session->start_date;
        $validated['effective_to'] = $session->end_date;

        // Check for duplicate title for the same session/campus/class/section combination
        $sectionId = !empty($validated['section_ids']) ? $validated['section_ids'][0] : null;
        $existingStructure = FeeStructure::where('session_id', $validated['session_id'])
            ->where('campus_id', $validated['campus_id'])
            ->where('class_id', $validated['class_id'] ?? null)
            ->where('section_id', $sectionId)
            ->where('title', $validated['title'])
            ->first();

        if ($existingStructure) {
            return response()->json([
                'message' => 'A fee structure with this title already exists for the selected session, campus, class and section. Please use a different title or change the section.',
            ], 422);
        }

        $validated['created_by'] = auth()->id();
        
        // Get section_ids from validated data
        $sectionIds = $validated['section_ids'] ?? [];
        unset($validated['section_ids']); // Remove from base data

        // If no sections selected, create ONE record with NULL section (applies to all)
        // If sections selected, create ONE record per section
        $structures = [];
        
        $sectionsToCreate = empty($sectionIds) ? [null] : $sectionIds;
        
        foreach ($sectionsToCreate as $sectionId) {
            $structureData = $validated;
            $structureData['section_id'] = $sectionId;
            
            $structure = FeeStructure::create($structureData);
            $structures[] = $structure;
            
            // Create fee structure items for this structure
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $feeHead = FeeHead::find($item['fee_head_id']);
                    $frequency = $feeHead?->default_frequency instanceof FeeFrequency
                        ? $feeHead->default_frequency->value
                        : ($feeHead?->default_frequency ?? 'monthly');
                    
                    $structure->items()->create([
                        'fee_head_id' => $item['fee_head_id'],
                        'amount' => $item['amount'],
                        'frequency' => $frequency,
                    ]);
                }
            }
        }

        $count = count($structures);
        $message = $count === 1 
            ? 'Fee structure created successfully with ' . count($validated['items'] ?? []) . ' fee items.'
            : 'Fee structures created successfully for ' . $count . ' sections with ' . count($validated['items'] ?? []) . ' fee items each.';

        return redirect()->route('fee.structures.index')
            ->with('success', $message);
    }

    /**
     * Display the specified fee structure.
     */
    public function show($feeStructure)
    {
        // Use explicit find to handle soft deletes properly
        $structure = FeeStructure::with(['session', 'campus', 'class', 'section', 'items.feeHead', 'creator'])
            ->findOrFail($feeStructure);

        // Transform the structure data for frontend (matching index method format)
        $data = [
            'id' => $structure->id,
            'title' => $structure->title,
            'session' => $structure->session ? [
                'id' => $structure->session->id,
                'name' => $structure->session->name,
            ] : null,
            'campus' => $structure->campus ? [
                'id' => $structure->campus->id,
                'name' => $structure->campus->name,
            ] : null,
            'class' => $structure->class ? [
                'id' => $structure->class->id,
                'name' => $structure->class->name,
            ] : null,
            'section' => $structure->section ? [
                'id' => $structure->section->id,
                'name' => $structure->section->name,
            ] : null,
            'status' => $structure->status instanceof FeeStructureStatus ? $structure->status->value : $structure->status,
            'effective_from' => $structure->effective_from?->toDateString(),
            'effective_to' => $structure->effective_to?->toDateString(),
            'notes' => $structure->notes,
            'created_by' => $structure->creator ? [
                'id' => $structure->creator->id,
                'name' => $structure->creator->name,
            ] : null,
            'items' => $structure->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fee_head' => $item->feeHead ? [
                        'id' => $item->feeHead->id,
                        'name' => $item->feeHead->name,
                    ] : null,
                    'amount' => (float) $item->amount,
                    'type' => $item->feeHead?->category instanceof FeeHeadCategory ? $item->feeHead->category->value : $item->feeHead?->category,
                    'frequency' => $item->frequency instanceof FeeFrequency ? $item->frequency->value : $item->frequency,
                    'is_optional' => $item->is_optional,
                    'applicable_on_admission' => $item->applicable_on_admission,
                ];
            })->toArray(),
        ];

        return Inertia::render('Fee/Structures/Show', [
            'structure' => $data,
        ]);
    }

    /**
     * Show the form for editing the specified fee structure.
     */
    public function edit(FeeStructure $structure)
    {
        // Find ALL related structures (same session/campus/class, different sections)
        $relatedStructures = FeeStructure::where('session_id', $structure->session_id)
            ->where('campus_id', $structure->campus_id)
            ->where('class_id', $structure->class_id)
            ->where('title', $structure->title)
            ->get();

        // Get all section IDs from related structures
        $sectionIds = $relatedStructures->pluck('section_id')->filter()->values()->toArray();

        // Load items for the main structure being edited
        $structure->load(['session', 'campus', 'class', 'section', 'items.feeHead']);

        // Get the currently active session for reference
        $activeSession = Session::where('is_active', true)->first();

        // Transform the structure data for frontend
        $data = [
            'id' => $structure->id,
            'title' => $structure->title,
            'session_id' => $structure->session_id,
            'campus_id' => $structure->campus_id,
            'class_id' => $structure->class_id,
            'section_ids' => $sectionIds, // Array of section IDs
            'status' => $structure->status instanceof FeeStructureStatus ? $structure->status->value : $structure->status,
            'effective_from' => $structure->effective_from?->toDateString(),
            'effective_to' => $structure->effective_to?->toDateString(),
            'notes' => $structure->notes,
            'items' => $structure->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fee_head_id' => $item->fee_head_id,
                    'fee_head' => $item->feeHead ? [
                        'id' => $item->feeHead->id,
                        'name' => $item->feeHead->name,
                        'category' => $item->feeHead->category instanceof FeeHeadCategory ? $item->feeHead->category->value : $item->feeHead->category,
                        'default_frequency' => $item->feeHead->default_frequency,
                    ] : null,
                    'amount' => (float) $item->amount,
                    'frequency' => $item->frequency instanceof FeeFrequency ? $item->frequency->value : $item->frequency,
                    'is_optional' => $item->is_optional,
                    'applicable_on_admission' => $item->applicable_on_admission,
                ];
            })->toArray(),
        ];

        return Inertia::render('Fee/Structures/Edit', [
            'structure' => $data,
            'active_session_id' => $activeSession?->id,
            'sessions' => Session::select('id', 'name', 'start_date', 'end_date')->get(),
            'campuses' => Campus::select('id', 'name')->get(),
            'classes' => SchoolClass::select('id', 'name')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->get(),
            'months' => Month::select('id', 'name', 'month_number')->orderBy('month_number')->get(),
            'feeHeads' => FeeHead::active()->select('id', 'name', 'category', 'default_frequency')->ordered()->get(),
        ]);
    }

    /**
     * Update the specified fee structure.
     */
    public function update(Request $request, FeeStructure $structure)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'session_id' => 'required|exists:academic_sessions,id',
            'campus_id' => 'required|exists:campuses,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
            'status' => 'required|in:draft,active,inactive',
            'notes' => 'nullable|string',
        ]);

        // Get session to auto-fill effective dates
        $session = Session::find($validated['session_id']);
        $validated['effective_from'] = $session->start_date;
        $validated['effective_to'] = $session->end_date;

        // Check for duplicate title for the same session/campus/class/section combination
        // but exclude the current structure being updated
        $sectionId = !empty($validated['section_ids']) ? $validated['section_ids'][0] : null;
        $existingStructure = FeeStructure::where('session_id', $validated['session_id'])
            ->where('campus_id', $validated['campus_id'])
            ->where('class_id', $validated['class_id'] ?? null)
            ->where('section_id', $sectionId)
            ->where('title', $validated['title'])
            ->where('id', '!=', $structure->id)
            ->first();

        if ($existingStructure) {
            return response()->json([
                'message' => 'A fee structure with this title already exists for the selected session, campus, class and section. Please use a different title or change the section.',
            ], 422);
        }

        // Get section_ids from validated data
        $sectionIds = $validated['section_ids'] ?? [];
        unset($validated['section_ids']);

        // Get the existing related structures (same class/session/campus/title)
        $existingRelatedStructures = FeeStructure::where('session_id', $structure->session_id)
            ->where('campus_id', $structure->campus_id)
            ->where('class_id', $structure->class_id)
            ->where('title', $structure->title)
            ->where('id', '!=', $structure->id)
            ->get();

        // Determine which sections should have structures
        $sectionsToKeep = empty($sectionIds) ? [null] : $sectionIds;

        // Delete structures that are no longer selected
        foreach ($existingRelatedStructures as $relatedStructure) {
            if (!in_array($relatedStructure->section_id, $sectionsToKeep)) {
                $relatedStructure->delete();
            }
        }

        // Update or create structures for each section
        $structures = [];
        
        // First, update the main structure being edited
        $mainStructureData = $validated;
        $mainStructureData['section_id'] = $sectionIds[0] ?? null;
        $structure->update($mainStructureData);
        $structures[] = $structure;

        // Sync fee items from main structure to all related structures
        $mainItems = $structure->items()->get();

        // Then create/update structures for remaining sections
        $sectionsToProcess = empty($sectionIds) ? [null] : $sectionIds;
        array_shift($sectionsToProcess);
        
        foreach ($sectionsToProcess as $sectionId) {
            // Check if structure already exists for this section
            $existingStructure = FeeStructure::where('session_id', $structure->session_id)
                ->where('campus_id', $structure->campus_id)
                ->where('class_id', $structure->class_id)
                ->where('section_id', $sectionId)
                ->where('title', $structure->title)
                ->first();

            if ($existingStructure) {
                // Update existing structure
                $existingStructure->update($validated);
                $structures[] = $existingStructure;
            } else {
                // Create new structure for this section
                $newStructureData = $validated;
                $newStructureData['section_id'] = $sectionId;
                $newStructureData['created_by'] = auth()->id();
                $newStructure = FeeStructure::create($newStructureData);
                $structures[] = $newStructure;
            }
        }

        // Sync items across all structures
        foreach ($structures as $struct) {
            if ($struct->id === $structure->id) continue; // Skip main (already has items)
            
            // Delete existing items and copy from main structure
            $struct->items()->delete();
            foreach ($mainItems as $item) {
                $struct->items()->create([
                    'fee_head_id' => $item->fee_head_id,
                    'amount' => $item->amount,
                    'frequency' => $item->frequency,
                    'is_optional' => $item->is_optional,
                    'applicable_on_admission' => $item->applicable_on_admission,
                ]);
            }
        }

        return redirect()->route('fee.structures.index')
            ->with('success', 'Fee structures updated successfully.');
    }

    /**
     * Remove the specified fee structure.
     */
    public function destroy(FeeStructure $structure)
    {
        $structure->delete();

        return redirect()->route('fee.structures.index')
            ->with('success', 'Fee structure deleted successfully.');
    }

    /**
     * Get fee structure by scope (class/session/campus)
     * Used by admission form to auto-populate fees
     */
    public function getByScope(Request $request)
    {
        $sessionId = $request->query('session_id');
        $campusId = $request->query('campus_id');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');
        
        if (!$sessionId || !$campusId) {
            return response()->json([
                'success' => false,
                'message' => 'Session and Campus are required'
            ], 400);
        }

        // Convert empty strings to null for proper query handling
        $classId = $classId && $classId !== '' ? $classId : null;
        $sectionId = $sectionId && $sectionId !== '' ? $sectionId : null;

        // Try section-specific first (class_id AND section_id both set)
        if ($classId && $sectionId) {
            $structure = FeeStructure::active()
                ->with(['items.feeHead'])
                ->where('session_id', $sessionId)
                ->where('campus_id', $campusId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->effectiveOn(now())
                ->first();

            if ($structure) {
                return $this->formatFeeStructureResponse($structure);
            }
        }

        // Try class-specific (class_id set, section_id is null)
        if ($classId) {
            $structure = FeeStructure::active()
                ->with(['items.feeHead'])
                ->where('session_id', $sessionId)
                ->where('campus_id', $campusId)
                ->where('class_id', $classId)
                ->whereNull('section_id')
                ->effectiveOn(now())
                ->first();

            if ($structure) {
                return $this->formatFeeStructureResponse($structure);
            }
        }

        // Try campus-wide (both class_id and section_id are null)
        $structure = FeeStructure::active()
            ->with(['items.feeHead'])
            ->where('session_id', $sessionId)
            ->where('campus_id', $campusId)
            ->whereNull('class_id')
            ->whereNull('section_id')
            ->effectiveOn(now())
            ->first();

        if (!$structure) {
            return response()->json([
                'success' => false,
                'message' => 'No active fee structure found for this class'
            ]);
        }

        return $this->formatFeeStructureResponse($structure);
    }

    /**
     * Search fee structure titles
     * Used by combobox to find existing titles
     */
    public function searchTitles(Request $request)
    {
        $query = $request->query('q', '');
        $sessionId = $request->query('session_id');
        $campusId = $request->query('campus_id');
        $classId = $request->query('class_id');
        $sectionId = $request->query('section_id');

        $structures = FeeStructure::select('id', 'title', 'session_id', 'campus_id', 'class_id', 'section_id')
            ->when($query, fn($q) => $q->where('title', 'like', "%{$query}%"))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->orderBy('title')
            ->limit(20)
            ->get();

        return response()->json($structures);
    }

    /**
     * Format fee structure for response
     */
    private function formatFeeStructureResponse(FeeStructure $structure)
    {
        // Calculate totals
        $monthlyTotal = 0;
        $yearlyTotal = 0;
        $oneTimeTotal = 0;

        foreach ($structure->items as $item) {
            $amount = (float) $item->amount;
            $frequency = $item->frequency instanceof FeeFrequency 
                ? $item->frequency->value 
                : $item->frequency;
            
            // Convert to lowercase for case-insensitive comparison
            $frequency = strtolower($frequency);
            
            switch ($frequency) {
                case 'monthly':
                    $monthlyTotal += $amount;
                    break;
                case 'yearly':
                    $yearlyTotal += $amount;
                    break;
                case 'once':
                    $oneTimeTotal += $amount;
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $structure->id,
                'title' => $structure->title,
                'monthly_fee' => $monthlyTotal,
                'annual_fee' => $yearlyTotal,
                'one_time_fee' => $oneTimeTotal,
                'items' => $structure->items->map(function($item) {
                    // Convert frequency enum to string value for JSON response
                    $frequency = $item->frequency instanceof FeeFrequency 
                        ? $item->frequency->value 
                        : $item->frequency;
                    
                    return [
                        'id' => $item->id,
                        'fee_head_id' => $item->fee_head_id,
                        'fee_head' => $item->feeHead->name,
                        'amount' => $item->amount,
                        'frequency' => $frequency,
                        'is_optional' => $item->is_optional,
                        'applicable_on_admission' => $item->applicable_on_admission,
                    ];
                })
            ]
        ]);
    }
}
