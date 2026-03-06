<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryReturnRequest;
use App\Models\Campus;
use App\Models\InventoryStock;
use App\Models\ReturnModel;
use App\Models\StudentInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class InventoryReturnsController extends Controller
{
    /**
     * Display inventory returns listing.
     *
     * IMPROVEMENTS:
     * - Added pagination
     * - Added campus_id filtering
     * - Added date range filters
     */
    public function index(Request $request)
    {
        $campusId = $request->get('campus_id');
        $studentId = $request->get('student_id');

        return inertia('inventory/Returns/Index', [
            'returns' => ReturnModel::with([
                'campus:id,name',
                'studentInventory.student' => function ($q) { $q->select('id', 'registration_no'); },
                'studentInventory.student.user' => function ($q) { $q->select('id', 'name'); },
                'studentInventory.items:id,name'
            ])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->when($studentId, fn($q) => $q->whereHas('studentInventory', fn($q) => $q->where('student_id', $studentId)))
                ->when($request->get('from_date'), fn($q) => $q->whereDate('return_date', '>=', $request->get('from_date')))
                ->when($request->get('to_date'), fn($q) => $q->whereDate('return_date', '<=', $request->get('to_date')))
                ->orderBy('return_date', 'desc')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
            'filters' => [
                'campus_id' => $campusId,
                'student_id' => $studentId,
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
            ],
        ]);
    }

    /**
     * Get all returns with optional filters.
     *
     * IMPROVEMENTS:
     * - campus_id required for multi-campus safety
     * - Consistent JSON response keys
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $studentId = $request->get('student_id');

        if (!$campusId) {
            return response()->json([
                'error' => 'campus_id is required',
                'message' => 'Please provide a campus_id filter for multi-campus safety.',
            ], 422);
        }

        if (!Campus::where('id', $campusId)->exists()) {
            return response()->json([
                'error' => 'Invalid campus_id',
                'message' => 'The specified campus does not exist.',
            ], 422);
        }

        $returns = ReturnModel::with([
            'campus:id,name',
            'studentInventory.student' => function ($q) { $q->select('id', 'registration_no'); },
            'studentInventory.student.user' => function ($q) { $q->select('id', 'name'); }
        ])
            ->where('campus_id', $campusId)
            ->when($studentId, fn($q) => $q->whereHas('studentInventory', fn($q) => $q->where('student_id', $studentId)))
            ->when($request->get('from_date'), fn($q) => $q->whereDate('return_date', '>=', $request->get('from_date')))
            ->when($request->get('to_date'), fn($q) => $q->whereDate('return_date', '<=', $request->get('to_date')))
            ->orderBy('return_date', 'desc')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($return) {
                return [
                    'id' => $return->id,
                    'campus_id' => $return->campus_id,
                    'campus_name' => $return->campus?->name,
                    'student_inventory_id' => $return->student_inventory_id,
                    'student_id' => $return->studentInventory?->student_id,
                    'student_name' => $return->studentInventory?->student?->name,
                    'registration_number' => $return->studentInventory?->student?->registration_number,
                    'item_name_snapshot' => $return->item_name_snapshot ?? $return->item_snapshot['item_name'] ?? null,
                    'description_snapshot' => $return->description_snapshot ?? $return->item_snapshot['description'] ?? null,
                    'quantity' => $return->quantity,
                    'unit_price_snapshot' => $return->unit_price_snapshot ?? $return->item_snapshot['unit_price'] ?? null,
                    'discount_snapshot' => $return->discount_snapshot ?? $return->item_snapshot['discount'] ?? null,
                    'return_date' => $return->return_date,
                    'note' => $return->note,
                    'item_snapshot' => $return->item_snapshot,
                    'created_at' => $return->created_at,
                    'updated_at' => $return->updated_at,
                    'total_value' => $return->getTotalValue(),
                ];
            });

        return response()->json($returns);
    }

    /**
     * Get dashboard summary for returns.
     *
     * IMPROVEMENT: Dashboard summary endpoint.
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        if (!$campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        $query = ReturnModel::where('campus_id', $campusId);

        $totalReturns = $query->count();
        $totalQuantity = $query->sum('quantity');
        $thisMonth = $query->thisMonth()->count();
        $thisMonthQuantity = $query->thisMonth()->sum('quantity');

        $allReturns = $query->get();
        $totalValue = $allReturns->sum(fn($r) => $r->getTotalValue());

        return response()->json([
            'totals' => [
                'total_returns' => $totalReturns,
                'total_quantity_returned' => $totalQuantity,
                'this_month_returns' => $thisMonth,
                'this_month_quantity' => $thisMonthQuantity,
            ],
            'values' => [
                'total_return_value' => $totalValue,
                'average_return_value' => $totalReturns > 0 ? round($totalValue / $totalReturns, 2) : 0,
                'average_return_quantity' => $totalReturns > 0 ? round($totalQuantity / $totalReturns, 2) : 0,
            ],
        ]);
    }

    /**
     * Show the form for creating a new return.
     *
     * REDIRECTED: Now uses modal on dashboard instead of separate page.
     */
    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('inventory.returns.index');
    }

    /**
     * Store a newly created return.
     *
     * IMPROVEMENTS:
     * - Added DB transaction
     * - Uses helper methods for stock
     * - Enhanced snapshot
     */
    public function store(StoreInventoryReturnRequest $request)
    {
        $validated = $request->validated();

        $studentInventory = StudentInventory::where('id', $validated['student_inventory_id'])
            ->when($validated['campus_id'] ?? null, fn($q) => $q->where('campus_id', $validated['campus_id']))
            ->firstOrFail();

        $maxReturnable = $studentInventory->remainingQuantity();
        if ($validated['quantity'] > $maxReturnable) {
            return back()->withErrors([
                'quantity' => 'Return quantity (' . $validated['quantity'] . ') cannot exceed remaining quantity (' . $maxReturnable . ').',
            ]);
        }

        try {
            \DB::transaction(function () use ($studentInventory, $validated) {
                // Update or create stock using helper method
                $stock = InventoryStock::firstOrCreate(
                    [
                        'campus_id' => $studentInventory->campus_id,
                        // Get the first item's inventory_item_id since parent doesn't have it directly
                        'inventory_item_id' => $studentInventory->items->first()?->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        // available_quantity is a generated column, don't set it
                    ]
                );

                $stock->restoreFromReturn($validated['quantity']);

                // Create return record with enhanced snapshot
                $snapshot = ReturnModel::createSnapshot($studentInventory);
                ReturnModel::create([
                    'campus_id' => $studentInventory->campus_id,
                    'student_inventory_id' => $validated['student_inventory_id'],
                    'quantity' => $validated['quantity'],
                    'return_date' => $validated['return_date'] ?? now()->toDateString(),
                    'note' => $validated['note'] ?? null,
                    'item_snapshot' => $snapshot,
                    // Get first item's snapshot since parent doesn't store these directly
                    'item_name_snapshot' => $studentInventory->items->first()?->item_name_snapshot,
                    'description_snapshot' => $studentInventory->items->first()?->description_snapshot,
                    'unit_price_snapshot' => $studentInventory->items->first()?->unit_price_snapshot,
                    'discount_snapshot' => $studentInventory->items->first() ? [
                        'discount_amount' => $studentInventory->items->first()?->discount_amount,
                        'discount_percentage' => $studentInventory->items->first()?->discount_percentage,
                    ] : null,
                ]);

                // Update student inventory
                $studentInventory->returned_quantity += $validated['quantity'];
                $studentInventory->status = $studentInventory->remainingQuantity() <= 0 ? 'returned' : 'partial_return';
                $studentInventory->save();
            });

            return redirect()->route('inventory.returns.index')
                ->with('success', 'Return processed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process return: ' . $e->getMessage());
        }
    }

    /**
     * Show the specified return.
     *
     * IMPROVEMENT: Added campus scope.
     */
    public function show(Request $request, ReturnModel $return): Response
    {
        /** @var ReturnModel $return */
        $return = ReturnModel::where('id', $return->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        return inertia('inventory/ReturnShow', [
            'return' => $return->load(['campus', 'studentInventory.student', 'studentInventory.inventoryItem']),
        ]);
    }

    /**
     * Soft delete a return record.
     */
    public function destroy(Request $request, ReturnModel $return)
    {
        /** @var ReturnModel $return */
        $return = ReturnModel::where('id', $return->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $return->delete();

        return back()->with('success', 'Return record deleted successfully.');
    }

    /**
     * Restore a soft-deleted return.
     */
    public function restore(Request $request, $id)
    {
        $return = ReturnModel::withTrashed()
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->findOrFail($id);

        $return->restore();

        return back()->with('success', 'Return record restored successfully.');
    }

    /**
     * Get return analysis.
     */
    public function getReturnAnalysis(Request $request, $id): JsonResponse
    {
        $campusId = $request->get('campus_id');

        $return = ReturnModel::with(['campus', 'studentInventory.student', 'studentInventory.inventoryItem'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->findOrFail($id);

        $stock = InventoryStock::where('campus_id', $return->campus_id)
            ->where('inventory_item_id', $return->studentInventory?->inventory_item_id)
            ->first();

        return response()->json([
            'return' => [
                'id' => $return->id,
                'quantity' => $return->quantity,
                'return_date' => $return->return_date,
                'note' => $return->note,
                'item_snapshot' => $return->item_snapshot,
                'total_value' => $return->getTotalValue(),
            ],
            'student' => $return->getStudentInfo(),
            'item' => $return->getItemInfo(),
            'discount_info' => $return->getDiscountInfo(),
            'stock_impact' => [
                'quantity_returned' => $return->quantity,
                'stock_before_return' => $stock ? ($stock->quantity - $return->quantity) : 0,
                'stock_after_return' => $stock?->quantity,
                'available_after' => $stock?->available_quantity,
            ],
        ]);
    }
}
