<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryType;
use App\Models\Purchase;
use App\Models\StudentInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class InventoryItemsController extends Controller
{
    /**
     * Display inventory items listing page.
     *
     * IMPROVEMENT: Added campus filtering for multi-campus safety.
     * Global scope filters by is_active=true and excludes soft-deleted records.
     */
    public function index(Request $request): Response
    {
        $campusId = $request->get('campus_id');
        $status = $request->get('status');
        $perPage = $request->get('per_page', 25);

        $query = InventoryItem::with(['campus', 'inventoryType'])
            ->withCount('inventoryStock')
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->when($status === 'inactive', fn ($q) => $q->withoutGlobalScope('active')->where('is_active', false))
            ->orderBy('name');

        return inertia('inventory/Items/Index', [
            'inventoryItems' => $query->paginate($perPage),
            'campuses' => Campus::orderBy('name')->get(),
            'inventoryTypes' => InventoryType::when($campusId, fn ($q) => $q->where('campus_id', $campusId))
                ->orderBy('name')
                ->get(),
            'filters' => [
                'campus_id' => $campusId,
            ],
        ]);
    }

    /**
     * Show the form for creating a new inventory item.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('inventory.items.index');
    }

    /**
     * Store a newly created inventory item.
     *
     * IMPROVEMENTS:
     * - Added DB transaction for data integrity
     * - Optionally create InventoryStock atomically
     * - Added error handling for stock creation failure
     */
    public function store(StoreInventoryItemRequest $request)
    {
        // IMPROVEMENT: Use DB transaction for atomic operation
        try {
            DB::transaction(function () use ($request, &$inventoryItem) {
                $inventoryItem = InventoryItem::create($request->validated());

                // IMPROVEMENT: Optionally create initial stock record atomically
                // This supports future snapshot/stock assignment integration
                if ($request->boolean('create_initial_stock', false)) {
                    InventoryStock::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'quantity' => $request->input('initial_quantity', 0),
                        'reserved_quantity' => 0,
                        // available_quantity is a generated column, don't set it
                        'notes' => 'Initial stock on item creation',
                    ]);
                }
            });

            return redirect()->to('/inventory/settings')
                ->with('success', 'Inventory item created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create inventory item. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified inventory item.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function edit(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        // Scope to campus for multi-campus safety
        /** @var InventoryItem $inventoryItem */
        $item = InventoryItem::where('id', $inventoryItem->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return redirect()->to("/inventory?modal=inventory-items-form&action=edit&id={$item->id}");
    }

    /**
     * Update the specified inventory item.
     *
     * IMPROVEMENT: Added DB transaction and campus scope.
     */
    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem)
    {
        // IMPROVEMENT: Scope to campus for multi-campus safety
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::where('id', $inventoryItem->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        // IMPROVEMENT: Use DB transaction for atomic operation
        try {
            DB::transaction(function () use ($request, $inventoryItem) {
                $inventoryItem->update($request->validated());
            });

            return redirect()->to('/inventory/settings')
                ->with('success', 'Inventory item updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update inventory item. Please try again.');
        }
    }

    /**
     * Remove the specified inventory item (soft delete).
     *
     * IMPROVEMENT: Added campus scope for multi-campus safety.
     * Performs soft delete by default.
     */
    public function destroy(Request $request, InventoryItem $inventoryItem)
    {
        // IMPROVEMENT: Scope to campus for multi-campus safety
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::where('id', $inventoryItem->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        // Perform soft delete
        $inventoryItem->delete();

        return response()->json(['success' => true, 'message' => 'Inventory item deleted successfully.']);
    }

    /**
     * Inactivate the specified inventory item.
     *
     * IMPROVEMENT: Added campus scope for multi-campus safety.
     */
    public function inactivate(Request $request, InventoryItem $inventoryItem)
    {
        // IMPROVEMENT: Scope to campus for multi-campus safety
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::where('id', $inventoryItem->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $inventoryItem->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Inventory item inactivated successfully.']);
    }

    /**
     * Activate the specified inventory item.
     *
     * IMPROVEMENT: Added campus scope for multi-campus safety.
     */
    public function activate(Request $request, InventoryItem $inventoryItem)
    {
        // IMPROVEMENT: Scope to campus for multi-campus safety
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::where('id', $inventoryItem->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $inventoryItem->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => 'Inventory item activated successfully.']);
    }

    /**
     * Get all inventory items with optional search and filters.
     *
     * IMPROVEMENTS:
     * - Defaults to first campus if no campus_id provided
     * - Added pagination support for large datasets
     * - Optimized query with selective column selection
     * - Added stock quantity for quick access
     * - Added status parameter for inactive items
     * - Global scope ensures only active, non-deleted items are returned by default
     * - Added supplier_id filter for items previously purchased from a supplier
     */
    public function getAll(Request $request): JsonResponse
    {
        $query = trim($request->get('q', ''));
        $campusId = $request->get('campus_id');
        $typeId = $request->get('type_id');
        $status = $request->get('status', 'active'); // active or inactive
        $supplierId = $request->get('supplier_id'); // Filter by supplier (items purchased from this supplier)

        // Default to first campus if no campus_id provided
        if (! $campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        // Build the base query - global scope already filters is_active=true and excludes soft-deleted
        $itemsQuery = InventoryItem::with(['campus:id,name', 'inventoryType:id,name'])
            ->select(['id', 'name', 'description', 'campus_id', 'inventory_type_id', 'is_active'])
            ->when($query, function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($query).'%']);
            })
            ->when($campusId, function ($q) use ($campusId) {
                $q->where('campus_id', $campusId);
            })
            ->when($typeId, function ($q) use ($typeId) {
                $q->where('inventory_type_id', $typeId);
            })
            ->when($status === 'inactive', function ($q) {
                $q->withoutGlobalScope('active')->where('is_active', false);
            })
            // Filter by supplier - get items that have been purchased from this supplier
            ->when($supplierId, function ($q) use ($supplierId) {
                $q->whereHas('purchaseItems.purchase', function ($purchaseQuery) use ($supplierId) {
                    $purchaseQuery->where('supplier_id', $supplierId);
                });
            });

        $items = $itemsQuery
            ->withCount(['inventoryStock as stock_quantity' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(available_quantity), 0)'));
            }])
            ->orderBy('name')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'campus_id' => $item->campus_id,
                    'campus_name' => $item->campus?->name,
                    'inventory_type_id' => $item->inventory_type_id,
                    'inventory_type_name' => $item->inventoryType?->name,
                    'stock_quantity' => $item->stock_quantity,
                    'is_active' => $item->is_active,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get paginated inventory items for dashboard.
     *
     * Global scope filters by is_active=true and excludes soft-deleted records.
     */
    public function getPaginated(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $status = $request->get('status', 'active'); // active or inactive
        $search = $request->get('search', '');

        $query = InventoryItem::with(['campus:id,name', 'inventoryType:id,name'])
            ->withCount('inventoryStock')
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->when($search, fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->withoutGlobalScope('active')->where('is_active', false));

        $items = $query
            ->orderBy('name')
            ->paginate($request->get('per_page', 25))
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'campus_id' => $item->campus_id,
                    'campus_name' => $item->campus?->name ?? 'Unknown',
                    'inventory_type_id' => $item->inventory_type_id,
                    'inventory_type_name' => $item->inventoryType?->name ?? 'Unknown',
                    'inventory_stock_count' => $item->inventory_stock_count,
                    'is_active' => $item->is_active,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get inventory item details with stock information.
     *
     * SUGGESTED ADDITION: Helper method for detailed view (future snapshot integration).
     */
    public function getDetails(Request $request, $id): JsonResponse
    {
        $campusId = $request->get('campus_id');

        // IMPROVEMENT: Scope to campus for multi-campus safety
        $item = InventoryItem::with(['campus', 'inventoryType', 'inventoryStock'])
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->findOrFail($id);

        return response()->json([
            'item' => $item,
            'snapshot' => [
                // FUTURE: Integrate with snapshot logic
                'available_quantity' => $item->inventoryStock?->available_quantity ?? 0,
                'reserved_quantity' => $item->inventoryStock?->reserved_quantity ?? 0,
                'total_quantity' => $item->inventoryStock?->quantity ?? 0,
            ],
        ]);
    }

    /**
     * Check if inventory item name exists for campus and type.
     *
     * SUGGESTED ADDITION: Helper method for frontend validation.
     */
    public function checkNameExists(Request $request): JsonResponse
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'inventory_type_id' => 'required|exists:inventory_types,id',
            'name' => 'required|string|max:255',
            'exclude_id' => 'nullable|exists:inventory_items,id',
        ]);

        $exists = InventoryItem::where('campus_id', $request->campus_id)
            ->where('inventory_type_id', $request->inventory_type_id)
            ->where('name', $request->name)
            ->when($request->exclude_id, fn ($q) => $q->where('id', '!=', $request->exclude_id))
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Get low stock items for alert notifications.
     *
     * SUGGESTED ADDITION: Helper method for stock alerts (future dashboard integration).
     */
    public function getLowStockItems(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $threshold = $request->get('threshold', 10);

        $items = InventoryItem::with(['campus', 'inventoryType'])
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->where('is_active', true)
            ->whereHas('inventoryStock', function ($q) use ($threshold) {
                $q->having('available_quantity', '<', $threshold);
            })
            ->withCount(['inventoryStock as available_qty' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(available_quantity), 0)'));
            }])
            ->orderBy('available_qty')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'campus_name' => $item->campus?->name,
                    'inventory_type_name' => $item->inventoryType?->name,
                    'available_qty' => $item->available_qty,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get dashboard summary data.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        // Get stats - global scope ensures only active, non-deleted items
        $typesCount = InventoryType::when($campusId, fn ($q) => $q->where('campus_id', $campusId))->count();
        $itemsCount = InventoryItem::when($campusId, fn ($q) => $q->where('campus_id', $campusId))->count();

        $stocks = InventoryStock::when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->whereHas('inventoryItem', fn ($q) => $q->where('is_active', true))
            ->get();

        $totalStock = $stocks->sum('quantity');
        $availableStock = $stocks->sum('available_quantity');
        $lowStockStocks = $stocks->filter(fn ($s) => $s->isLowStock());
        $lowStockItems = $lowStockStocks->count();

        // Get actual low stock items with details
        $lowStockItemsList = $lowStockStocks->map(fn ($s) => [
            'id' => $s->id,
            'item_id' => $s->inventory_item_id,
            'item_name' => $s->inventoryItem?->name ?? 'Unknown',
            'available_quantity' => $s->available_quantity,
            'low_stock_threshold' => $s->low_stock_threshold ?? 10,
            'campus_name' => $s->campus?->name,
        ])->take(10);

        $purchasesCount = Purchase::when($campusId, fn ($q) => $q->where('campus_id', $campusId))->count();
        $totalPurchaseValue = Purchase::when($campusId, fn ($q) => $q->where('campus_id', $campusId))->sum('total_amount');

        $studentInventories = StudentInventory::when($campusId, fn ($q) => $q->where('campus_id', $campusId))->get();
        $assignedItems = $studentInventories->count();
        $pendingReturns = $studentInventories->whereIn('status', ['assigned', 'partial_return'])->count();

        // Get recent activities from actual data
        $recentPurchases = Purchase::with(['campus:id,name', 'supplier:id,name'])
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'type' => 'purchase',
                'description' => 'New purchase from '.($p->supplier?->name ?? 'N/A'),
                'date' => $p->purchase_date,
                'created_at' => $p->created_at,
            ]);

        $recentAssignments = StudentInventory::with(['campus:id,name', 'student'])
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($si) => [
                'id' => $si->id,
                'type' => 'assignment',
                'description' => 'Inventory assigned to '.($si->student?->user?->name ?? 'N/A'),
                'date' => $si->assigned_date,
                'created_at' => $si->created_at,
            ]);

        // Merge and sort by created_at
        $recentActivities = $recentPurchases->concat($recentAssignments)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        // Get recent purchases with items for "Top Moving Items" calculation
        $recentPurchasesWithItems = Purchase::with(['purchaseItems.inventoryItem:id,name'])
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->orderBy('purchase_date', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'stats' => [
                'types' => $typesCount,
                'items' => $itemsCount,
                'totalStock' => $totalStock,
                'availableStock' => $availableStock,
                'lowStockItems' => $lowStockItems,
                'purchases' => $purchasesCount,
                'totalPurchaseValue' => $totalPurchaseValue,
                'assignedItems' => $assignedItems,
                'pendingReturns' => $pendingReturns,
            ],
            'low_stock_items' => $lowStockItemsList,
            'recent_activities' => $recentActivities,
            'recent_purchases' => $recentPurchasesWithItems->map(fn ($p) => [
                'id' => $p->id,
                'purchase_id' => $p->purchase_id,
                'purchase_date' => $p->purchase_date,
                'supplier_name' => $p->supplier?->name,
                'total_amount' => $p->total_amount,
                'items' => $p->purchaseItems->map(fn ($item) => [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $item->inventoryItem?->name ?? $item->item_name_snapshot ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'purchase_rate' => $item->purchase_rate,
                ]),
            ]),
        ]);
    }
}
