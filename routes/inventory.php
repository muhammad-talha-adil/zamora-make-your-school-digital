<?php

use App\Http\Controllers\Inventory\InventoryAdjustmentsController;
use App\Http\Controllers\Inventory\InventoryItemsController;
use App\Http\Controllers\Inventory\InventoryPageController;
use App\Http\Controllers\Inventory\InventoryReturnsController;
use App\Http\Controllers\Inventory\InventoryStocksController;
use App\Http\Controllers\Inventory\InventoryTypesController;
use App\Http\Controllers\Inventory\PurchaseReturnsController;
use App\Http\Controllers\Inventory\PurchasesController;
use App\Http\Controllers\Inventory\StudentInventoriesController;
use App\Http\Controllers\Inventory\SuppliersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Inventory routes
|--------------------------------------------------------------------------
|
| Every route below now carries a `permission:` check. Until this pass none
| of them did — the ten seeded `inventory.*` permissions (see
| PermissionsSeeder) were never consulted anywhere, so any signed-in,
| verified account could view, create, adjust or delete stock, purchases,
| suppliers and student issuances regardless of role.
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // ==================== INVENTORY DASHBOARD ====================
    Route::get('/inventory/dashboard-data', [InventoryItemsController::class, 'getDashboardData'])
        ->name('inventory.dashboard-data')
        ->middleware('permission:inventory.view');

    // ==================== INVENTORY SETTINGS ====================
    // Note: Uses global scope which filters by is_active=true and excludes soft-deleted records
    Route::get('/inventory/settings', [InventoryPageController::class, 'settings'])
        ->name('inventory.settings')
        ->middleware('permission:inventory.view');

    // ==================== NEW CONSOLIDATED PAGES ====================
    // Items & Stock - Tabs: Types | Items | Stocks | Adjustments
    Route::get('/inventory/items-stock', [InventoryPageController::class, 'itemsStock'])
        ->name('inventory.items-stock')
        ->middleware('permission:inventory.view');

    // Purchases - Tabs: Purchases | Suppliers | Purchase Returns
    Route::get('/inventory/purchases-manage', [InventoryPageController::class, 'purchasesManage'])
        ->name('inventory.purchases-manage')
        ->middleware('permission:inventory.view|inventory.purchase.view');

    // View Purchase Details
    Route::get('/inventory/purchases/{id}/view', [InventoryPageController::class, 'purchaseView'])
        ->name('inventory.purchases.view')
        ->middleware('permission:inventory.view|inventory.purchase.view');

    // Student Inventory - Tabs: Assigned Items | Returns
    Route::get('/inventory/student-manage', [InventoryPageController::class, 'studentManage'])
        ->name('inventory.student-manage')
        ->middleware('permission:inventory.view|inventory.student.issue');

    // ==================== INVENTORY TYPES ====================
    Route::prefix('inventory/types')->name('inventory.types.')->group(function () {
        Route::get('/', [InventoryTypesController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.item.manage');
        Route::get('/all', [InventoryTypesController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.item.manage');
        Route::get('/paginated', [InventoryTypesController::class, 'getPaginated'])->name('paginated')
            ->middleware('permission:inventory.view|inventory.item.manage');
        Route::get('/check-name-exists', [InventoryTypesController::class, 'checkNameExists'])->name('check-name-exists')
            ->middleware('permission:inventory.item.manage');
        Route::get('/create', [InventoryTypesController::class, 'create'])->name('create')
            ->middleware('permission:inventory.item.manage');
        Route::post('/', [InventoryTypesController::class, 'store'])->name('store')
            ->middleware('permission:inventory.item.manage');
        Route::get('/{inventoryType}/edit', [InventoryTypesController::class, 'edit'])->name('edit')
            ->middleware('permission:inventory.item.manage');
        Route::put('/{inventoryType}', [InventoryTypesController::class, 'update'])->name('update')
            ->middleware('permission:inventory.item.manage');
        Route::delete('/{inventoryType}', [InventoryTypesController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.item.manage');
        Route::patch('/{inventoryType}/inactivate', [InventoryTypesController::class, 'inactivate'])->name('inactivate')
            ->middleware('permission:inventory.item.manage');
        Route::patch('/{inventoryType}/activate', [InventoryTypesController::class, 'activate'])->name('activate')
            ->middleware('permission:inventory.item.manage');
    });

    // ==================== INVENTORY ITEMS ====================
    Route::prefix('inventory/items')->name('inventory.items.')->group(function () {
        Route::get('/', [InventoryItemsController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.item.manage');
        Route::get('/all', [InventoryItemsController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.item.manage');
        Route::get('/paginated', [InventoryItemsController::class, 'getPaginated'])->name('paginated')
            ->middleware('permission:inventory.view|inventory.item.manage');
        Route::get('/create', [InventoryItemsController::class, 'create'])->name('create')
            ->middleware('permission:inventory.item.manage');
        Route::post('/', [InventoryItemsController::class, 'store'])->name('store')
            ->middleware('permission:inventory.item.manage');
        Route::get('/{inventoryItem}/edit', [InventoryItemsController::class, 'edit'])->name('edit')
            ->middleware('permission:inventory.item.manage');
        Route::put('/{inventoryItem}', [InventoryItemsController::class, 'update'])->name('update')
            ->middleware('permission:inventory.item.manage');
        Route::delete('/{inventoryItem}', [InventoryItemsController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.item.manage');
        Route::patch('/{inventoryItem}/inactivate', [InventoryItemsController::class, 'inactivate'])->name('inactivate')
            ->middleware('permission:inventory.item.manage');
        Route::patch('/{inventoryItem}/activate', [InventoryItemsController::class, 'activate'])->name('activate')
            ->middleware('permission:inventory.item.manage');
        Route::get('/{inventoryItem}/low-stock', [InventoryItemsController::class, 'getLowStockItems'])->name('low-stock')
            ->middleware('permission:inventory.view|inventory.stock.manage');
    });

    // ==================== INVENTORY STOCKS ====================
    Route::prefix('inventory/stocks')->name('inventory.stocks.')->group(function () {
        Route::get('/', [InventoryStocksController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::get('/all', [InventoryStocksController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::get('/check-availability', [InventoryStocksController::class, 'checkAvailability'])->name('check-availability')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::get('/low-stock', [InventoryStocksController::class, 'getLowStockItems'])->name('low-stock')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::post('/reserve', [InventoryStocksController::class, 'reserve'])->name('reserve')
            ->middleware('permission:inventory.stock.manage');
        Route::post('/release', [InventoryStocksController::class, 'release'])->name('release')
            ->middleware('permission:inventory.stock.manage');
        Route::put('/{id}/threshold', [InventoryStocksController::class, 'updateThreshold'])->name('update-threshold')
            ->middleware('permission:inventory.stock.manage');
    });

    // ==================== SUPPLIERS ====================
    Route::prefix('inventory/suppliers')->name('inventory.suppliers.')->group(function () {
        Route::get('/', [SuppliersController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.supplier.manage');
        Route::get('/all', [SuppliersController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.supplier.manage');
        Route::get('/check-name-exists', [SuppliersController::class, 'checkNameExists'])->name('check-name-exists')
            ->middleware('permission:inventory.supplier.manage');
        Route::get('/create', [SuppliersController::class, 'create'])->name('create')
            ->middleware('permission:inventory.supplier.manage');
        Route::post('/', [SuppliersController::class, 'store'])->name('store')
            ->middleware('permission:inventory.supplier.manage');
        Route::get('/{supplier}', [SuppliersController::class, 'show'])->name('show')
            ->middleware('permission:inventory.view|inventory.supplier.manage');
        Route::get('/{supplier}/edit', [SuppliersController::class, 'edit'])->name('edit')
            ->middleware('permission:inventory.supplier.manage');
        Route::put('/{supplier}', [SuppliersController::class, 'update'])->name('update')
            ->middleware('permission:inventory.supplier.manage');
        Route::delete('/{supplier}', [SuppliersController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.supplier.manage');
        Route::patch('/{supplier}/inactivate', [SuppliersController::class, 'inactivate'])->name('inactivate')
            ->middleware('permission:inventory.supplier.manage');
        Route::patch('/{supplier}/activate', [SuppliersController::class, 'activate'])->name('activate')
            ->middleware('permission:inventory.supplier.manage');
    });

    // ==================== INVENTORY ADJUSTMENTS ====================
    Route::prefix('inventory/adjustments')->name('inventory.adjustments.')->group(function () {
        Route::get('/', [InventoryAdjustmentsController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::get('/all', [InventoryAdjustmentsController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::get('/create', [InventoryAdjustmentsController::class, 'create'])->name('create')
            ->middleware('permission:inventory.stock.manage');
        Route::post('/', [InventoryAdjustmentsController::class, 'store'])->name('store')
            ->middleware('permission:inventory.stock.manage');
        Route::get('/{adjustment}', [InventoryAdjustmentsController::class, 'show'])->name('show')
            ->middleware('permission:inventory.view|inventory.stock.manage');
        Route::delete('/{adjustment}', [InventoryAdjustmentsController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.stock.manage');
        Route::get('/summary', [InventoryAdjustmentsController::class, 'getSummary'])->name('summary')
            ->middleware('permission:inventory.view|inventory.stock.manage');
    });

    // ==================== PURCHASES ====================
    Route::prefix('inventory/purchases')->name('inventory.purchases.')->group(function () {
        Route::get('/', [PurchasesController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.purchase.view');
        Route::get('/all', [PurchasesController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.purchase.view');
        Route::get('/create', [InventoryPageController::class, 'purchaseCreate'])->name('create')
            ->middleware('permission:inventory.purchase.manage');
        Route::get('/{purchase}/edit', [InventoryPageController::class, 'purchaseEdit'])->name('edit')
            ->middleware('permission:inventory.purchase.manage');
        Route::get('/{purchase}/details', [PurchasesController::class, 'show'])->name('details')
            ->middleware('permission:inventory.view|inventory.purchase.view');
        Route::get('/{purchase}', [PurchasesController::class, 'getPurchase'])->name('show')
            ->middleware('permission:inventory.view|inventory.purchase.view');
        Route::post('/', [PurchasesController::class, 'store'])->name('store')
            ->middleware('permission:inventory.purchase.manage');
        Route::put('/{purchase}', [PurchasesController::class, 'update'])->name('update')
            ->middleware('permission:inventory.purchase.manage');
        Route::delete('/{purchase}', [PurchasesController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.purchase.delete');
        Route::post('/cancel', [PurchasesController::class, 'cancel'])->name('cancel')
            ->middleware('permission:inventory.purchase.delete');
        Route::get('/analysis/{id}', [PurchasesController::class, 'getPurchaseAnalysis'])->name('analysis')
            ->middleware('permission:inventory.view|inventory.purchase.view|inventory.reports');
    });

    // ==================== PURCHASE RETURNS ====================
    Route::prefix('inventory/purchase-returns')->name('inventory.purchase-returns.')->group(function () {
        Route::get('/', [PurchaseReturnsController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.return.manage');
        Route::get('/all', [PurchaseReturnsController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.return.manage');
        Route::get('/reasons', [PurchaseReturnsController::class, 'getReasons'])->name('reasons')
            ->middleware('permission:inventory.return.manage');
        Route::get('/suppliers', [PurchaseReturnsController::class, 'getSuppliers'])->name('suppliers')
            ->middleware('permission:inventory.return.manage');
        Route::get('/purchases', [PurchaseReturnsController::class, 'getPurchases'])->name('purchases')
            ->middleware('permission:inventory.return.manage');
        Route::get('/create', [InventoryPageController::class, 'purchaseReturnCreate'])->name('create')
            ->middleware('permission:inventory.return.manage');
        Route::get('/{purchaseReturn}/edit', [InventoryPageController::class, 'purchaseReturnEdit'])->name('edit')
            ->middleware('permission:inventory.return.manage');
        Route::post('/', [PurchaseReturnsController::class, 'store'])->name('store')
            ->middleware('permission:inventory.return.manage');
        Route::get('/{purchaseReturn}', [PurchaseReturnsController::class, 'show'])->name('show')
            ->middleware('permission:inventory.view|inventory.return.manage');
        Route::put('/{purchaseReturn}', [PurchaseReturnsController::class, 'update'])->name('update')
            ->middleware('permission:inventory.return.manage');
        Route::delete('/{purchaseReturn}', [PurchaseReturnsController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.return.manage');
        Route::get('/purchase/{purchase}/items', [PurchaseReturnsController::class, 'getPurchaseItems'])->name('purchase-items')
            ->middleware('permission:inventory.return.manage');
    });

    // ==================== STUDENT INVENTORY ====================
    Route::prefix('inventory/student-inventory')->name('inventory.student-inventory.')->group(function () {
        Route::get('/', [StudentInventoriesController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/all', [StudentInventoriesController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/create', [StudentInventoriesController::class, 'create'])->name('create')
            ->middleware('permission:inventory.student.issue');
        Route::post('/assign', [StudentInventoriesController::class, 'assign'])->name('assign')
            ->middleware('permission:inventory.student.issue');
        Route::post('/return', [StudentInventoriesController::class, 'processReturn'])->name('return.process')
            ->middleware('permission:inventory.student.issue');
        Route::get('/students/with-inventory', [StudentInventoriesController::class, 'getStudentsWithInventory'])->name('students.with-inventory')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/students', [StudentInventoriesController::class, 'getStudents'])->name('students')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/items', [StudentInventoriesController::class, 'getInventoryItems'])->name('items')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/types', [StudentInventoriesController::class, 'getInventoryTypes'])->name('types')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/{studentInventory}/return', [StudentInventoriesController::class, 'createReturn'])->name('return.create')
            ->middleware('permission:inventory.student.issue');
        Route::post('/{studentInventory}/return', [StudentInventoriesController::class, 'return'])->name('return.store')
            ->middleware('permission:inventory.student.issue');
        Route::get('/return/{studentInventoryReturn}/view', [StudentInventoriesController::class, 'showReturn'])->name('return.view')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/{studentInventory}/check-return', [StudentInventoriesController::class, 'checkReturnAvailability'])->name('check-return')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/dashboard-summary', [StudentInventoriesController::class, 'getDashboardSummary'])->name('dashboard-summary')
            ->middleware('permission:inventory.view|inventory.student.issue');
        Route::get('/{studentInventory}/view', [StudentInventoriesController::class, 'show'])->name('show')
            ->middleware('permission:inventory.view|inventory.student.issue');
    });

    // ==================== INVENTORY RETURNS (Student to School) ====================
    Route::prefix('inventory/returns')->name('inventory.returns.')->group(function () {
        Route::get('/', [InventoryReturnsController::class, 'index'])->name('index')
            ->middleware('permission:inventory.view|inventory.return.manage');
        Route::get('/all', [InventoryReturnsController::class, 'getAll'])->name('all')
            ->middleware('permission:inventory.view|inventory.return.manage');
        Route::get('/create', [InventoryReturnsController::class, 'create'])->name('create')
            ->middleware('permission:inventory.return.manage');
        Route::post('/', [InventoryReturnsController::class, 'store'])->name('store')
            ->middleware('permission:inventory.return.manage');
        Route::get('/{return}', [InventoryReturnsController::class, 'show'])->name('show')
            ->middleware('permission:inventory.view|inventory.return.manage');
        Route::delete('/{return}', [InventoryReturnsController::class, 'destroy'])->name('destroy')
            ->middleware('permission:inventory.return.manage');
        Route::get('/analysis/{id}', [InventoryReturnsController::class, 'getReturnAnalysis'])->name('analysis')
            ->middleware('permission:inventory.view|inventory.return.manage|inventory.reports');
    });
});
