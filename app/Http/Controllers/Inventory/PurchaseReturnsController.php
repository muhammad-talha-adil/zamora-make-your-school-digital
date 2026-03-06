<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Reason;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PurchaseReturnsController extends Controller
{
    /**
     * Display purchase returns listing.
     */
    public function index(Request $request): Response
    {
        $campusId = $request->get('campus_id');
        $supplierId = $request->get('supplier_id');

        return inertia('inventory/PurchaseReturns/Index', [
            'returns' => PurchaseReturn::with(['campus:id,name', 'supplier:id,name', 'user:id,name'])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
                ->when($request->get('from_date'), fn($q) => $q->whereDate('return_date', '>=', $request->get('from_date')))
                ->when($request->get('to_date'), fn($q) => $q->whereDate('return_date', '<=', $request->get('to_date')))
                ->orderBy('return_date', 'desc')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
            'suppliers' => Supplier::when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'filters' => [
                'campus_id' => $campusId,
                'supplier_id' => $supplierId,
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
            ],
        ]);
    }

    /**
     * Get all returns with filters.
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $perPage = $request->get('per_page', 25);
        $page = $request->get('page', 1);

        // Validate per_page
        $perPage = in_array($perPage, [25, 50, 75, 100]) ? $perPage : 25;

        $returnsQuery = PurchaseReturn::with(['campus:id,name', 'supplier:id,name', 'items.inventoryItem:id,name'])
            ->select(['id', 'purchase_return_id', 'supplier_id', 'return_date', 'total_amount', 'campus_id', 'created_at'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($request->get('supplier_id'), fn($q) => $q->where('supplier_id', $request->get('supplier_id')))
            ->when($request->get('q'), fn($q) => $q->where(function($query) use ($request) {
                $query->where('purchase_return_id', 'like', '%' . $request->get('q') . '%')
                    ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', '%' . $request->get('q') . '%'));
            }))
            ->when($request->get('from_date'), fn($q) => $q->whereDate('return_date', '>=', $request->get('from_date')))
            ->when($request->get('to_date'), fn($q) => $q->whereDate('return_date', '<=', $request->get('to_date')))
            ->orderBy('return_date', 'desc');

        // Use pagination
        $paginator = $returnsQuery->paginate($perPage, ['*'], 'page', $page);

        $returns = collect($paginator->items())
            ->map(function ($return) {
                // Get item names from return items
                $itemNames = $return->items
                    ->pluck('inventoryItem.name')
                    ->filter()
                    ->take(3)
                    ->implode(', ');
                
                $moreItemsCount = $return->items->count() - 3;
                if ($moreItemsCount > 0) {
                    $itemNames .= " (+{$moreItemsCount} more)";
                }

                // Calculate total from items if total_amount is 0 or null
                $totalAmount = $return->total_amount;
                if (!$totalAmount || $totalAmount == 0) {
                    $totalAmount = $return->items->sum('total');
                }

                return [
                    'id' => $return->id,
                    'purchase_return_id' => $return->purchase_return_id,
                    'return_number' => $return->return_number,
                    'supplier_id' => $return->supplier_id,
                    'return_date' => $return->return_date,
                    'total_amount' => $totalAmount,
                    'campus_id' => $return->campus_id,
                    'campus_name' => $return->campus?->name,
                    'supplier' => $return->supplier ? [
                        'id' => $return->supplier->id,
                        'name' => $return->supplier->name,
                    ] : null,
                    'items_count' => $return->items->count(),
                    'item_names' => $itemNames ?: '-',
                    'created_at' => $return->created_at,
                ];
            });

        return response()->json([
            'data' => $returns,
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
     * Get all reasons (active only).
     */
    public function getReasons(Request $request): JsonResponse
    {
        $reasons = Reason::active()
            ->orderBy('name')
            ->get();
        
        return response()->json($reasons);
    }

    /**
     * Get suppliers filtered by campus.
     */
    public function getSuppliers(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        
        $suppliers = Supplier::query()
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId)->orWhereNull('campus_id'))
            ->when(!$campusId, fn($q) => $q->whereNull('campus_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'campus_id']);
        
        return response()->json($suppliers);
    }

    /**
     * Get purchases filtered by campus and supplier.
     */
    public function getPurchases(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $supplierId = $request->get('supplier_id');
        
        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'purchaseItems:id,inventory_item_id,quantity,purchase_rate'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->orderBy('purchase_date', 'desc')
            ->limit(50)
            ->get(['id', 'campus_id', 'supplier_id', 'purchase_date', 'total_amount']);
        
        // Transform purchases to include formatted data
        $transformed = $purchases->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'campus_id' => $purchase->campus_id,
                'supplier_id' => $purchase->supplier_id,
                'supplier' => $purchase->supplier,
                'purchase_date' => $purchase->purchase_date,
                'total_amount' => $purchase->total_amount,
                'display_text' => 'PUR-' . str_pad($purchase->id, 4, '0', STR_PAD_LEFT) . ' | ' . 
                    ($purchase->supplier?->name ?? 'N/A') . ' | ' . 
                    (new \DateTime($purchase->purchase_date))->format('d M Y'),
            ];
        });
        
        return response()->json($transformed);
    }

    /**
     * Show form for creating a new purchase return.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('inventory.purchase-returns.index');
    }

    /**
     * Get purchase items for return selection.
     */
    public function getPurchaseItems(Request $request, Purchase $purchase): JsonResponse
    {
        $purchase = Purchase::where('id', $purchase->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->with(['purchaseItems.inventoryItem:id,name', 'supplier:id,name'])
            ->firstOrFail();

        $items = $purchase->purchaseItems->map(function ($item) use ($purchase) {
            // Get current stock for this item at this campus
            $stock = InventoryStock::where('inventory_item_id', $item->inventory_item_id)
                ->where('campus_id', $purchase->campus_id)
                ->first();

            $quantityPurchased = $item->quantity;
            $currentStock = $stock?->quantity ?? 0;
            
            // Calculate available for return: can't return more than purchased
            // and can't return more than current stock
            $availableForReturn = min($quantityPurchased, $currentStock);

            return [
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'item_name' => $item->inventoryItem?->name ?? 'Unknown Item',
                'quantity_purchased' => $quantityPurchased,
                'current_stock' => $currentStock,
                'purchase_rate' => $item->purchase_rate,
                'available_for_return' => max(0, $availableForReturn),
            ];
        });

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'supplier_name' => $purchase->supplier?->name,
                'supplier_id' => $purchase->supplier_id,
                'purchase_date' => $purchase->purchase_date,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Store a new purchase return.
     */
    public function store(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:inventory_purchases,id',
            'return_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason_id' => 'nullable|exists:reasons,id',
            'items.*.custom_reason' => 'nullable|string|max:500',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            \DB::transaction(function () use ($request) {
                $campus = Campus::findOrFail($request->campus_id);
                
                // Create purchase return
                $return = PurchaseReturn::create([
                    'campus_id' => $request->campus_id,
                    'supplier_id' => $request->supplier_id,
                    'purchase_id' => $request->purchase_id,
                    'user_id' => auth()->id(),
                    'return_number' => PurchaseReturn::generateReturnNumber($campus),
                    'return_date' => $request->return_date,
                    'total_amount' => 0,
                    'note' => $request->note,
                ]);

                $totalAmount = 0;

                foreach ($request->items as $item) {
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $total = $quantity * $unitPrice;
                    $totalAmount += $total;

                    $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);

                    // Handle reason - either use existing reason_id or create new one from custom_reason
                    $reasonId = null;
                    $reasonText = null;
                    
                    if (!empty($item['reason_id'])) {
                        $reasonId = $item['reason_id'];
                    } elseif (!empty($item['custom_reason'])) {
                        // Create or find the custom reason
                        $reason = Reason::findOrCreate($item['custom_reason']);
                        $reasonId = $reason->id;
                        $reasonText = $item['custom_reason'];
                    } elseif (!empty($item['reason'])) {
                        // Legacy support for text-only reason
                        $reasonText = $item['reason'];
                    }

                    // Create return item
                    PurchaseReturnItem::create([
                        'purchase_return_id' => $return->id,
                        'inventory_item_id' => $item['inventory_item_id'],
                        'purchase_item_id' => $item['purchase_item_id'] ?? null,
                        'reason_id' => $reasonId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $total,
                        'item_snapshot' => [
                            'item_name' => $inventoryItem->name,
                            'description' => $inventoryItem->description,
                            'purchase_rate' => $inventoryItem->purchase_rate,
                            'captured_at' => now()->toIso8601String(),
                        ],
                        'reason' => $reasonText,
                    ]);

                    // Update stock (reverse the purchase)
                    $stock = InventoryStock::firstOrCreate(
                        [
                            'campus_id' => $request->campus_id,
                            'inventory_item_id' => $item['inventory_item_id'],
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            // available_quantity is a generated column, don't include it
                        ]
                    );

                    $stock->quantity = max(0, $stock->quantity - $quantity);
                    $stock->save();
                }

                // Update total
                $return->total_amount = $return->recalculateTotal();
                $return->save();
            });

            return response()->json(['success' => true, 'message' => 'Purchase return created successfully. Stock has been updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create purchase return: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a purchase return.
     */
    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn = PurchaseReturn::where('id', $purchaseReturn->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:inventory_purchases,id',
            'return_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            \DB::transaction(function () use ($request, $purchaseReturn) {
                // First, reverse old stock for existing return items
                foreach ($purchaseReturn->items as $oldItem) {
                    $stock = InventoryStock::where('campus_id', $purchaseReturn->campus_id)
                        ->where('inventory_item_id', $oldItem->inventory_item_id)
                        ->first();

                    if ($stock) {
                        // Add back the quantity that was subtracted
                        $stock->quantity = $stock->quantity + $oldItem->quantity;
                        $stock->save();
                    }
                }

                // Delete old return items
                $purchaseReturn->items()->delete();

                // Create new return items and update stock
                $totalAmount = 0;

                foreach ($request->items as $item) {
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $total = $quantity * $unitPrice;
                    $totalAmount += $total;

                    $inventoryItem = InventoryItem::findOrFail($item['inventory_item_id']);

                    // Handle reason
                    $reasonId = null;
                    $reasonText = null;
                    
                    if (!empty($item['reason_id'])) {
                        $reasonId = $item['reason_id'];
                    } elseif (!empty($item['custom_reason'])) {
                        $reason = Reason::findOrCreate($item['custom_reason']);
                        $reasonId = $reason->id;
                        $reasonText = $item['custom_reason'];
                    } elseif (!empty($item['reason'])) {
                        $reasonText = $item['reason'];
                    }

                    // Create new return item
                    PurchaseReturnItem::create([
                        'purchase_return_id' => $purchaseReturn->id,
                        'inventory_item_id' => $item['inventory_item_id'],
                        'purchase_item_id' => $item['purchase_item_id'] ?? null,
                        'reason_id' => $reasonId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $total,
                        'item_snapshot' => [
                            'item_name' => $inventoryItem->name,
                            'description' => $inventoryItem->description,
                            'purchase_rate' => $inventoryItem->purchase_rate,
                            'captured_at' => now()->toIso8601String(),
                        ],
                        'reason' => $reasonText,
                    ]);

                    // Subtract new stock
                    $stock = InventoryStock::firstOrCreate(
                        [
                            'campus_id' => $purchaseReturn->campus_id,
                            'inventory_item_id' => $item['inventory_item_id'],
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                        ]
                    );

                    $stock->quantity = max(0, $stock->quantity - $quantity);
                    $stock->save();
                }

                // Update return header
                $purchaseReturn->update([
                    'supplier_id' => $request->supplier_id,
                    'purchase_id' => $request->purchase_id,
                    'return_date' => $request->return_date,
                    'note' => $request->note ?? null,
                    'total_amount' => $purchaseReturn->recalculateTotal(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Purchase return updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update purchase return: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show purchase return details.
     */
    public function show(Request $request, PurchaseReturn $purchaseReturn): Response
    {
        /** @var PurchaseReturn $purchaseReturn */
        $purchaseReturn = PurchaseReturn::where('id', $purchaseReturn->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return inertia('inventory/PurchaseReturns/Show', [
            'return' => $purchaseReturn->load(['campus', 'supplier', 'user', 'items.inventoryItem']),
        ]);
    }

    /**
     * Delete a purchase return.
     */
    public function destroy(Request $request, PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn = PurchaseReturn::where('id', $purchaseReturn->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        try {
            \DB::transaction(function () use ($purchaseReturn) {
                // Reverse stock changes
                foreach ($purchaseReturn->items as $item) {
                    $stock = InventoryStock::where('campus_id', $purchaseReturn->campus_id)
                        ->where('inventory_item_id', $item->inventory_item_id)
                        ->first();

                    if ($stock) {
                        $stock->quantity += $item->quantity;
                        $stock->save();
                    }
                }

                $purchaseReturn->delete();
            });

            return back()->with('success', 'Purchase return deleted successfully. Stock has been reverted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete purchase return: ' . $e->getMessage());
        }
    }
}
