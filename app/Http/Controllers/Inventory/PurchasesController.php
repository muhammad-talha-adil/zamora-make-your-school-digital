<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StorePurchaseRequest;
use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PurchasesController extends Controller
{
    /**
     * Display inventory purchases listing page.
     *
     * IMPROVEMENTS:
     * - Added pagination for large datasets
     * - Added campus_id filtering for multi-campus safety
     * - Loads inventory items for the form dropdown
     */
    public function index(Request $request): Response
    {
        $campusId = $request->get('campus_id');

        // Default to first campus if none selected
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        return inertia('inventory/Purchases/Index', [
            'purchases' => Purchase::with(['campus:id,name', 'supplier:id,name', 'purchaseItems.inventoryItem:id,name'])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->orderBy('purchase_date', 'desc')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
            'suppliers' => Supplier::when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'campus_id' => $campusId,
            ],
        ]);
    }

    /**
     * Get all purchases with optional filters.
     *
     * IMPROVEMENTS:
     * - campus_id defaults to first campus if not provided
     * - Added pagination support for large datasets
     * - Added snapshot fields for audit trail
     * - Added search by supplier name OR item name
     * - Added date range filtering
     * - Added per_page parameter (25, 50, 75, 100)
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $query = trim($request->get('q', ''));
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);

        // Validate per_page
        $perPage = in_array($perPage, [25, 50, 75, 100]) ? $perPage : 25;

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $purchasesQuery = Purchase::with(['campus:id,name', 'supplier:id,name', 'purchaseItems.inventoryItem:id,name'])
            ->select(['id', 'purchase_id', 'supplier_id', 'purchase_date', 'total_amount', 'campus_id', 'created_at'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($query, function ($q) use ($query) {
                // Search in supplier name OR item name
                $q->where(function ($sq) use ($query) {
                    $sq->whereHas('supplier', fn($ssq) => $ssq->where('name', 'LIKE', "%{$query}%"))
                        ->orWhereHas('purchaseItems.inventoryItem', fn($iq) => $iq->where('name', 'LIKE', "%{$query}%"));
                });
            })
            ->when($request->get('from_date'), function ($q) use ($request) {
                $q->whereDate('purchase_date', '>=', $request->get('from_date'));
            })
            ->when($request->get('to_date'), function ($q) use ($request) {
                $q->whereDate('purchase_date', '<=', $request->get('to_date'));
            })
            ->orderBy('purchase_date', 'desc');

        // Use pagination
        $paginator = $purchasesQuery->paginate($perPage, ['*'], 'page', $page);

        $purchases = collect($paginator->items())
            ->map(function ($purchase) {
                // Get item names from purchase items
                $itemNames = $purchase->purchaseItems
                    ->pluck('inventoryItem.name')
                    ->filter()
                    ->take(3)
                    ->implode(', ');
                
                $moreItemsCount = $purchase->purchaseItems->count() - 3;
                if ($moreItemsCount > 0) {
                    $itemNames .= " (+{$moreItemsCount} more)";
                }

                return [
                    'id' => $purchase->id,
                    'purchase_id' => $purchase->purchase_id,
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_date' => $purchase->purchase_date,
                    'total_amount' => $purchase->total_amount,
                    'campus_id' => $purchase->campus_id,
                    'campus_name' => $purchase->campus?->name,
                    'supplier' => $purchase->supplier ? [
                        'id' => $purchase->supplier->id,
                        'name' => $purchase->supplier->name,
                    ] : null,
                    'items_count' => $purchase->purchaseItems->count(),
                    'item_names' => $itemNames ?: '-',
                    'created_at' => $purchase->created_at,
                ];
            });

        return response()->json([
            'data' => $purchases,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new purchase.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('inventory.purchases.index');
    }

    /**
     * Store a newly created purchase with items.
     *
     * CRITICAL IMPROVEMENTS:
     * - Added DB transaction for atomicity (creates purchase + items + stock update)
     * - Added proper error handling with rollback
     * - Added validation that inventory items belong to campus and are active
     * - Added idempotency key to prevent duplicate submissions
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        // Check for idempotency key to prevent duplicate submissions
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $existingPurchase = Purchase::where('idempotency_key', $idempotencyKey)->first();
            if ($existingPurchase) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Purchase already created.',
                    'purchase_id' => $existingPurchase->id,
                ]);
            }
        }

        // IMPROVEMENT: Use DB transaction for atomic operation
        try {
            \DB::transaction(function () use ($validated, &$purchase, $idempotencyKey) {
                // Create purchase first with idempotency key if available
                $purchaseData = [
                    'campus_id' => $validated['campus_id'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_date' => $validated['purchase_date'],
                    'total_amount' => 0, // Will update after items
                    'note' => $validated['note'] ?? null,
                ];
                
                // Add idempotency key if provided
                if ($idempotencyKey) {
                    $purchaseData['idempotency_key'] = $idempotencyKey;
                }
                
                $purchase = Purchase::create($purchaseData);

                $totalAmount = 0;

                foreach ($validated['purchase_items'] as $item) {
                    $quantity = (int) $item['quantity'];
                    $rate = (float) $item['purchase_rate'];
                    $saleRate = (float) ($item['sale_rate'] ?? 0);
                    $total = $quantity * $rate;
                    $totalAmount += $total;

                    // Create purchase item with snapshot fields for audit
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'inventory_item_id' => $item['inventory_item_id'],
                        'quantity' => $quantity,
                        'purchase_rate' => $rate, // Snapshot: price at time of purchase
                        'sale_rate' => $saleRate, // Snapshot: sale price at time of purchase
                        'total' => $total,
                        'item_snapshot' => $this->getItemSnapshot($item['inventory_item_id'], $rate, $saleRate),
                    ]);

                    // Update or create stock atomically
                    $stock = InventoryStock::firstOrCreate(
                        [
                            'campus_id' => $validated['campus_id'],
                            'inventory_item_id' => $item['inventory_item_id'],
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            // available_quantity is a generated column, don't include it in create
                        ]
                    );

                    $stock->quantity = ($stock->quantity ?? 0) + $quantity;
                    $stock->save();
                }

                // Update purchase total
                $purchase->total_amount = $totalAmount;
                $purchase->save();
            });

            return response()->json(['success' => true, 'message' => 'Purchase created successfully. Stock has been updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get item snapshot for audit purposes.
     */
    protected function getItemSnapshot(int $itemId, float $purchaseRate = 0, float $saleRate = 0): array
    {
        $item = InventoryItem::find($itemId);
        if (!$item) {
            return [];
        }

        return [
            'item_name' => $item->name,
            'item_description' => $item->description,
            'purchase_rate' => $purchaseRate,
            'sale_rate' => $saleRate,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Display the specified purchase with items.
     */
    public function show(Request $request, Purchase $purchase): Response
    {
        /** @var Purchase $purchase */
        $purchase = Purchase::where('id', $purchase->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return inertia('inventory/Purchases/Show', [
            'purchase' => $purchase->load(['campus', 'purchaseItems.inventoryItem']),
        ]);
    }

    /**
     * Show the form for editing the specified purchase.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function edit(Request $request, Purchase $purchase): \Illuminate\Http\RedirectResponse
    {
        /** @var Purchase $purchase */
        $purchase = Purchase::where('id', $purchase->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return redirect()->to("/inventory?modal=inventory-purchases-form&action=edit&id={$purchase->id}");
    }

    /**
     * Update the specified purchase.
     *
     * IMPROVEMENTS:
     * - Added support for updating purchase items
     * - Added stock adjustment when items are modified
     * - Returns JSON response for AJAX requests
     */
    public function update(Request $request, Purchase $purchase)
    {
        /** @var Purchase $purchase */
        $purchase = Purchase::where('id', $purchase->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'purchase_items' => 'required|array|min:1',
            'purchase_items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'purchase_items.*.quantity' => 'required|integer|min:1',
            'purchase_items.*.purchase_rate' => 'required|numeric|min:0',
            'purchase_items.*.sale_rate' => 'nullable|numeric|min:0',
        ]);

        try {
            \DB::transaction(function () use ($request, $purchase) {
                // First, reverse old stock for existing purchase items
                foreach ($purchase->purchaseItems as $oldItem) {
                    $stock = InventoryStock::where('campus_id', $purchase->campus_id)
                        ->where('inventory_item_id', $oldItem->inventory_item_id)
                        ->first();

                    if ($stock) {
                        $stock->quantity = max(0, $stock->quantity - $oldItem->quantity);
                        $stock->save();
                    }
                }

                // Delete old purchase items
                $purchase->purchaseItems()->delete();

                // Create new purchase items and add stock
                $totalAmount = 0;

                foreach ($request->purchase_items as $item) {
                    $quantity = (int) $item['quantity'];
                    $rate = (float) $item['purchase_rate'];
                    $saleRate = (float) ($item['sale_rate'] ?? 0);
                    $total = $quantity * $rate;
                    $totalAmount += $total;

                    // Create new purchase item
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'inventory_item_id' => $item['inventory_item_id'],
                        'quantity' => $quantity,
                        'purchase_rate' => $rate,
                        'sale_rate' => $saleRate,
                        'total' => $total,
                        'item_snapshot' => $this->getItemSnapshot($item['inventory_item_id'], $rate, $saleRate),
                    ]);

                    // Add new stock
                    $stock = InventoryStock::firstOrCreate(
                        [
                            'campus_id' => $purchase->campus_id,
                            'inventory_item_id' => $item['inventory_item_id'],
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                        ]
                    );

                    $stock->quantity = ($stock->quantity ?? 0) + $quantity;
                    $stock->save();
                }

                // Update purchase header
                $purchase->update([
                    'supplier_id' => $request->supplier_id,
                    'purchase_date' => $request->purchase_date,
                    'note' => $request->note ?? null,
                    'total_amount' => $totalAmount,
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Purchase updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified purchase.
     */
    public function destroy(Request $request, Purchase $purchase)
    {
        /** @var Purchase $purchase */
        $purchase = Purchase::where('id', $purchase->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        if ($purchase->purchaseItems()->exists()) {
            return back()->with('warning', 'Cannot delete purchase with associated items. Consider cancelling or contacting administrator.');
        }

        $purchase->delete();

        return back()->with('success', 'Purchase deleted successfully.');
    }

    /**
     * Cancel a purchase (soft delete with reason).
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:inventory_purchases,id',
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $campusId = $request->get('campus_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $purchase = Purchase::where('id', $request->purchase_id)
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->firstOrFail();

        try {
            \DB::transaction(function () use ($purchase, $request) {
                // Add cancellation note
                $existingNote = $purchase->note ?? '';
                $purchase->note = $existingNote . "\n\n[CANCELLED] " . now()->toIso8601String() . ": " . $request->cancellation_reason;
                $purchase->save();

                // Reverse stock
                if ($request->boolean('reverse_stock', false)) {
                    foreach ($purchase->purchaseItems as $item) {
                        $stock = InventoryStock::where('campus_id', $purchase->campus_id)
                            ->where('inventory_item_id', $item->inventory_item_id)
                            ->first();

                        if ($stock) {
                            $stock->quantity = max(0, $stock->quantity - $item->quantity);
                            $stock->save();
                        }
                    }
                }

                $purchase->delete();
            });

            return response()->json(['success' => true, 'message' => 'Purchase cancelled successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel purchase: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get purchase details for editing.
     */
    public function getPurchase(Request $request, $id): JsonResponse
    {
        $campusId = $request->get('campus_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $purchase = Purchase::with(['campus:id,name', 'supplier:id,name', 'purchaseItems.inventoryItem:id,name'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->findOrFail($id);

        $items = $purchase->purchaseItems->map(function ($item) {
            return [
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $item->quantity,
                'purchase_rate' => $item->purchase_rate,
                'sale_rate' => $item->sale_rate,
                'inventory_item' => $item->inventoryItem ? [
                    'id' => $item->inventoryItem->id,
                    'name' => $item->inventoryItem->name,
                ] : null,
            ];
        });

        return response()->json([
            'id' => $purchase->id,
            'purchase_id' => $purchase->purchase_id,
            'campus_id' => $purchase->campus_id,
            'campus_name' => $purchase->campus?->name,
            'supplier_id' => $purchase->supplier_id,
            'supplier' => $purchase->supplier ? [
                'id' => $purchase->supplier->id,
                'name' => $purchase->supplier->name,
            ] : null,
            'purchase_date' => $purchase->purchase_date,
            'note' => $purchase->note,
            'total_amount' => $purchase->total_amount,
            'created_at' => $purchase->created_at,
            'items' => $items,
        ]);
    }

    /**
     * Get purchase details with stock impact analysis.
     */
    public function getPurchaseAnalysis(Request $request, $id): JsonResponse
    {
        $campusId = $request->get('campus_id');

        // Default to first campus if no campus_id provided
        if (!$campusId) {
            $firstCampus = Campus::first();
            if ($firstCampus) {
                $campusId = $firstCampus->id;
            }
        }

        $purchase = Purchase::with(['campus', 'supplier', 'purchaseItems.inventoryItem'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->findOrFail($id);

        $analysis = $purchase->purchaseItems->map(function ($item) {
            $stock = InventoryStock::where('inventory_item_id', $item->inventory_item_id)
                ->where('campus_id', $item->purchase->campus_id)
                ->first();

            return [
                'item_id' => $item->inventory_item_id,
                'item_name' => $item->inventoryItem?->name,
                'quantity_purchased' => $item->quantity,
                'purchase_rate' => $item->purchase_rate,
                'stock_before' => $stock ? ($stock->quantity - $item->quantity) : 0,
                'stock_after' => $stock?->quantity,
                'stock_available' => $stock?->available_quantity,
            ];
        });

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'supplier' => $purchase->supplier ? [
                    'name' => $purchase->supplier->name,
                ] : null,
                'purchase_date' => $purchase->purchase_date,
                'total_amount' => $purchase->total_amount,
            ],
            'analysis' => $analysis,
        ]);
    }
}
