<?php

use App\Http\Controllers\Timetable\TimetableController;
use App\Http\Controllers\Timetable\TimetableEntryController;
use App\Http\Controllers\Timetable\TimetablePeriodController;
use Illuminate\Support\Facades\Route;

Route::prefix('timetable')->name('timetable.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [TimetableController::class, 'index'])->name('index')
        ->middleware('permission:academics.timetable.view|academics.timetable.manage');
    Route::get('/my', [TimetableController::class, 'mine'])->name('mine')
        ->middleware('permission:academics.timetable.view|academics.timetable.manage');

    Route::middleware('permission:academics.timetable.manage')->group(function () {
        Route::get('/periods', [TimetablePeriodController::class, 'index'])->name('periods.index');
        Route::post('/periods', [TimetablePeriodController::class, 'store'])->name('periods.store');
        Route::put('/periods/{period}', [TimetablePeriodController::class, 'update'])->name('periods.update');
        Route::delete('/periods/{period}', [TimetablePeriodController::class, 'destroy'])->name('periods.destroy');

        Route::post('/entries', [TimetableEntryController::class, 'store'])->name('entries.store');
        Route::put('/entries/{entry}', [TimetableEntryController::class, 'update'])->name('entries.update');
        Route::delete('/entries/{entry}', [TimetableEntryController::class, 'destroy'])->name('entries.destroy');
    });
});
