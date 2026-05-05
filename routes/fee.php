<?php

use App\Http\Controllers\Fee\DiscountTypeController;
use App\Http\Controllers\Fee\FeeDashboardController;
use App\Http\Controllers\Fee\FeeHeadController;
use App\Http\Controllers\Fee\FeePaymentController;
use App\Http\Controllers\Fee\FeeReportController;
use App\Http\Controllers\Fee\FeeSettingsController;
use App\Http\Controllers\Fee\FeeStructureController;
use App\Http\Controllers\Fee\FeeStructureItemController;
use App\Http\Controllers\Fee\FeeVoucherController;
use App\Http\Controllers\Fee\FineRuleController;
use App\Models\Fee\FeeStructure;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fee Module Routes
|--------------------------------------------------------------------------
|
| Complete fee management system routes
|
*/

$middleware = ['web', 'auth'];

Route::prefix('fee')->name('fee.')->middleware($middleware)->group(function () {

    // ==================== FEE HEADS ====================
    Route::prefix('heads')->name('heads.')->group(function () {
        Route::get('/', [FeeHeadController::class, 'index'])->name('index');
        Route::get('/create', [FeeHeadController::class, 'create'])->name('create');
        Route::post('/', [FeeHeadController::class, 'store'])->name('store');
        Route::get('/{feeHead}/edit', [FeeHeadController::class, 'edit'])->name('edit');
        Route::put('/{feeHead}', [FeeHeadController::class, 'update'])->name('update');
        Route::post('/{feeHead}/toggle-active', [FeeHeadController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/{feeHead}', [FeeHeadController::class, 'destroy'])->name('destroy');

        // API endpoints
        Route::get('/all', [FeeHeadController::class, 'getAll'])->name('all');
    });

    // ==================== DISCOUNT TYPES ====================
    Route::prefix('discount-types')->name('discount-types.')->group(function () {
        Route::get('/', [DiscountTypeController::class, 'index'])->name('index');
        Route::get('/create', [DiscountTypeController::class, 'create'])->name('create');
        Route::post('/', [DiscountTypeController::class, 'store'])->name('store');
        Route::get('/{discountType}/edit', [DiscountTypeController::class, 'edit'])->name('edit');
        Route::put('/{discountType}', [DiscountTypeController::class, 'update'])->name('update');
        Route::delete('/{discountType}', [DiscountTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{discountType}/toggle-active', [DiscountTypeController::class, 'toggleActive'])->name('toggle-active');

        // API endpoints
        Route::get('/all', [DiscountTypeController::class, 'getAll'])->name('all');
    });

    // ==================== FEE STRUCTURES ====================
    Route::prefix('structures')->name('structures.')->group(function () {
        Route::get('/', [FeeStructureController::class, 'index'])->name('index');
        Route::get('/create', [FeeStructureController::class, 'create'])->name('create');
        Route::post('/', [FeeStructureController::class, 'store'])->name('store');
        Route::get('/{feeStructure}', [FeeStructureController::class, 'show'])->name('show');
        Route::get('/{feeStructure}/debug', function (FeeStructure $feeStructure) {
            $feeStructure->load(['session', 'campus', 'class', 'section', 'items.feeHead', 'creator']);

            return response()->json($feeStructure);
        })->name('debug');
        Route::get('/{feeStructure}/edit', [FeeStructureController::class, 'edit'])->name('edit');
        Route::put('/{feeStructure}', [FeeStructureController::class, 'update'])->name('update');
        Route::delete('/{feeStructure}', [FeeStructureController::class, 'destroy'])->name('destroy');

        // Status management
        Route::patch('/{feeStructure}/activate', [FeeStructureController::class, 'activate'])->name('activate');
        Route::patch('/{feeStructure}/deactivate', [FeeStructureController::class, 'deactivate'])->name('deactivate');
        Route::patch('/{feeStructure}/set-default', [FeeStructureController::class, 'setDefault'])->name('set-default');

        // API endpoints
        Route::get('/by-scope/get', [FeeStructureController::class, 'getByScope'])->name('by-scope');
        Route::get('/search-titles', [FeeStructureController::class, 'searchTitles'])->name('search-titles');

        // Fee Structure Items (add/edit/remove fee items from structure)
        Route::post('/{feeStructure}/items', [FeeStructureItemController::class, 'store'])->name('items.store');
        Route::put('/{feeStructure}/items/{item}', [FeeStructureItemController::class, 'update'])->name('items.update');
        Route::delete('/{feeStructure}/items/{item}', [FeeStructureItemController::class, 'destroy'])->name('items.destroy');
        Route::get('/{feeStructure}/items/available-fee-heads', [FeeStructureItemController::class, 'getAvailableFeeHeads'])->name('items.available-fee-heads');
    });

    // ==================== FEE VOUCHERS ====================
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        // API endpoint for fetching vouchers (returns JSON)
        Route::get('/list', [FeeVoucherController::class, 'getVouchersList'])->name('list');

        Route::get('/', [FeeVoucherController::class, 'index'])->name('index');
        Route::get('/create', [FeeVoucherController::class, 'create'])->name('create');

        // Generation - must be defined before {voucher} route
        Route::get('/generate', [FeeVoucherController::class, 'generateForm'])->name('generate.form');
        Route::post('/generate', [FeeVoucherController::class, 'generate'])->name('generate.post');
        Route::post('/generate-bulk', [FeeVoucherController::class, 'generateBulk'])->name('generate-bulk');

        // {voucher} route must be after specific routes
        Route::get('/{voucher}', [FeeVoucherController::class, 'show'])->name('show');
        Route::get('/{voucher}/edit', [FeeVoucherController::class, 'edit'])->name('edit');
        Route::put('/{voucher}', [FeeVoucherController::class, 'update'])->name('update');
        Route::delete('/{voucher}', [FeeVoucherController::class, 'destroy'])->name('destroy');

        // Voucher Item Management (add/edit/remove fee heads)
        Route::post('/{voucher}/items', [FeeVoucherController::class, 'addItem'])->name('add-item');
        Route::put('/{voucher}/items/{item}', [FeeVoucherController::class, 'updateItem'])->name('update-item');
        Route::delete('/{voucher}/items/{item}', [FeeVoucherController::class, 'removeItem'])->name('remove-item');

        // Status management
        Route::patch('/{voucher}/publish', [FeeVoucherController::class, 'publish'])->name('publish');
        Route::patch('/{voucher}/cancel', [FeeVoucherController::class, 'cancel'])->name('cancel');

        // Adjustments
        Route::post('/{voucher}/adjustments', [FeeVoucherController::class, 'addAdjustment'])->name('add-adjustment');

        // Print - must be defined before {voucher} route
        Route::get('/{voucher}/print', [FeeVoucherController::class, 'print'])->name('print');
        Route::get('/print-batch', [FeeVoucherController::class, 'printBatch'])->name('print-batch');
        Route::post('/{voucher}/log-print', [FeeVoucherController::class, 'logPrint'])->name('log-print');

        // API endpoints
        Route::get('/student/{student}', [FeeVoucherController::class, 'getByStudent'])->name('by-student');
        Route::get('/unpaid/list', [FeeVoucherController::class, 'getUnpaid'])->name('unpaid');
        Route::get('/overdue/list', [FeeVoucherController::class, 'getOverdue'])->name('overdue');
    });

    // Public print routes (no auth required - for opening in new windows)
    Route::prefix('print-voucher')->name('print-voucher.')->withoutMiddleware('auth')->group(function () {
        Route::get('/batch', [FeeVoucherController::class, 'printBatch'])->name('batch');
        Route::get('/{voucher}', [FeeVoucherController::class, 'print'])->name('single');
    });

    // ==================== FEE PAYMENTS ====================
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [FeePaymentController::class, 'index'])->name('index');
        Route::get('/create', [FeePaymentController::class, 'create'])->name('create');
        Route::post('/', [FeePaymentController::class, 'store'])->name('store');
        Route::get('/{payment}', [FeePaymentController::class, 'show'])->name('show');
        Route::delete('/{payment}', [FeePaymentController::class, 'destroy'])->name('destroy');

        // Receipt
        Route::get('/{payment}/receipt', [FeePaymentController::class, 'receipt'])->name('receipt');
        Route::get('/{payment}/print-receipt', [FeePaymentController::class, 'printReceipt'])->name('print-receipt');

        // Reversal
        Route::post('/{payment}/reverse', [FeePaymentController::class, 'reverse'])->name('reverse');

        // API endpoints
        Route::get('/student/{student}', [FeePaymentController::class, 'getByStudent'])->name('by-student');
        Route::get('/voucher/{voucher}', [FeePaymentController::class, 'getByVoucher'])->name('by-voucher');
    });

    // ==================== REPORTS ====================
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [FeeReportController::class, 'index'])->name('index');
        Route::get('/collection', [FeeReportController::class, 'collection'])->name('collection');
        Route::get('/outstanding', [FeeReportController::class, 'outstanding'])->name('outstanding');
        Route::get('/defaulters', [FeeReportController::class, 'defaulters'])->name('defaulters');
        Route::get('/payment-methods', [FeeReportController::class, 'paymentMethods'])->name('payment-methods');
    });

    // ==================== SETTINGS ====================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [FeeSettingsController::class, 'index'])->name('index');

        // Fee Heads
        Route::get('/fee-heads', [FeeHeadController::class, 'index'])->name('fee-heads');

        // Discount Types
        Route::get('/discount-types', [DiscountTypeController::class, 'index'])->name('discount-types');

        // Fine Rules
        Route::get('/fine-rules', [FineRuleController::class, 'index'])->name('fine-rules');
        Route::post('/fine-rules', [FineRuleController::class, 'store'])->name('fine-rules.store');
        Route::put('/fine-rules/{fineRule}', [FineRuleController::class, 'update'])->name('fine-rules.update');
        Route::delete('/fine-rules/{fineRule}', [FineRuleController::class, 'destroy'])->name('fine-rules.destroy');
        Route::patch('/fine-rules/{fineRule}/toggle-status', [FineRuleController::class, 'toggleStatus'])->name('fine-rules.toggle-status');
    });

    // ==================== DASHBOARD ====================
    Route::get('/dashboard', [FeeDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [FeeDashboardController::class, 'stats'])->name('dashboard.stats');

});
