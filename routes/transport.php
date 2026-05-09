<?php

use App\Http\Controllers\TransportController;
use Illuminate\Support\Facades\Route;

$middleware = ['web', 'auth'];

Route::prefix('transport')->name('transport.')->middleware($middleware)->group(function () {
    Route::get('/', [TransportController::class, 'index'])->name('index');

    Route::post('/vehicles', [TransportController::class, 'storeVehicle'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [TransportController::class, 'updateVehicle'])->name('vehicles.update');

    Route::post('/stops', [TransportController::class, 'storeStop'])->name('stops.store');
    Route::put('/stops/{stop}', [TransportController::class, 'updateStop'])->name('stops.update');

    Route::post('/routes', [TransportController::class, 'storeRoute'])->name('routes.store');
    Route::put('/routes/{route}', [TransportController::class, 'updateRoute'])->name('routes.update');

    Route::post('/assignments', [TransportController::class, 'storeAssignment'])->name('assignments.store');
    Route::put('/assignments/{assignment}', [TransportController::class, 'updateAssignment'])->name('assignments.update');

    Route::post('/expenses', [TransportController::class, 'storeExpense'])->name('expenses.store');
    Route::put('/expenses/{expense}', [TransportController::class, 'updateExpense'])->name('expenses.update');

    Route::post('/generate-dues', [TransportController::class, 'generateDues'])->name('generate-dues');
});
