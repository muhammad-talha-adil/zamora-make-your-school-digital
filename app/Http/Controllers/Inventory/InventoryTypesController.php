<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryTypeRequest;
use App\Http\Requests\Inventory\UpdateInventoryTypeRequest;
use App\Models\Campus;
use App\Models\InventoryType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class InventoryTypesController extends Controller
{
    /**
     * Display inventory types listing page.
     *
     * IMPROVEMENT: Added campus filtering for multi-campus safety.
     * Global scope filters by is_active=true and excludes soft-deleted records.
     * Uses scopeForCampus to include types available for all campuses.
     */
    public function index(Request $request): Response
    {
        $campusId = $request->get('campus_id');
        $status = $request->get('status');
        $perPage = $request->get('per_page', 25);

        $query = InventoryType::with(['campus:id,name'])
            ->withCount('inventoryItems')
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->when($status === 'inactive', fn ($q) => $q->withoutGlobalScope('active')->where('is_active', false))
            ->orderBy('name');

        return inertia('inventory/InventoryTypes/Index', [
            'inventoryTypes' => $query->paginate($perPage),
            'campuses' => Campus::orderBy('name')->get(),
            'filters' => [
                'campus_id' => $campusId,
            ],
        ]);
    }

    /**
     * Show the form for creating a new inventory type.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('inventory.types.index');
    }

    /**
     * Store a newly created inventory type.
     *
     * IMPROVEMENT: Added error handling and support for 'all' campuses.
     */
    public function store(StoreInventoryTypeRequest $request)
    {
        try {
            $data = $request->validated();

            // Convert 'all' to null for "all campuses" functionality
            if (isset($data['campus_id']) && $data['campus_id'] === 'all') {
                $data['campus_id'] = null;
            }

            InventoryType::create($data);

            return redirect()->to('/inventory/settings')
                ->with('success', 'Inventory type created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create inventory type. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified inventory type.
     */
    public function edit(Request $request, InventoryType $inventoryType): RedirectResponse
    {
        /** @var InventoryType $inventoryType */
        $type = InventoryType::where('id', $inventoryType->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return redirect()->to("/inventory/types/{$type->id}/edit?campus_id={$type->campus_id}");
    }

    /**
     * Update the specified inventory type.
     *
     * IMPROVEMENT: Added campus scope, error handling, and support for 'all' campuses.
     */
    public function update(UpdateInventoryTypeRequest $request, InventoryType $inventoryType)
    {
        /** @var InventoryType $inventoryType */
        $inventoryType = InventoryType::where('id', $inventoryType->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        try {
            $data = $request->validated();

            // Convert 'all' to null for "all campuses" functionality
            if (isset($data['campus_id']) && $data['campus_id'] === 'all') {
                $data['campus_id'] = null;
            }

            $inventoryType->update($data);

            return redirect()->to('/inventory/settings')
                ->with('success', 'Inventory type updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update inventory type. Please try again.');
        }
    }

    /**
     * Remove the specified inventory type (soft delete).
     *
     * IMPROVEMENT: Added campus scope for multi-campus safety.
     * Performs soft delete by default.
     */
    public function destroy(Request $request, InventoryType $inventoryType)
    {
        /** @var InventoryType $inventoryType */
        $inventoryType = InventoryType::where('id', $inventoryType->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        // Perform soft delete
        $inventoryType->delete();

        return response()->json(['success' => true, 'message' => 'Inventory type deleted successfully.']);
    }

    /**
     * Inactivate the specified inventory type.
     *
     * IMPROVEMENT: Added campus scope for multi-campus safety.
     */
    public function inactivate(Request $request, InventoryType $inventoryType)
    {
        /** @var InventoryType $inventoryType */
        $inventoryType = InventoryType::where('id', $inventoryType->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $inventoryType->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Inventory type inactivated successfully.']);
    }

    /**
     * Activate the specified inventory type.
     *
     * IMPROVEMENT: Added campus scope for multi-campus safety.
     */
    public function activate(Request $request, InventoryType $inventoryType)
    {
        /** @var InventoryType $inventoryType */
        $inventoryType = InventoryType::where('id', $inventoryType->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $inventoryType->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => 'Inventory type activated successfully.']);
    }

    /**
     * Get all inventory types with optional search and filters.
     *
     * IMPROVEMENTS:
     * - Added pagination support for large datasets
     * - Optimized query with selective column selection
     * - Added items_count for quick access
     * - Added status parameter for inactive items
     * - Global scope ensures only active, non-deleted items are returned by default
     * - Uses scopeForCampus to include types available for all campuses
     */
    public function getAll(Request $request): JsonResponse
    {
        $query = trim($request->get('q', ''));
        $campusId = $request->get('campus_id');
        $status = $request->get('status', 'active'); // active or inactive

        $typesQuery = InventoryType::with(['campus:id,name'])
            ->select(['id', 'name', 'campus_id', 'is_active'])
            ->when($query, function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($query).'%']);
            })
            ->when($campusId, function ($q) use ($campusId) {
                $q->forCampus($campusId);
            })
            ->when($status === 'inactive', function ($q) {
                $q->withoutGlobalScope('active')->where('is_active', false);
            });

        $types = $typesQuery
            ->withCount('inventoryItems')
            ->orderBy('name')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'campus_id' => $type->campus_id,
                    'campus_name' => $type->campus_name, // Uses accessor for "All Campuses" display
                    'items_count' => $type->items_count,
                    'is_active' => $type->is_active,
                ];
            });

        return response()->json($types);
    }

    /**
     * Get paginated inventory types for dashboard.
     *
     * Global scope filters by is_active=true and excludes soft-deleted records.
     */
    public function getPaginated(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $status = $request->get('status', 'active'); // active or inactive
        $search = $request->get('search', '');

        $query = InventoryType::with(['campus:id,name'])
            ->withCount('inventoryItems')
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->when($search, fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->withoutGlobalScope('active')->where('is_active', false));

        $types = $query
            ->orderBy('name')
            ->paginate($request->get('per_page', 25))
            ->through(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'campus_id' => $type->campus_id,
                    'campus_name' => $type->campus_id === null ? 'All Campuses' : ($type->campus?->name ?? 'Unknown'),
                    'items_count' => $type->items_count,
                    'is_active' => $type->is_active,
                    'created_at' => $type->created_at,
                    'updated_at' => $type->updated_at,
                ];
            });

        return response()->json($types);
    }

    /**
     * Check if inventory type name exists for campus.
     *
     * SUGGESTED ADDITION: Helper method for frontend validation.
     * Supports 'null' for checking all campuses.
     * Also checks if there's an "All Campuses" type that would conflict.
     */
    public function checkNameExists(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'exclude_id' => 'nullable|exists:inventory_types,id',
        ]);

        // campus_id can be null (all campuses), numeric, or 'all'
        $campusId = $request->get('campus_id');
        $isAllCampus = ($campusId === null || $campusId === 'all');
        $name = $request->name;
        $excludeId = $request->exclude_id;

        if ($isAllCampus) {
            // Check if name exists in ANY campus (excluding current type)
            $exists = InventoryType::whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            return response()->json([
                'exists' => $exists,
                'is_all_campus_conflict' => false,
            ]);
        } else {
            // Check if name exists in the specific campus
            if (! is_numeric($campusId) || ! Campus::where('id', $campusId)->exists()) {
                return response()->json(['exists' => false, 'message' => 'Invalid campus']);
            }

            // Check if name exists in the specific campus
            $existsInCampus = InventoryType::where('campus_id', $campusId)
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            // Also check if there's an "All Campuses" type with the same name
            $existsInAllCampuses = InventoryType::whereNull('campus_id')
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            return response()->json([
                'exists' => $existsInCampus || $existsInAllCampuses,
                'is_all_campus_conflict' => $existsInAllCampuses,
            ]);
        }
    }

    /**
     * Get inventory type with items.
     *
     * SUGGESTED ADDITION: Helper method for detailed view.
     * Uses scopeForCampus to include types available for all campuses.
     */
    public function getWithItems(Request $request, $id): JsonResponse
    {
        $campusId = $request->get('campus_id');

        $type = InventoryType::with(['campus', 'inventoryItems' => function ($q) {
            $q->select(['id', 'name', 'inventory_type_id', 'campus_id'])
                ->orderBy('name');
        }])
            ->when($campusId, fn ($q) => $q->forCampus($campusId))
            ->findOrFail($id);

        return response()->json([
            'type' => $type,
            'items' => $type->inventoryItems->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
            ]),
        ]);
    }
}
