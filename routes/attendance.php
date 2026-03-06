<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Settings\AttendanceSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Attendance module.
| These routes are loaded by the RouteServiceProvider.
|
*/

Route::prefix('attendance')->name('attendance.')->group(function () {
    // Settings routes - MUST come before parameterized routes
    Route::get('/settings', [AttendanceSettingsController::class, 'show'])->name('settings');
    Route::get('/settings/leave-types', [AttendanceSettingsController::class, 'indexLeaveTypes'])->name('settings.leave-types.index');
    Route::post('/settings/leave-types', [AttendanceSettingsController::class, 'storeLeaveType'])->name('settings.leave-types.store');
    Route::put('/settings/leave-types/{leaveType}', [AttendanceSettingsController::class, 'updateLeaveType'])->name('settings.leave-types.update');
    Route::delete('/settings/leave-types/{leaveType}', [AttendanceSettingsController::class, 'destroyLeaveType'])->name('settings.leave-types.destroy');
    Route::get('/settings/holidays', [AttendanceSettingsController::class, 'indexHolidays'])->name('settings.holidays.index');
    Route::post('/settings/holidays', [AttendanceSettingsController::class, 'storeHoliday'])->name('settings.holidays.store');
    Route::put('/settings/holidays/{holiday}', [AttendanceSettingsController::class, 'updateHoliday'])->name('settings.holidays.update');
    Route::delete('/settings/holidays/{holiday}', [AttendanceSettingsController::class, 'destroyHoliday'])->name('settings.holidays.destroy');
    Route::get('/settings/past-holidays', [AttendanceSettingsController::class, 'indexPastHolidays'])->name('settings.holidays.past');

    // Main routes
    Route::get('/', [AttendanceController::class, 'index'])->name('index');
    Route::get('/create', [AttendanceController::class, 'create'])->name('create');
    Route::post('/', [AttendanceController::class, 'storeBulk'])->name('store');
    Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
    Route::get('/{attendance}/edit', [AttendanceController::class, 'edit'])->name('edit');
    Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
    Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');

    // Lock/Unlock attendance
    Route::post('/{attendance}/lock', [AttendanceController::class, 'lock'])->name('lock');
    Route::post('/{attendance}/unlock', [AttendanceController::class, 'unlock'])->name('unlock');

    // Student report
    Route::get('/student/{student}/report', [AttendanceController::class, 'studentReport'])->name('student-report');

    // Class report
    Route::get('/class/report', [AttendanceController::class, 'classReport'])->name('class-report');

    // API routes
    Route::get('/api/students', [AttendanceController::class, 'getStudentsByClassSection'])->name('api.students');
    Route::get('/api/check-holiday', [AttendanceController::class, 'checkHoliday'])->name('api.check-holiday');
});
