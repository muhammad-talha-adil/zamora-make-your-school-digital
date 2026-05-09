<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryType;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\StudentInventoryReturn;
use App\Models\Supplier;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class InventoryPageController extends Controller
{
    public function settings(): Response
    {
        return Inertia::render('inventory/InventorySettings', [
            'inventoryTypes' => InventoryType::with(['campus:id,name'])
                ->select('id', 'name', 'campus_id', 'is_active', 'created_at')
                ->withCount('inventoryItems')
                ->orderBy('name')
                ->paginate(20),
            'inventoryItems' => InventoryItem::with(['campus:id,name', 'inventoryType:id,name'])
                ->withCount('inventoryStock')
                ->orderBy('name')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    }

    public function itemsStock(): Response
    {
        $allTypes = InventoryType::with(['campus:id,name'])
            ->select('id', 'name', 'campus_id', 'is_active')
            ->orderBy('name')
            ->get();

        $allItems = InventoryItem::with(['campus:id,name', 'inventoryType:id,name', 'inventoryStock'])
            ->select('id', 'name', 'description', 'campus_id', 'inventory_type_id', 'is_active')
            ->orderBy('name')
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
                    'is_active' => $item->is_active,
                    'current_stock' => (int) $item->inventoryStock->sum('available_quantity'),
                ];
            })
            ->values();

        $stocks = InventoryStock::with(['campus:id,name', 'inventoryItem:id,name', 'inventoryItem.inventoryType:id,name'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $stocks->getCollection()->transform(function ($stock) {
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

        return Inertia::render('inventory/ItemsStock', [
            'inventoryTypes' => InventoryType::with(['campus:id,name'])
                ->select('id', 'name', 'campus_id', 'is_active', 'created_at')
                ->withCount('inventoryItems')
                ->orderBy('name')
                ->paginate(20),
            'inventoryItems' => InventoryItem::with(['campus:id,name', 'inventoryType:id,name'])
                ->withCount('inventoryStock')
                ->orderBy('name')
                ->paginate(20),
            'inventoryStocks' => $stocks,
            'allInventoryTypes' => $allTypes,
            'allInventoryItems' => $allItems,
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    }

    public function purchasesManage(): Response
    {
        return Inertia::render('inventory/PurchasesManage', [
            'purchases' => Purchase::with(['campus:id,name', 'supplier:id,name'])
                ->orderBy('purchase_date', 'desc')
                ->paginate(20),
            'suppliers' => Supplier::with(['campus:id,name'])
                ->orderBy('name')
                ->paginate(20),
            'purchaseReturns' => PurchaseReturn::with(['campus:id,name', 'supplier:id,name'])
                ->orderBy('return_date', 'desc')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    }

    public function purchaseView(int $id): Response
    {
        return Inertia::render('inventory/PurchaseView', [
            'purchaseId' => $id,
        ]);
    }

    public function studentManage(): Response
    {
        $studentInventories = StudentInventory::with(['campus:id,name', 'student', 'items', 'items.inventoryItem:id,name'])
            ->orderBy('assigned_date', 'desc')
            ->paginate(20);

        $studentInventories->getCollection()->transform(function ($si) {
            $totalQuantity = $si->items->sum('quantity');

            return [
                'id' => $si->id,
                'student_inventory_id' => $si->student_inventory_id,
                'student' => $si->student ? [
                    'id' => $si->student->id,
                    'name' => $si->student->user?->name ?? 'N/A',
                    'registration_number' => $si->student->registration_no,
                ] : null,
                'items' => $si->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'item_name_snapshot' => $item->item_name_snapshot,
                        'quantity' => $item->quantity,
                        'inventory_item' => $item->inventoryItem ? [
                            'id' => $item->inventoryItem->id,
                            'name' => $item->inventoryItem->name,
                        ] : null,
                    ];
                }),
                'quantity' => $totalQuantity,
                'final_amount' => $si->final_amount,
                'assigned_date' => $si->assigned_date,
                'status' => $si->status,
                'campus' => $si->campus ? [
                    'id' => $si->campus->id,
                    'name' => $si->campus->name,
                ] : null,
                'student_name' => $si->student?->user?->name ?? 'N/A',
                'registration_number' => $si->student?->registration_no,
            ];
        });

        $returns = StudentInventoryReturn::with(['campus:id,name', 'student', 'studentInventoryRecord', 'items'])
            ->orderBy('return_date', 'desc')
            ->paginate(20);

        $returns->getCollection()->transform(function ($r) {
            return [
                'id' => $r->id,
                'return_id' => $r->return_id,
                'student' => $r->student ? [
                    'id' => $r->student->id,
                    'name' => $r->student->user?->name ?? 'N/A',
                    'registration_number' => $r->student->registration_no,
                ] : null,
                'student_name' => $r->student?->user?->name ?? 'N/A',
                'registration_number' => $r->student?->registration_no,
                'total_quantity' => $r->total_quantity,
                'total_amount' => $r->total_amount,
                'return_date' => $r->return_date,
                'status' => $r->status,
                'campus_name' => $r->campus?->name,
                'items' => $r->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_name_snapshot' => $item->item_snapshot['item_name'] ?? 'N/A',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'return_price' => $item->return_price,
                        'total_amount' => $item->total_amount,
                        'reason_id' => $item->reason_id,
                        'custom_reason' => $item->custom_reason,
                    ];
                }),
            ];
        });

        return Inertia::render('inventory/StudentManage', [
            'studentInventories' => $studentInventories,
            'returns' => $returns,
            'campuses' => Campus::orderBy('name')->get(),
            'students' => Student::with('user:id,name')->orderBy('registration_no')->get()->map(
                fn ($s) => ['id' => $s->id, 'name' => $s->name, 'registration_number' => $s->registration_no]
            ),
            'inventoryItems' => InventoryItem::select('id', 'name', 'description')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function purchaseCreate(): Response
    {
        return Inertia::render('inventory/PurchaseCreate', [
            'campuses' => Campus::orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'campus_id']),
        ]);
    }

    public function purchaseEdit(Purchase $purchase): Response
    {
        $purchase->load(['campus:id,name', 'supplier:id,name', 'purchaseItems.inventoryItem:id,name']);

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

        return Inertia::render('inventory/PurchaseCreate', [
            'campuses' => Campus::orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'campus_id']),
            'purchase' => [
                'id' => $purchase->id,
                'campus_id' => $purchase->campus_id,
                'supplier_id' => $purchase->supplier_id,
                'supplier' => $purchase->supplier ? [
                    'id' => $purchase->supplier->id,
                    'name' => $purchase->supplier->name,
                ] : null,
                'purchase_date' => $purchase->purchase_date,
                'note' => $purchase->note,
                'items' => $items,
            ],
        ]);
    }

    public function purchaseReturnCreate(): Response
    {
        return Inertia::render('inventory/PurchaseReturnCreate', [
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    }

    public function purchaseReturnEdit(PurchaseReturn $purchaseReturn): Response
    {
        $purchaseReturn->load([
            'campus:id,name',
            'supplier:id,name',
            'purchase:id,purchase_id,supplier_id,campus_id,purchase_date,total_amount',
            'items.inventoryItem:id,name',
            'items.reason:id,name',
        ]);

        $items = $purchaseReturn->items->map(function ($item) {
            return [
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'reason_id' => $item->reason_id,
                'reason' => $item->reason,
                'inventory_item' => $item->inventoryItem ? [
                    'id' => $item->inventoryItem->id,
                    'name' => $item->inventoryItem->name,
                ] : null,
            ];
        });

        $purchaseDisplayText = null;
        if ($purchaseReturn->purchase) {
            $purchaseDisplayText = $purchaseReturn->purchase->purchase_id.' | '.
                ($purchaseReturn->supplier?->name ?? 'N/A').' | '.
                Carbon::parse($purchaseReturn->purchase->purchase_date)->format('d M Y');
        }

        return Inertia::render('inventory/PurchaseReturnCreate', [
            'campuses' => Campus::orderBy('name')->get(),
            'return' => [
                'id' => $purchaseReturn->id,
                'campus_id' => $purchaseReturn->campus_id,
                'supplier_id' => $purchaseReturn->supplier_id,
                'supplier' => $purchaseReturn->supplier ? [
                    'id' => $purchaseReturn->supplier->id,
                    'name' => $purchaseReturn->supplier->name,
                ] : null,
                'purchase_id' => $purchaseReturn->purchase_id,
                'purchase' => $purchaseReturn->purchase ? [
                    'id' => $purchaseReturn->purchase->id,
                    'purchase_id' => $purchaseReturn->purchase->purchase_id,
                    'purchase_date' => $purchaseReturn->purchase->purchase_date,
                    'total_amount' => $purchaseReturn->purchase->total_amount,
                    'display_text' => $purchaseDisplayText,
                    'name' => $purchaseDisplayText,
                    'supplier_id' => $purchaseReturn->purchase->supplier_id,
                    'campus_id' => $purchaseReturn->purchase->campus_id,
                ] : null,
                'return_date' => $purchaseReturn->return_date,
                'note' => $purchaseReturn->note,
                'total_amount' => $purchaseReturn->total_amount,
                'items' => $items,
            ],
        ]);
    }
}
