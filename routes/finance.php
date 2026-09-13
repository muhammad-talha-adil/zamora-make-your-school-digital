<?php

use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\MakePaymentController;
use App\Http\Controllers\Finance\PaymentMethodController;
use App\Http\Controllers\Finance\ReceivePaymentController;
use App\Http\Controllers\Finance\ReportController;
use App\Http\Controllers\Finance\StudentAccountStatementController;
use App\Http\Controllers\Finance\TransactionController;
use Illuminate\Support\Facades\Route;

/*
| Every route below now carries a `permission:` check — the `finance.*`
| permissions had been seeded and never once checked, same gap as `fee.php`.
*/

$middleware = ['web', 'auth'];

Route::prefix('finance')->name('finance.')->middleware($middleware)->group(function () {

    // Dashboard
    Route::get('/', [FinanceController::class, 'index'])->name('dashboard')
        ->middleware('permission:finance.view');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index')
        ->middleware('permission:finance.transaction.view');
    Route::get('/transactions/{ledger}', [TransactionController::class, 'show'])->name('transactions.show')
        ->middleware('permission:finance.transaction.view');

    // Student Account Statement
    Route::get('/student-account-statement', [StudentAccountStatementController::class, 'index'])->name('student-account-statement.index')
        ->middleware('permission:finance.view');
    Route::get('/student-account-statement/search-students', [StudentAccountStatementController::class, 'searchStudents'])->name('student-account-statement.search-students')
        ->middleware('permission:finance.view');

    // Receive Payment (Income)
    Route::get('/receive-payment', [ReceivePaymentController::class, 'create'])->name('receive.create')
        ->middleware('permission:finance.transaction.manage');
    Route::post('/receive-payment', [ReceivePaymentController::class, 'store'])->name('receive.store')
        ->middleware('permission:finance.transaction.manage');

    // API endpoints for Receive Payment (AJAX)
    Route::get('/receive-payment/classes', [ReceivePaymentController::class, 'getClasses'])->name('receive.get-classes')
        ->middleware('permission:finance.transaction.manage');
    Route::get('/receive-payment/sections', [ReceivePaymentController::class, 'getSections'])->name('receive.get-sections')
        ->middleware('permission:finance.transaction.manage');
    Route::get('/receive-payment/students', [ReceivePaymentController::class, 'getStudents'])->name('receive.get-students')
        ->middleware('permission:finance.transaction.manage');
    Route::get('/receive-payment/student-details', [ReceivePaymentController::class, 'getStudentDetails'])->name('receive.get-student-details')
        ->middleware('permission:finance.transaction.manage');

    // Make Payment (Expense)
    Route::get('/make-payment', [MakePaymentController::class, 'create'])->name('make.create')
        ->middleware('permission:finance.transaction.manage');
    Route::post('/make-payment', [MakePaymentController::class, 'store'])->name('make.store')
        ->middleware('permission:finance.transaction.manage');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index')
        ->middleware('permission:finance.ledger.manage');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store')
        ->middleware('permission:finance.ledger.manage');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update')
        ->middleware('permission:finance.ledger.manage');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')
        ->middleware('permission:finance.ledger.manage');

    // Payment Methods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index')
        ->middleware('permission:finance.ledger.manage');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store')
        ->middleware('permission:finance.ledger.manage');
    Route::put('/payment-methods/{method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update')
        ->middleware('permission:finance.ledger.manage');

    // Reports
    Route::get('/reports/cash-book', [ReportController::class, 'cashBook'])->name('reports.cash-book')
        ->middleware('permission:finance.reports');
    Route::get('/reports/income', [ReportController::class, 'income'])->name('reports.income')
        ->middleware('permission:finance.reports');
    Route::get('/reports/expense', [ReportController::class, 'expense'])->name('reports.expense')
        ->middleware('permission:finance.reports');
});
