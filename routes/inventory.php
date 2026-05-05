<?php

use App\Http\Controllers\Inventory\InventoryAdjustmentsController;
use App\Http\Controllers\Inventory\InventoryItemsController;
use App\Http\Controllers\Inventory\InventoryReturnsController;
use App\Http\Controllers\Inventory\InventoryStocksController;
use App\Http\Controllers\Inventory\InventoryTypesController;
use App\Http\Controllers\Inventory\PurchaseReturnsController;
use App\Http\Controllers\Inventory\PurchasesController;
use App\Http\Controllers\Inventory\StudentInventoriesController;
use App\Http\Controllers\Inventory\SuppliersController;
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
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    // ==================== INVENTORY DASHBOARD ====================
    Route::get('/inventory/dashboard-data', [InventoryItemsController::class, 'getDashboardData'])->name('inventory.dashboard-data');

    // ==================== INVENTORY SETTINGS ====================
    // Note: Uses global scope which filters by is_active=true and excludes soft-deleted records
    Route::get('/inventory/settings', function () {
        return Inertia::render('inventory/InventorySettings', [
            // Global scope automatically applies where('is_active', true) and whereNull('deleted_at')
            'inventoryTypes' => InventoryType::with(['campus:id,name'])
                ->select('id', 'name', 'campus_id', 'is_active', 'created_at')
                ->withCount('inventoryItems')
                ->orderBy('name')
                ->paginate(20),
            // Global scope automatically applies where('is_active', true) and whereNull('deleted_at')
            'inventoryItems' => InventoryItem::with(['campus:id,name', 'inventoryType:id,name'])
                ->withCount('inventoryStock')
                ->orderBy('name')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    })->name('inventory.settings');

    // ==================== NEW CONSOLIDATED PAGES ====================
    // Items & Stock - Tabs: Types | Items | Stocks | Adjustments
    Route::get('/inventory/items-stock', function () {
        // Get paginated stocks with transformed data
        $stocks = InventoryStock::with(['campus:id,name', 'inventoryItem:id,name', 'inventoryItem.inventoryType:id,name'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Transform to match the format used by the API
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
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    })->name('inventory.items-stock');

    // Purchases - Tabs: Purchases | Suppliers | Purchase Returns
    Route::get('/inventory/purchases-manage', function () {
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
    })->name('inventory.purchases-manage');

    // View Purchase Details
    Route::get('/inventory/purchases/{id}/view', function ($id) {
        return Inertia::render('inventory/PurchaseView', [
            'purchaseId' => (int) $id,
        ]);
    })->name('inventory.purchases.view');

    // Student Inventory - Tabs: Assigned Items | Returns
    Route::get('/inventory/student-manage', function () {
        // Get paginated student inventories with transformed student data
        $studentInventories = StudentInventory::with(['campus:id,name', 'student', 'items', 'items.inventoryItem:id,name'])
            ->orderBy('assigned_date', 'desc')
            ->paginate(20);

        // Transform the student data
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

        // Get paginated returns with transformed student data (using new StudentInventoryReturn model)
        $returns = StudentInventoryReturn::with(['campus:id,name', 'student', 'studentInventoryRecord', 'items'])
            ->orderBy('return_date', 'desc')
            ->paginate(20);

        $returns->getCollection()->transform(function ($r) {
            $studentInventory = $r->studentInventoryRecord;

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
            'students' => Student::with('user:id,name')->orderBy('registration_no')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'registration_number' => $s->registration_no]),
            'inventoryItems' => InventoryItem::select('id', 'name', 'description')->where('is_active', true)->orderBy('name')->get(),
        ]);
    })->name('inventory.student-manage');

    // ==================== INVENTORY TYPES ====================
    Route::prefix('inventory/types')->name('inventory.types.')->group(function () {
        Route::get('/', [InventoryTypesController::class, 'index'])->name('index');
        Route::get('/all', [InventoryTypesController::class, 'getAll'])->name('all');
        Route::get('/paginated', [InventoryTypesController::class, 'getPaginated'])->name('paginated');
        Route::get('/check-name-exists', [InventoryTypesController::class, 'checkNameExists'])->name('check-name-exists');
        Route::get('/create', [InventoryTypesController::class, 'create'])->name('create');
        Route::post('/', [InventoryTypesController::class, 'store'])->name('store');
        Route::get('/{inventoryType}/edit', [InventoryTypesController::class, 'edit'])->name('edit');
        Route::put('/{inventoryType}', [InventoryTypesController::class, 'update'])->name('update');
        Route::delete('/{inventoryType}', [InventoryTypesController::class, 'destroy'])->name('destroy');
        Route::patch('/{inventoryType}/inactivate', [InventoryTypesController::class, 'inactivate'])->name('inactivate');
        Route::patch('/{inventoryType}/activate', [InventoryTypesController::class, 'activate'])->name('activate');
    });

    // ==================== INVENTORY ITEMS ====================
    Route::prefix('inventory/items')->name('inventory.items.')->group(function () {
        Route::get('/', [InventoryItemsController::class, 'index'])->name('index');
        Route::get('/all', [InventoryItemsController::class, 'getAll'])->name('all');
        Route::get('/paginated', [InventoryItemsController::class, 'getPaginated'])->name('paginated');
        Route::get('/create', [InventoryItemsController::class, 'create'])->name('create');
        Route::post('/', [InventoryItemsController::class, 'store'])->name('store');
        Route::get('/{inventoryItem}/edit', [InventoryItemsController::class, 'edit'])->name('edit');
        Route::put('/{inventoryItem}', [InventoryItemsController::class, 'update'])->name('update');
        Route::delete('/{inventoryItem}', [InventoryItemsController::class, 'destroy'])->name('destroy');
        Route::patch('/{inventoryItem}/inactivate', [InventoryItemsController::class, 'inactivate'])->name('inactivate');
        Route::patch('/{inventoryItem}/activate', [InventoryItemsController::class, 'activate'])->name('activate');
        Route::get('/{inventoryItem}/low-stock', [InventoryItemsController::class, 'getLowStockItems'])->name('low-stock');
    });

    // ==================== INVENTORY STOCKS ====================
    Route::prefix('inventory/stocks')->name('inventory.stocks.')->group(function () {
        Route::get('/', [InventoryStocksController::class, 'index'])->name('index');
        Route::get('/all', [InventoryStocksController::class, 'getAll'])->name('all');
        Route::get('/check-availability', [InventoryStocksController::class, 'checkAvailability'])->name('check-availability');
        Route::get('/low-stock', [InventoryStocksController::class, 'getLowStockItems'])->name('low-stock');
        Route::post('/reserve', [InventoryStocksController::class, 'reserve'])->name('reserve');
        Route::post('/release', [InventoryStocksController::class, 'release'])->name('release');
        Route::put('/{id}/threshold', [InventoryStocksController::class, 'updateThreshold'])->name('update-threshold');
    });

    // ==================== SUPPLIERS ====================
    Route::prefix('inventory/suppliers')->name('inventory.suppliers.')->group(function () {
        Route::get('/', [SuppliersController::class, 'index'])->name('index');
        Route::get('/all', [SuppliersController::class, 'getAll'])->name('all');
        Route::get('/check-name-exists', [SuppliersController::class, 'checkNameExists'])->name('check-name-exists');
        Route::get('/create', [SuppliersController::class, 'create'])->name('create');
        Route::post('/', [SuppliersController::class, 'store'])->name('store');
        Route::get('/{supplier}', [SuppliersController::class, 'show'])->name('show');
        Route::get('/{supplier}/edit', [SuppliersController::class, 'edit'])->name('edit');
        Route::put('/{supplier}', [SuppliersController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SuppliersController::class, 'destroy'])->name('destroy');
        Route::patch('/{supplier}/inactivate', [SuppliersController::class, 'inactivate'])->name('inactivate');
        Route::patch('/{supplier}/activate', [SuppliersController::class, 'activate'])->name('activate');
    });

    // ==================== INVENTORY ADJUSTMENTS ====================
    Route::prefix('inventory/adjustments')->name('inventory.adjustments.')->group(function () {
        Route::get('/', [InventoryAdjustmentsController::class, 'index'])->name('index');
        Route::get('/all', [InventoryAdjustmentsController::class, 'getAll'])->name('all');
        Route::get('/create', [InventoryAdjustmentsController::class, 'create'])->name('create');
        Route::post('/', [InventoryAdjustmentsController::class, 'store'])->name('store');
        Route::get('/{adjustment}', [InventoryAdjustmentsController::class, 'show'])->name('show');
        Route::delete('/{adjustment}', [InventoryAdjustmentsController::class, 'destroy'])->name('destroy');
        Route::get('/summary', [InventoryAdjustmentsController::class, 'getSummary'])->name('summary');
    });

    // ==================== PURCHASES ====================
    Route::prefix('inventory/purchases')->name('inventory.purchases.')->group(function () {
        Route::get('/', [PurchasesController::class, 'index'])->name('index');
        Route::get('/all', [PurchasesController::class, 'getAll'])->name('all');
        Route::get('/create', function () {
            return Inertia::render('inventory/PurchaseCreate', [
                'campuses' => Campus::orderBy('name')->get(),
                'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name', 'campus_id']),
            ]);
        })->name('create');
        Route::get('/{purchase}/edit', function ($id) {
            $purchase = Purchase::with(['campus:id,name', 'supplier:id,name', 'purchaseItems.inventoryItem:id,name'])->findOrFail($id);

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
        })->name('edit');
        Route::get('/{purchase}', [PurchasesController::class, 'getPurchase'])->name('show');
        Route::post('/', [PurchasesController::class, 'store'])->name('store');
        Route::put('/{purchase}', [PurchasesController::class, 'update'])->name('update');
        Route::delete('/{purchase}', [PurchasesController::class, 'destroy'])->name('destroy');
        Route::post('/cancel', [PurchasesController::class, 'cancel'])->name('cancel');
        Route::get('/analysis/{id}', [PurchasesController::class, 'getPurchaseAnalysis'])->name('analysis');
    });

    // ==================== PURCHASE RETURNS ====================
    Route::prefix('inventory/purchase-returns')->name('inventory.purchase-returns.')->group(function () {
        Route::get('/', [PurchaseReturnsController::class, 'index'])->name('index');
        Route::get('/all', [PurchaseReturnsController::class, 'getAll'])->name('all');
        Route::get('/reasons', [PurchaseReturnsController::class, 'getReasons'])->name('reasons');
        Route::get('/suppliers', [PurchaseReturnsController::class, 'getSuppliers'])->name('suppliers');
        Route::get('/purchases', [PurchaseReturnsController::class, 'getPurchases'])->name('purchases');
        Route::get('/create', function () {
            return Inertia::render('inventory/PurchaseReturnCreate', [
                'campuses' => Campus::orderBy('name')->get(),
            ]);
        })->name('create');
        Route::get('/{purchaseReturn}/edit', function ($id) {
            $return = PurchaseReturn::with(['campus:id,name', 'supplier:id,name', 'purchase:id,purchase_id,supplier_id,campus_id,purchase_date,total_amount', 'items.inventoryItem:id,name', 'items.reason:id,name'])
                ->findOrFail($id);

            $items = $return->items->map(function ($item) {
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

            // Build display text for the original purchase
            $purchaseDisplayText = null;
            if ($return->purchase) {
                $purchaseDisplayText = $return->purchase->purchase_id.' | '.
                    ($return->supplier?->name ?? 'N/A').' | '.
                    (new DateTime($return->purchase->purchase_date))->format('d M Y');
            }

            return Inertia::render('inventory/PurchaseReturnCreate', [
                'campuses' => Campus::orderBy('name')->get(),
                'return' => [
                    'id' => $return->id,
                    'campus_id' => $return->campus_id,
                    'supplier_id' => $return->supplier_id,
                    'supplier' => $return->supplier ? [
                        'id' => $return->supplier->id,
                        'name' => $return->supplier->name,
                    ] : null,
                    'purchase_id' => $return->purchase_id,
                    'purchase' => $return->purchase ? [
                        'id' => $return->purchase->id,
                        'purchase_id' => $return->purchase->purchase_id,
                        'purchase_date' => $return->purchase->purchase_date,
                        'total_amount' => $return->purchase->total_amount,
                        'display_text' => $purchaseDisplayText,
                        'name' => $purchaseDisplayText,
                        'supplier_id' => $return->purchase->supplier_id,
                        'campus_id' => $return->purchase->campus_id,
                    ] : null,
                    'return_date' => $return->return_date,
                    'note' => $return->note,
                    'total_amount' => $return->total_amount,
                    'items' => $items,
                ],
            ]);
        })->name('edit');
        Route::post('/', [PurchaseReturnsController::class, 'store'])->name('store');
        Route::get('/{purchaseReturn}', [PurchaseReturnsController::class, 'show'])->name('show');
        Route::put('/{purchaseReturn}', [PurchaseReturnsController::class, 'update'])->name('update');
        Route::delete('/{purchaseReturn}', [PurchaseReturnsController::class, 'destroy'])->name('destroy');
        Route::get('/purchase/{purchase}/items', [PurchaseReturnsController::class, 'getPurchaseItems'])->name('purchase-items');
    });

    // ==================== STUDENT INVENTORY ====================
    Route::prefix('inventory/student-inventory')->name('inventory.student-inventory.')->group(function () {
        Route::get('/', [StudentInventoriesController::class, 'index'])->name('index');
        Route::get('/all', [StudentInventoriesController::class, 'getAll'])->name('all');
        Route::get('/create', [StudentInventoriesController::class, 'create'])->name('create');
        Route::post('/assign', [StudentInventoriesController::class, 'assign'])->name('assign');
        Route::post('/return', [StudentInventoriesController::class, 'processReturn'])->name('return.process');
        Route::get('/students/with-inventory', [StudentInventoriesController::class, 'getStudentsWithInventory'])->name('students.with-inventory');
        Route::get('/students', [StudentInventoriesController::class, 'getStudents'])->name('students');
        Route::get('/items', [StudentInventoriesController::class, 'getInventoryItems'])->name('items');
        Route::get('/types', [StudentInventoriesController::class, 'getInventoryTypes'])->name('types');
        Route::get('/{studentInventory}/return', [StudentInventoriesController::class, 'createReturn'])->name('return.create');
        Route::post('/{studentInventory}/return', [StudentInventoriesController::class, 'return'])->name('return.store');
        Route::get('/return/{studentInventoryReturn}/view', [StudentInventoriesController::class, 'showReturn'])->name('return.view');
        Route::get('/{studentInventory}/check-return', [StudentInventoriesController::class, 'checkReturnAvailability'])->name('check-return');
        Route::get('/dashboard-summary', [StudentInventoriesController::class, 'getDashboardSummary'])->name('dashboard-summary');
        Route::get('/{studentInventory}/view', [StudentInventoriesController::class, 'show'])->name('show');
    });

    // ==================== INVENTORY RETURNS (Student to School) ====================
    Route::prefix('inventory/returns')->name('inventory.returns.')->group(function () {
        Route::get('/', [InventoryReturnsController::class, 'index'])->name('index');
        Route::get('/all', [InventoryReturnsController::class, 'getAll'])->name('all');
        Route::get('/create', [InventoryReturnsController::class, 'create'])->name('create');
        Route::post('/', [InventoryReturnsController::class, 'store'])->name('store');
        Route::get('/{return}', [InventoryReturnsController::class, 'show'])->name('show');
        Route::delete('/{return}', [InventoryReturnsController::class, 'destroy'])->name('destroy');
        Route::get('/analysis/{id}', [InventoryReturnsController::class, 'getReturnAnalysis'])->name('analysis');
    });
});
