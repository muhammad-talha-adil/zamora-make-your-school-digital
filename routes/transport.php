<?php

use App\Http\Controllers\TransportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Transport Module Routes
|--------------------------------------------------------------------------
|
| Every route below now carries a `permission:` check. Until this pass none
| of them did — the six `transport.*` permissions had been seeded from the
| start and not one was checked, so any signed-in account (a student's own
| portal login included, since students and staff share one `User` model and
| one guard) could create vehicles, reassign routes, or read every campus's
| fleet, stops and student assignments.
|
*/

$middleware = ['web', 'auth'];

Route::prefix('transport')->name('transport.')->middleware($middleware)->group(function () {
    Route::get('/', [TransportController::class, 'index'])->name('index')
        ->middleware('permission:transport.view|transport.view.own|transport.vehicle.manage|transport.route.manage|transport.assignment.manage|transport.expense.manage');

    Route::post('/vehicles', [TransportController::class, 'storeVehicle'])->name('vehicles.store')
        ->middleware('permission:transport.vehicle.manage');
    Route::put('/vehicles/{vehicle}', [TransportController::class, 'updateVehicle'])->name('vehicles.update')
        ->middleware('permission:transport.vehicle.manage');

    Route::post('/stops', [TransportController::class, 'storeStop'])->name('stops.store')
        ->middleware('permission:transport.route.manage');
    Route::put('/stops/{stop}', [TransportController::class, 'updateStop'])->name('stops.update')
        ->middleware('permission:transport.route.manage');

    Route::post('/routes', [TransportController::class, 'storeRoute'])->name('routes.store')
        ->middleware('permission:transport.route.manage');
    Route::put('/routes/{route}', [TransportController::class, 'updateRoute'])->name('routes.update')
        ->middleware('permission:transport.route.manage');

    Route::post('/assignments', [TransportController::class, 'storeAssignment'])->name('assignments.store')
        ->middleware('permission:transport.assignment.manage');
    Route::put('/assignments/{assignment}', [TransportController::class, 'updateAssignment'])->name('assignments.update')
        ->middleware('permission:transport.assignment.manage');

    Route::post('/expenses', [TransportController::class, 'storeExpense'])->name('expenses.store')
        ->middleware('permission:transport.expense.manage');
    Route::put('/expenses/{expense}', [TransportController::class, 'updateExpense'])->name('expenses.update')
        ->middleware('permission:transport.expense.manage');

    Route::post('/generate-dues', [TransportController::class, 'generateDues'])->name('generate-dues')
        ->middleware('permission:transport.assignment.manage');
});
