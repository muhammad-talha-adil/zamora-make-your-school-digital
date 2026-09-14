<?php

use App\Http\Controllers\Portal\PortalAttendanceController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\PortalExamController;
use App\Http\Controllers\Portal\PortalFeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student / Guardian Portal Routes
|--------------------------------------------------------------------------
|
| `portal.student.access` was seeded on the `student`/`guardian` roles from
| the start and never checked anywhere — a dead placeholder for a gate that
| did not exist yet. This file is that gate: every route here reads only the
| signed-in family's own records, resolved server-side through
| `ResolvesOwnStudent`, never by a route-supplied student id.
|
*/

Route::prefix('portal')->name('portal.')->middleware(['web', 'auth', 'permission:portal.student.access'])->group(function () {
    Route::get('/', [PortalController::class, 'index'])->name('index');

    Route::prefix('fees')->name('fees.')->group(function () {
        Route::get('/', [PortalFeeController::class, 'index'])->name('index');
        Route::get('/{voucher}', [PortalFeeController::class, 'show'])->name('show');
    });

    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [PortalExamController::class, 'index'])->name('index');
        Route::get('/{resultHeader}', [PortalExamController::class, 'show'])->name('show');
    });

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [PortalAttendanceController::class, 'index'])->name('index');
    });
});
