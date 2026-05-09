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

Route::middleware(['auth', 'verified'])->group(function () {
    // ==================== INVENTORY DASHBOARD ====================
    Route::get('/inventory/dashboard-data', [InventoryItemsController::class, 'getDashboardData'])->name('inventory.dashboard-data');

    // ==================== INVENTORY SETTINGS ====================
    // Note: Uses global scope which filters by is_active=true and excludes soft-deleted records
    Route::get('/inventory/settings', [InventoryPageController::class, 'settings'])->name('inventory.settings');

    // ==================== NEW CONSOLIDATED PAGES ====================
    // Items & Stock - Tabs: Types | Items | Stocks | Adjustments
    Route::get('/inventory/items-stock', [InventoryPageController::class, 'itemsStock'])->name('inventory.items-stock');

    // Purchases - Tabs: Purchases | Suppliers | Purchase Returns
    Route::get('/inventory/purchases-manage', [InventoryPageController::class, 'purchasesManage'])->name('inventory.purchases-manage');

    // View Purchase Details
    Route::get('/inventory/purchases/{id}/view', [InventoryPageController::class, 'purchaseView'])->name('inventory.purchases.view');

    // Student Inventory - Tabs: Assigned Items | Returns
    Route::get('/inventory/student-manage', [InventoryPageController::class, 'studentManage'])->name('inventory.student-manage');

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
        Route::get('/create', [InventoryPageController::class, 'purchaseCreate'])->name('create');
        Route::get('/{purchase}/edit', [InventoryPageController::class, 'purchaseEdit'])->name('edit');
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
        Route::get('/create', [InventoryPageController::class, 'purchaseReturnCreate'])->name('create');
        Route::get('/{purchaseReturn}/edit', [InventoryPageController::class, 'purchaseReturnEdit'])->name('edit');
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
