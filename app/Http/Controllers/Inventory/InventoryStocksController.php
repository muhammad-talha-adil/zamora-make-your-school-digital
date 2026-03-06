<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryStocksController extends Controller
{
    /**
     * Display inventory stocks listing page.
     *
     * IMPROVEMENTS:
     * - Added pagination
     * - Added campus filtering
     * - Added low stock indicator
     */
    public function index(Request $request)
    {
        $campusId = $request->get('campus_id');
        $itemId = $request->get('item_id');
        $perPage = $request->get('per_page', 25);

        return inertia('inventory/Stocks/Index', [
            'inventoryStocks' => InventoryStock::with(['campus:id,name', 'inventoryItem:id,name', 'inventoryItem.inventoryType:id,name'])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->when($itemId, fn($q) => $q->where('inventory_item_id', $itemId))
                ->when($request->get('low_stock_only'), function ($q) {
                    $q->whereHas('inventoryItem', fn($sq) => $sq->where('is_active', true));
                })
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage),
            'campuses' => Campus::orderBy('name')->get(),
            'filters' => [
                'campus_id' => $campusId,
                'item_id' => $itemId,
                'low_stock_only' => $request->get('low_stock_only'),
            ],
        ]);
    }

    /**
     * Get all inventory stocks with optional filters.
     *
     * IMPROVEMENTS:
     * - campus_id defaults to first campus if not provided
     * - Added pagination
     * - Added snapshot fields
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $itemId = $request->get('item_id');
        $lowStockOnly = $request->get('low_stock_only', false);
        $perPage = $request->get('per_page', 25);

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $stocks = InventoryStock::with(['campus:id,name', 'inventoryItem:id,name'])
            ->select(['id', 'campus_id', 'inventory_item_id', 'quantity', 'reserved_quantity', 'available_quantity', 'low_stock_threshold', 'updated_at'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($itemId, fn($q) => $q->where('inventory_item_id', $itemId))
            ->when($lowStockOnly, function ($q) {
                $q->having('available_quantity', '<', \DB::raw('COALESCE(low_stock_threshold, 10)'));
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->through(function ($stock) {
                return [
                    'id' => $stock->id,
                    'campus_id' => $stock->campus_id,
                    'campus_name' => $stock->campus?->name,
                    'inventory_item_id' => $stock->inventory_item_id,
                    'item_name' => $stock->inventoryItem?->name,
                    'quantity' => $stock->quantity,
                    'reserved_quantity' => $stock->reserved_quantity ?? 0,
                    'available_quantity' => $stock->available_quantity,
                    'low_stock_threshold' => $stock->low_stock_threshold ?? 10,
                    'is_low_stock' => $stock->isLowStock(),
                    'stock_status' => $stock->getStockStatusLevel(),
                    'updated_at' => $stock->updated_at,
                ];
            });

        return response()->json($stocks);
    }

    /**
     * Get low stock items for alerts.
     *
     * IMPROVEMENT: Dedicated endpoint for low stock alerts.
     */
    public function getLowStockItems(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $threshold = (int) $request->get('threshold', 10);
        $typeId = $request->get('inventory_type_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $stocks = InventoryStock::with(['campus:id,name', 'inventoryItem:id,name', 'inventoryItem.inventoryType:id,name'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($typeId, fn($q) => $q->whereHas('inventoryItem', fn($iq) => $iq->where('inventory_type_id', $typeId)))
            ->whereHas('inventoryItem', fn($q) => $q->where('is_active', true))
            ->orderBy('available_quantity', 'asc')
            ->limit(100)
            ->get()
            ->map(function ($stock) use ($threshold) {
                $available = $stock->available_quantity ?? 0;
                $stockThreshold = $stock->low_stock_threshold ?? $threshold;

                return [
                    'id' => $stock->id,
                    'item_name' => $stock->inventoryItem?->name,
                    'item_id' => $stock->inventory_item_id,
                    'campus_name' => $stock->campus?->name,
                    'inventory_type' => $stock->inventoryItem?->inventoryType?->name,
                    'available_quantity' => $available,
                    'low_stock_threshold' => $stockThreshold,
                    'is_low_stock' => $available < $stockThreshold,
                    'is_out_of_stock' => $available <= 0,
                    'severity' => $available <= 0 ? 'critical' : ($available < $stockThreshold / 2 ? 'warning' : 'info'),
                    'stock_until_low' => max(0, $available - $stockThreshold),
                ];
            })
            ->filter(fn($s) => $s['is_low_stock'])
            ->values();

        return response()->json([
            'alerts' => $stocks,
            'threshold' => $threshold,
            'total_alerts' => $stocks->count(),
            'critical_count' => $stocks->where('severity', 'critical')->count(),
            'warning_count' => $stocks->where('severity', 'warning')->count(),
        ]);
    }

    /**
     * Check stock availability for an item.
     *
     * IMPROVEMENTS:
     * - Added item validation
     * - Added reserved_quantity consideration
     * - Added snapshot fields
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $itemId = $request->get('item_id');
        $requiredQuantity = (int) $request->get('quantity', 0);

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        if (!$itemId) {
            return response()->json([
                'available' => false,
                'message' => 'Item is required.',
            ], 422);
        }

        $item = InventoryItem::where('id', $itemId)
            ->where('campus_id', $campusId)
            ->first();

        if (!$item) {
            return response()->json([
                'available' => false,
                'message' => 'Inventory item not found or does not belong to the specified campus.',
            ], 422);
        }

        $stock = InventoryStock::where('campus_id', $campusId)
            ->where('inventory_item_id', $itemId)
            ->first();

        $availableQuantity = $stock?->available_quantity ?? 0;
        $reservedQuantity = $stock?->reserved_quantity ?? 0;
        $totalQuantity = $stock?->quantity ?? 0;
        $isAvailable = $availableQuantity >= $requiredQuantity;

        return response()->json([
            'available' => $isAvailable,
            'available_quantity' => $availableQuantity,
            'reserved_quantity' => $reservedQuantity,
            'total_quantity' => $totalQuantity,
            'required_quantity' => $requiredQuantity,
            'item_name' => $item->name,
            'item_id' => $itemId,
            'is_low_stock' => $stock?->isLowStock() ?? true,
            'low_stock_threshold' => $stock?->low_stock_threshold ?? 10,
            'message' => $isAvailable
                ? 'Stock is available.'
                : 'Insufficient stock. Required: ' . $requiredQuantity . ', Available: ' . $availableQuantity,
        ]);
    }

    /**
     * Update stock quantity manually.
     *
     * IMPROVEMENT: Added DB transaction.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $campusId = $request->get('campus_id');

        /** @var InventoryStock $stock */
        $stock = InventoryStock::where('id', $id)
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->firstOrFail();

        try {
            \DB::transaction(function () use ($stock, $request) {
                $stock->quantity = $request->quantity;
                $stock->reserved_quantity = min($stock->reserved_quantity ?? 0, $stock->quantity);
                // available_quantity is a generated column, don't set it
                $stock->low_stock_threshold = $request->low_stock_threshold ?? $stock->low_stock_threshold ?? 10;
                $stock->notes = $request->notes ?? $stock->notes;
                $stock->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully.',
                'stock' => [
                    'id' => $stock->id,
                    'quantity' => $stock->quantity,
                    'available_quantity' => $stock->available_quantity,
                    'low_stock_threshold' => $stock->low_stock_threshold,
                    'is_low_stock' => $stock->isLowStock(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reserve stock quantity.
     */
    public function reserve(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $campusId = $request->get('campus_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        /** @var InventoryStock $stock */
        $stock = InventoryStock::where('campus_id', $campusId)
            ->where('inventory_item_id', $request->item_id)
            ->firstOrFail();

        try {
            \DB::transaction(function () use ($stock, $request) {
                $stock->reserveStock($request->quantity);
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock reserved successfully.',
                'stock' => [
                    'id' => $stock->id,
                    'quantity' => $stock->quantity,
                    'reserved_quantity' => $stock->reserved_quantity,
                    'available_quantity' => $stock->available_quantity,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reserve stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Release reserved stock.
     */
    public function release(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $campusId = $request->get('campus_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        /** @var InventoryStock $stock */
        $stock = InventoryStock::where('campus_id', $campusId)
            ->where('inventory_item_id', $request->item_id)
            ->firstOrFail();

        try {
            \DB::transaction(function () use ($stock, $request) {
                $stock->releaseStock($request->quantity);
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock release successful.',
                'stock' => [
                    'id' => $stock->id,
                    'quantity' => $stock->quantity,
                    'reserved_quantity' => $stock->reserved_quantity,
                    'available_quantity' => $stock->available_quantity,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to release stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update low stock threshold for an item.
     */
    public function updateThreshold(Request $request, $id): JsonResponse
    {
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:0',
        ]);

        $campusId = $request->get('campus_id');

        /** @var InventoryStock $stock */
        $stock = InventoryStock::where('id', $id)
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->firstOrFail();

        $stock->low_stock_threshold = $request->low_stock_threshold;
        $stock->save();

        return response()->json([
            'success' => true,
            'message' => 'Low stock threshold updated successfully.',
            'stock' => [
                'id' => $stock->id,
                'item_name' => $stock->inventoryItem?->name,
                'low_stock_threshold' => $stock->low_stock_threshold,
                'is_low_stock' => $stock->isLowStock(),
            ],
        ]);
    }

    /**
     * Get dashboard summary for stocks.
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $stocks = InventoryStock::with(['inventoryItem:id,name'])
            ->where('campus_id', $campusId)
            ->whereHas('inventoryItem', fn($q) => $q->where('is_active', true))
            ->get();

        $totalItems = $stocks->count();
        $totalQuantity = $stocks->sum('quantity');
        $totalAvailable = $stocks->sum('available_quantity');
        $lowStockItems = $stocks->filter(fn($s) => $s->isLowStock())->count();
        $outOfStockItems = $stocks->filter(fn($s) => $s->isOutOfStock())->count();

        return response()->json([
            'totals' => [
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'total_available' => $totalAvailable,
            ],
            'alerts' => [
                'low_stock_count' => $lowStockItems,
                'out_of_stock_count' => $outOfStockItems,
                'healthy_count' => $totalItems - $lowStockItems - $outOfStockItems,
            ],
            'health' => [
                'percentage_healthy' => $totalItems > 0
                    ? round((($totalItems - $lowStockItems - $outOfStockItems) / $totalItems) * 100, 2)
                    : 100,
                'percentage_low_stock' => $totalItems > 0
                    ? round(($lowStockItems / $totalItems) * 100, 2)
                    : 0,
                'percentage_out_of_stock' => $totalItems > 0
                    ? round(($outOfStockItems / $totalItems) * 100, 2)
                    : 0,
            ],
        ]);
    }
}
