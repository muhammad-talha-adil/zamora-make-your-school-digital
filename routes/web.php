<?php

use App\Http\Controllers\CacheController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

// Authenticated Routes
$d = app()->environment('local') ? ['auth'] : ['auth', 'verified'];

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware($d)->name('dashboard');

// Inventory Dashboard Route
Route::get('/inventory', function () {
    return Inertia::render('inventory/Index');
})->middleware($d)->name('inventory.index');

// Cache Clear Routes - Available to all authenticated users (removed local environment restriction)
Route::prefix('cache')->name('cache.')->middleware($d)->group(function () {
    Route::get('/', [CacheController::class, 'index'])->name('clear.index');
    Route::post('/clear', [CacheController::class, 'clear'])->name('clear');
    Route::post('/clear/backend', [CacheController::class, 'clearBackend'])->name('clear.backend');
    Route::post('/clear/frontend', [CacheController::class, 'clearFrontend'])->name('clear.frontend');
    Route::post('/rebuild', [CacheController::class, 'rebuild'])->name('rebuild');
});

// Include Settings Routes
require __DIR__.'/settings.php';

// Include Student Routes
require __DIR__.'/students.php';

// Include Inventory Routes
require __DIR__.'/inventory.php';

// Include Fee Routes
require __DIR__.'/fee.php';

// Include Attendance Routes
require __DIR__.'/attendance.php';

// Include Exam Routes
require __DIR__.'/exam.php';
