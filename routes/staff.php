<?php

use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

$middleware = ['web', 'auth'];

Route::prefix('staff')->name('staff.')->middleware($middleware)->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('index');

    Route::post('/departments', [StaffController::class, 'storeDepartment'])->name('departments.store');
    Route::put('/departments/{department}', [StaffController::class, 'updateDepartment'])->name('departments.update');

    Route::post('/designations', [StaffController::class, 'storeDesignation'])->name('designations.store');
    Route::put('/designations/{designation}', [StaffController::class, 'updateDesignation'])->name('designations.update');

    Route::post('/members', [StaffController::class, 'storeStaff'])->name('members.store');
    Route::put('/members/{staffProfile}', [StaffController::class, 'updateStaff'])->name('members.update');
    Route::patch('/members/{staffProfile}/toggle', [StaffController::class, 'toggleStaff'])->name('members.toggle');

    Route::post('/payroll/generate', [StaffController::class, 'generatePayroll'])->name('payroll.generate');
    Route::post('/payroll/items/{payrollRunItem}/pay', [StaffController::class, 'payPayrollItem'])->name('payroll.items.pay');
});
