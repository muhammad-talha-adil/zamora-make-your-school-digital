<?php

use App\Http\Controllers\Fee\FeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fee Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Fee module.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::prefix('fees')->name('fees.')->group(function () {
    // Fee Types Routes
    Route::prefix('types')->name('types.')->group(function () {
        Route::get('/', [FeeController::class, 'index'])->name('index');
        Route::get('/trashed', [FeeController::class, 'trashed'])->name('trashed');
        Route::get('/{id}', [FeeController::class, 'show'])->name('show');
        Route::post('/', [FeeController::class, 'store'])->name('store');
        Route::put('/{id}', [FeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [FeeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [FeeController::class, 'restore'])->name('restore');
        Route::post('/{id}/toggle-status', [FeeController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Fee Structures Routes
    Route::prefix('structures')->name('structures.')->group(function () {
        Route::get('/', [FeeController::class, 'feeStructureIndex'])->name('index');
        Route::get('/summary', [FeeController::class, 'feeStructureSummary'])->name('summary');
        Route::get('/{id}', [FeeController::class, 'feeStructureShow'])->name('show');
        Route::post('/', [FeeController::class, 'feeStructureStore'])->name('store');
        Route::post('/bulk', [FeeController::class, 'feeStructureBulkStore'])->name('bulk-store');
        Route::put('/{id}', [FeeController::class, 'feeStructureUpdate'])->name('update');
        Route::patch('/{id}/toggle-status', [FeeController::class, 'feeStructureToggleStatus'])->name('toggle-status');
        Route::delete('/{id}', [FeeController::class, 'feeStructureDestroy'])->name('destroy');
    });

    // Student Fees Routes
    Route::prefix('student-fees')->name('student-fees.')->group(function () {
        Route::get('/', [FeeController::class, 'studentFeeIndex'])->name('index');
        Route::get('/student/{student_id}', [FeeController::class, 'studentFeeByStudent'])->name('by-student');
        Route::get('/summary/{student_id}', [FeeController::class, 'studentFeeSummary'])->name('summary');
        Route::post('/assign', [FeeController::class, 'studentFeeAssign'])->name('assign');
        Route::post('/assign-all', [FeeController::class, 'studentFeeAssignAll'])->name('assign-all');
        Route::put('/{id}/status', [FeeController::class, 'studentFeeUpdateStatus'])->name('update-status');
        Route::delete('/{id}', [FeeController::class, 'studentFeeDestroy'])->name('destroy');
    });

    // Invoice Routes
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [FeeController::class, 'invoiceIndex'])->name('index');
        Route::get('/preview', [FeeController::class, 'invoicePreview'])->name('preview');
        Route::get('/{id}', [FeeController::class, 'invoiceShow'])->name('show');
        Route::post('/', [FeeController::class, 'invoiceStore'])->name('store');
        Route::post('/{id}/cancel', [FeeController::class, 'invoiceCancel'])->name('cancel');
    });

    // Payment Routes
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [FeeController::class, 'paymentIndex'])->name('index');
        Route::get('/invoice/{invoice_id}', [FeeController::class, 'paymentByInvoice'])->name('by-invoice');
        Route::get('/receipt/{id}', [FeeController::class, 'paymentReceipt'])->name('receipt');
        Route::post('/', [FeeController::class, 'paymentStore'])->name('store');
    });
});
