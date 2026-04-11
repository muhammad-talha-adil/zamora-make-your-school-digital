<?php

use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\ReceivePaymentController;
use App\Http\Controllers\Finance\MakePaymentController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\PaymentMethodController;
use App\Http\Controllers\Finance\ReportController;
use Illuminate\Support\Facades\Route;

$middleware = ['web', 'auth'];

Route::prefix('finance')->name('finance.')->middleware($middleware)->group(function () {

    // Dashboard
    Route::get('/', [FinanceController::class, 'index'])->name('dashboard');
    
    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{ledger}', [TransactionController::class, 'show'])->name('transactions.show');
    
    // Receive Payment (Income)
    Route::get('/receive-payment', [ReceivePaymentController::class, 'create'])->name('receive.create');
    Route::post('/receive-payment', [ReceivePaymentController::class, 'store'])->name('receive.store');
    
    // API endpoints for Receive Payment (AJAX)
    Route::get('/receive-payment/classes', [ReceivePaymentController::class, 'getClasses'])->name('receive.get-classes');
    Route::get('/receive-payment/sections', [ReceivePaymentController::class, 'getSections'])->name('receive.get-sections');
    Route::get('/receive-payment/students', [ReceivePaymentController::class, 'getStudents'])->name('receive.get-students');
    Route::get('/receive-payment/student-details', [ReceivePaymentController::class, 'getStudentDetails'])->name('receive.get-student-details');
    
    // Make Payment (Expense)
    Route::get('/make-payment', [MakePaymentController::class, 'create'])->name('make.create');
    Route::post('/make-payment', [MakePaymentController::class, 'store'])->name('make.store');
    
    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Payment Methods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::put('/payment-methods/{method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
    
    // Reports
    Route::get('/reports/cash-book', [ReportController::class, 'cashBook'])->name('reports.cash-book');
    Route::get('/reports/income', [ReportController::class, 'income'])->name('reports.income');
    Route::get('/reports/expense', [ReportController::class, 'expense'])->name('reports.expense');
});
