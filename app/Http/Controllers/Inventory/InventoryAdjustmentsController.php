<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\InventoryAdjustment;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class InventoryAdjustmentsController extends Controller
{
    /**
     * Display inventory adjustments listing.
     */
    public function index(Request $request): Response
    {
        $campusId = $request->get('campus_id');
        $itemId = $request->get('item_id');

        return inertia('inventory/Adjustments/Index', [
            'adjustments' => InventoryAdjustment::with(['campus:id,name', 'inventoryItem:id,name', 'user:id,name'])
                ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
                ->when($itemId, fn ($q) => $q->where('inventory_item_id', $itemId))
                ->when($request->get('type'), fn ($q) => $q->where('type', $request->get('type')))
                ->when($request->get('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from_date')))
                ->when($request->get('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to_date')))
                ->orderBy('created_at', 'desc')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
            'inventoryItems' => InventoryItem::when($campusId, fn ($q) => $q->where('campus_id', $campusId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'filters' => [
                'campus_id' => $campusId,
                'item_id' => $itemId,
                'type' => $request->get('type'),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
            ],
        ]);
    }

    /**
     * Get all adjustments with optional filters.
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        if (! $campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        $adjustments = InventoryAdjustment::with(['campus:id,name', 'inventoryItem:id,name'])
            ->when($campusId, fn ($q) => $q->where('campus_id', $campusId))
            ->when($request->get('item_id'), fn ($q) => $q->where('inventory_item_id', $request->get('item_id')))
            ->when($request->get('type'), fn ($q) => $q->where('type', $request->get('type')))
            ->when($request->get('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from_date')))
            ->when($request->get('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to_date')))
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json($adjustments);
    }

    /**
     * Show form for creating a new adjustment.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('inventory.adjustments.index');
    }

    /**
     * Store a new inventory adjustment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'type' => 'required|in:add,subtract,set',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
            'reference_number' => 'nullable|string|max:100',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Get or create stock record
                $stock = InventoryStock::firstOrCreate(
                    [
                        'campus_id' => $request->campus_id,
                        'inventory_item_id' => $request->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        // available_quantity is a generated column, don't set it
                    ]
                );

                $previousQuantity = $stock->quantity ?? 0;
                $quantity = (int) $request->quantity;
                $newQuantity = 0;

                switch ($request->type) {
                    case 'add':
                        $newQuantity = $previousQuantity + $quantity;
                        break;
                    case 'subtract':
                        $newQuantity = max(0, $previousQuantity - $quantity);
                        break;
                    case 'set':
                        $newQuantity = $quantity;
                        break;
                }

                // Update stock
                $stock->quantity = $newQuantity;
                // available_quantity is a generated column, don't set it
                $stock->save();

                // Create adjustment record
                InventoryAdjustment::create([
                    'campus_id' => $request->campus_id,
                    'inventory_item_id' => $request->inventory_item_id,
                    'user_id' => auth()->id(),
                    'type' => $request->type,
                    'quantity' => $quantity,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'reason' => $request->reason,
                    'reference_number' => $request->reference_number,
                ]);
            });

            return redirect()->route('inventory.adjustments.index')
                ->with('success', 'Stock adjustment created successfully. Stock has been updated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create adjustment: '.$e->getMessage());
        }
    }

    /**
     * Show adjustment details.
     */
    public function show(Request $request, InventoryAdjustment $adjustment): Response
    {
        /** @var InventoryAdjustment $adjustment */
        $adjustment = InventoryAdjustment::where('id', $adjustment->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return inertia('inventory/Adjustments/Show', [
            'adjustment' => $adjustment->load(['campus', 'inventoryItem', 'user']),
        ]);
    }

    /**
     * Delete an adjustment (reverts stock).
     */
    public function destroy(Request $request, InventoryAdjustment $adjustment)
    {
        $adjustment = InventoryAdjustment::where('id', $adjustment->id)
            ->when($request->get('campus_id'), fn ($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        try {
            DB::transaction(function () use ($adjustment) {
                // Revert stock
                $stock = InventoryStock::where('campus_id', $adjustment->campus_id)
                    ->where('inventory_item_id', $adjustment->inventory_item_id)
                    ->first();

                if ($stock) {
                    switch ($adjustment->type) {
                        case 'add':
                            $stock->quantity = max(0, $stock->quantity - $adjustment->quantity);
                            break;
                        case 'subtract':
                            $stock->quantity = $adjustment->previous_quantity + $adjustment->quantity;
                            break;
                        case 'set':
                            $stock->quantity = $adjustment->previous_quantity;
                            break;
                    }
                    // available_quantity is a generated column, don't set it
                    $stock->save();
                }

                $adjustment->delete();
            });

            return back()->with('success', 'Adjustment deleted and stock reverted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete adjustment: '.$e->getMessage());
        }
    }

    /**
     * Get adjustment summary for dashboard.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        if (! $campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        $today = now()->toDateString();

        $additions = InventoryAdjustment::where('campus_id', $campusId)
            ->where('type', 'add')
            ->when($request->get('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from_date')))
            ->when($request->get('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to_date')))
            ->count();

        $subtractions = InventoryAdjustment::where('campus_id', $campusId)
            ->where('type', 'subtract')
            ->when($request->get('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from_date')))
            ->when($request->get('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to_date')))
            ->count();

        return response()->json([
            'total_adjustments' => $additions + $subtractions,
            'additions_count' => $additions,
            'subtractions_count' => $subtractions,
        ]);
    }
}
