<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\FeeFrequency;
use App\Enums\Fee\FeeHeadCategory;
use App\Enums\Fee\FeeStructureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\StoreFeeStructureRequest;
use App\Http\Requests\Fee\UpdateFeeStructureRequest;
use App\Models\Campus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use App\Models\Month;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\StudentEnrollmentRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeStructureController extends Controller
{
    /**
     * Display a listing of fee structures.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $structures = FeeStructure::with(['session', 'campus', 'class', 'section'])
            ->withCount('items');

        if ($request->filled('session_id')) {
            $structures->where('session_id', $request->session_id);
        }

        if ($request->filled('campus_id')) {
            $structures->where('campus_id', $request->campus_id);
        }

        if ($request->filled('class_id')) {
            $structures->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $structures->where('section_id', $request->section_id);
        }

        if ($request->filled('status')) {
            $structures->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $structures->where('title', 'like', '%'.$request->search.'%');
        }

        $paginatedStructures = $structures->latest()->paginate($perPage, ['*'], 'page', $page);

        $transformedStructures = $paginatedStructures->through(fn (FeeStructure $structure) => $this->transformStructure($structure));

        if ($request->expectsJson()) {
            return response()->json($transformedStructures);
        }

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
    public function store(StoreFeeStructureRequest $request)
    {
        $validated = $request->validated();

        // Get session to auto-fill effective dates
        $session = Session::find($validated['session_id']);
        $validated['effective_from'] = $session->start_date;
        $validated['effective_to'] = $session->end_date;

        $validated['created_by'] = auth()->id();

        // Get section_ids from validated data
        $sectionIds = $validated['section_ids'] ?? [];
        unset($validated['section_ids']); // Remove from base data

        $sectionsToCreate = empty($sectionIds) ? [null] : array_values($sectionIds);

        foreach ($sectionsToCreate as $sectionId) {
            $existingStructure = FeeStructure::where('session_id', $validated['session_id'])
                ->where('campus_id', $validated['campus_id'])
                ->where('class_id', $validated['class_id'] ?? null)
                ->where('section_id', $sectionId)
                ->where('title', $validated['title'])
                ->first();

            if ($existingStructure) {
                return response()->json([
                    'message' => 'A fee structure with this title already exists for one of the selected class/section combinations. Please use a different title or change the selection.',
                ], 422);
            }
        }

        // If no sections selected, create ONE record with NULL section (applies to all)
        // If sections selected, create ONE record per section
        $structures = [];

        foreach ($sectionsToCreate as $sectionId) {
            $structureData = $validated;
            unset($structureData['items']);
            $structureData['section_id'] = $sectionId;

            $structure = FeeStructure::create($structureData);
            $structures[] = $structure;

            // Create fee structure items for this structure
            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $structure->items()->create($this->itemAttributes($item));
                }
            }
        }

        $count = count($structures);
        $message = $count === 1
            ? 'Fee structure created successfully with '.count($validated['items'] ?? []).' fee items.'
            : 'Fee structures created successfully for '.$count.' sections with '.count($validated['items'] ?? []).' fee items each.';

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

    public function debug(FeeStructure $feeStructure): JsonResponse
    {
        $feeStructure->load(['session', 'campus', 'class', 'section', 'items.feeHead', 'creator']);

        return response()->json($feeStructure);
    }

    /**
     * Show the form for editing the specified fee structure.
     */
    public function edit(FeeStructure $feeStructure)
    {
        // Find ALL related structures (same session/campus/class, different sections)
        $relatedStructures = FeeStructure::where('session_id', $feeStructure->session_id)
            ->where('campus_id', $feeStructure->campus_id)
            ->where('class_id', $feeStructure->class_id)
            ->where('title', $feeStructure->title)
            ->get();

        // Get all section IDs from related structures
        $sectionIds = $relatedStructures->pluck('section_id')->filter()->values()->toArray();

        // Load items for the main structure being edited
        $feeStructure->load(['session', 'campus', 'class', 'section', 'items.feeHead']);

        // Get the currently active session for reference
        $activeSession = Session::where('is_active', true)->first();

        // Transform the structure data for frontend
        $data = [
            'id' => $feeStructure->id,
            'title' => $feeStructure->title,
            'session_id' => $feeStructure->session_id,
            'campus_id' => $feeStructure->campus_id,
            'class_id' => $feeStructure->class_id,
            'section_ids' => $sectionIds, // Array of section IDs
            'status' => $feeStructure->status instanceof FeeStructureStatus ? $feeStructure->status->value : $feeStructure->status,
            'effective_from' => $feeStructure->effective_from?->toDateString(),
            'effective_to' => $feeStructure->effective_to?->toDateString(),
            'notes' => $feeStructure->notes,
            'items' => $feeStructure->items->map(function ($item) {
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
            // So the office can see that an edit reaches real students before
            // it saves, rather than finding out from the next fee run.
            'usage' => $this->usageOf($feeStructure),
        ]);
    }

    /**
     * Update the specified fee structure.
     */
    public function update(UpdateFeeStructureRequest $request, FeeStructure $feeStructure)
    {
        $validated = $request->validated();

        // Get session to auto-fill effective dates
        $session = Session::find($validated['session_id']);
        $validated['effective_from'] = $session->start_date;
        $validated['effective_to'] = $session->end_date;

        // Get section_ids from validated data
        $sectionIds = $validated['section_ids'] ?? [];
        unset($validated['section_ids']);

        // Get the existing related structures (same class/session/campus/title)
        $existingRelatedStructures = FeeStructure::where('session_id', $feeStructure->session_id)
            ->where('campus_id', $feeStructure->campus_id)
            ->where('class_id', $feeStructure->class_id)
            ->where('title', $feeStructure->title)
            ->where('id', '!=', $feeStructure->id)
            ->get();

        $relatedStructureIds = $existingRelatedStructures->pluck('id')
            ->push($feeStructure->id)
            ->all();

        $sectionsToKeep = empty($sectionIds) ? [null] : array_values($sectionIds);

        foreach ($sectionsToKeep as $sectionId) {
            $duplicateQuery = FeeStructure::where('session_id', $validated['session_id'])
                ->where('campus_id', $validated['campus_id'])
                ->where('class_id', $validated['class_id'] ?? null)
                ->where('title', $validated['title'])
                ->whereNotIn('id', $relatedStructureIds);

            if ($sectionId === null) {
                $duplicateQuery->whereNull('section_id');
            } else {
                $duplicateQuery->where('section_id', $sectionId);
            }

            if ($duplicateQuery->exists()) {
                return response()->json([
                    'message' => 'A fee structure with this title already exists for one of the selected class/section combinations. Please use a different title or change the selection.',
                ], 422);
            }
        }

        // Determine which sections should have structures
        // Delete structures that are no longer selected
        foreach ($existingRelatedStructures as $relatedStructure) {
            if (! in_array($relatedStructure->section_id, $sectionsToKeep)) {
                $relatedStructure->delete();
            }
        }

        // Update or create structures for each section
        $structures = [];

        // First, update the main structure being edited
        $mainStructureData = $validated;
        unset($mainStructureData['items']);
        $mainStructureData['section_id'] = $sectionIds[0] ?? null;
        $feeStructure->update($mainStructureData);
        $structures[] = $feeStructure;

        // The edit screen changes charges one at a time through
        // `fee.structures.items.*` and sends no `items` key, so its updates must
        // leave them alone. A caller that does send the key states the whole set
        // and gets it synced; sending an empty array clears the charges.
        if ($request->has('items')) {
            $this->syncItems($feeStructure, $validated['items'] ?? []);
        }

        // Sync fee items from main structure to all related structures
        $mainItems = $feeStructure->items()->get();

        // Then create/update structures for remaining sections
        $sectionsToProcess = empty($sectionIds) ? [null] : $sectionIds;
        array_shift($sectionsToProcess);

        foreach ($sectionsToProcess as $sectionId) {
            // Check if structure already exists for this section
            $existingStructure = FeeStructure::where('session_id', $feeStructure->session_id)
                ->where('campus_id', $feeStructure->campus_id)
                ->where('class_id', $feeStructure->class_id)
                ->where('section_id', $sectionId)
                ->where('title', $feeStructure->title)
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
            if ($struct->id === $feeStructure->id) {
                continue;
            } // Skip main (already has items)

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
    public function destroy(FeeStructure $feeStructure)
    {
        $inUse = $this->usageOf($feeStructure);

        // Deleting a structure students are enrolled on, or that vouchers were
        // raised from, leaves those records pointing at a trashed row. The
        // school deactivates it instead, which stops it being picked up for new
        // vouchers while the history stays readable.
        if ($inUse['enrollments'] > 0 || $inUse['vouchers'] > 0) {
            $message = sprintf(
                'This fee structure is in use by %d student(s) and %d voucher(s), so it cannot be deleted. Deactivate it instead.',
                $inUse['enrollments'],
                $inUse['vouchers'],
            );

            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('fee.structures.index')->with('error', $message);
        }

        $feeStructure->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fee structure deleted successfully.',
            ]);
        }

        return redirect()->route('fee.structures.index')
            ->with('success', 'Fee structure deleted successfully.');
    }

    /**
     * How many records depend on a fee structure.
     *
     * Shown before an edit so the office knows the change reaches real
     * students, and checked before a delete.
     *
     * @return array{enrollments: int, vouchers: int}
     */
    private function usageOf(FeeStructure $feeStructure): array
    {
        return [
            'enrollments' => StudentEnrollmentRecord::where('fee_structure_id', $feeStructure->id)->count(),
            'vouchers' => FeeVoucher::whereHas(
                'enrollmentRecord',
                fn ($q) => $q->where('fee_structure_id', $feeStructure->id)
            )->count(),
        ];
    }

    /**
     * Brings a structure's charges in line with the set the caller sent.
     *
     * Matched on the fee head, which the request already forbids appearing
     * twice: a repriced charge keeps its row, so the voucher lines that name it
     * as their source still point at something real. Heads no longer in the set
     * are removed.
     *
     * Each charge is replaced by what was sent, so an omitted flag goes back to
     * its default. That is the opposite of the single-item endpoint, which
     * edits one row in place and keeps what the form left out — here the caller
     * is stating the whole set, and any other reading would make it impossible
     * to turn a flag off.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(FeeStructure $feeStructure, array $items): void
    {
        $keep = [];

        foreach ($items as $item) {
            $attributes = $this->itemAttributes($item);

            $existing = $feeStructure->items()
                ->where('fee_head_id', $attributes['fee_head_id'])
                ->first();

            $keep[] = $existing
                ? tap($existing)->update($attributes)->id
                : $feeStructure->items()->create($attributes)->id;
        }

        $feeStructure->items()->whereNotIn('id', $keep)->delete();
    }

    /**
     * The columns a fee structure item is created from.
     *
     * `frequency` comes from the form when given, so one structure can bill a
     * head monthly where another bills it yearly; it falls back to the fee
     * head's own default, which used to be the only possible value.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function itemAttributes(array $item): array
    {
        $feeHead = FeeHead::find($item['fee_head_id']);

        $default = $feeHead?->default_frequency instanceof FeeFrequency
            ? $feeHead->default_frequency->value
            : ($feeHead?->default_frequency ?? 'monthly');

        return [
            'fee_head_id' => $item['fee_head_id'],
            'amount' => $item['amount'],
            'frequency' => $item['frequency'] ?? $default,
            'is_optional' => $item['is_optional'] ?? false,
            'applicable_on_admission' => $item['applicable_on_admission'] ?? false,
            'is_transport_related' => $item['is_transport_related'] ?? false,
            'starts_from_month_id' => $item['starts_from_month_id'] ?? null,
            'ends_at_month_id' => $item['ends_at_month_id'] ?? null,
            'billing_month_id' => $item['billing_month_id'] ?? null,
            'billing_year' => $item['billing_year'] ?? null,
            'notes' => $item['notes'] ?? null,
        ];
    }

    /**
     * Activate the specified fee structure.
     */
    public function activate(Request $request, FeeStructure $feeStructure): JsonResponse
    {
        $feeStructure->update([
            'status' => FeeStructureStatus::ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee structure activated successfully.',
            'structure' => $this->transformStructure($feeStructure->fresh(['session', 'campus', 'class', 'section'])->loadCount('items')),
        ]);
    }

    /**
     * Deactivate the specified fee structure.
     */
    public function deactivate(Request $request, FeeStructure $feeStructure): JsonResponse
    {
        $feeStructure->update([
            'status' => FeeStructureStatus::INACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee structure deactivated successfully.',
            'structure' => $this->transformStructure($feeStructure->fresh(['session', 'campus', 'class', 'section'])->loadCount('items')),
        ]);
    }

    /**
     * Set the specified fee structure as default.
     */
    public function setDefault(Request $request, FeeStructure $feeStructure): JsonResponse
    {
        FeeStructure::where('session_id', $feeStructure->session_id)
            ->where('campus_id', $feeStructure->campus_id)
            ->where('class_id', $feeStructure->class_id)
            ->where('section_id', $feeStructure->section_id)
            ->update(['is_default' => false]);

        $feeStructure->update([
            'is_default' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Default fee structure updated successfully.',
            'structure' => $this->transformStructure($feeStructure->fresh(['session', 'campus', 'class', 'section'])->loadCount('items')),
        ]);
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

        if (! $sessionId || ! $campusId) {
            return response()->json([
                'success' => false,
                'message' => 'Session and Campus are required',
            ], 400);
        }

        // Convert empty strings to null for proper query handling
        $classId = $classId && $classId !== '' ? $classId : null;
        $sectionId = $sectionId && $sectionId !== '' ? $sectionId : null;

        $baseQuery = FeeStructure::active()
            ->with(['items.feeHead'])
            ->where('session_id', $sessionId)
            ->where('campus_id', $campusId)
            ->orderByDesc('is_default')
            ->orderByDesc('effective_from')
            ->orderByDesc('id');

        // For student admission/edit forms, match the selected session scope.
        // Do not block lookup only because the current date falls outside effective range.

        // Try section-specific first (class_id AND section_id both set)
        if ($classId && $sectionId) {
            $structure = (clone $baseQuery)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->first();

            if ($structure) {
                return $this->formatFeeStructureResponse($structure);
            }
        }

        // Try class-specific (class_id set, section_id is null)
        if ($classId) {
            $structure = (clone $baseQuery)
                ->where('class_id', $classId)
                ->whereNull('section_id')
                ->first();

            if ($structure) {
                return $this->formatFeeStructureResponse($structure);
            }
        }

        // Try campus-wide (both class_id and section_id are null)
        $structure = (clone $baseQuery)
            ->whereNull('class_id')
            ->whereNull('section_id')
            ->first();

        if (! $structure) {
            return response()->json([
                'success' => false,
                'message' => 'No active fee structure found for this class',
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
            ->when($query, fn ($q) => $q->where('title', 'like', "%{$query}%"))
            ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
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
                'source' => $structure->section_id ? 'section' : ($structure->class_id ? 'class' : 'campus'),
                'monthly_fee' => $monthlyTotal,
                'annual_fee' => $yearlyTotal,
                'one_time_fee' => $oneTimeTotal,
                'items' => $structure->items->map(function ($item) {
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
                }),
            ],
        ]);
    }

    /**
     * Get transformed fee structures for the listing.
     */
    private function getTransformedStructures(Request $request)
    {
        $query = FeeStructure::with(['session', 'campus', 'class', 'section'])
            ->withCount('items');

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
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        return $query->latest()->get()->map(fn (FeeStructure $structure) => $this->transformStructure($structure));
    }

    /**
     * Transform a fee structure for frontend consumption.
     */
    private function transformStructure(FeeStructure $structure): array
    {
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
            'is_default' => (bool) $structure->is_default,
        ];
    }
}
