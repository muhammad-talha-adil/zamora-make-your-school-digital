<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/about', [PageController::class, 'about'])->name('about');

Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// Authenticated Routes
$d = app()->environment('local') ? ['auth'] : ['auth', 'verified'];

Route::get('dashboard', [PageController::class, 'dashboard'])->middleware($d)->name('dashboard');

// Inventory Dashboard Route
Route::get('/inventory', [PageController::class, 'inventoryIndex'])->middleware($d)->name('inventory.index');

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

// Include Finance Routes
require __DIR__.'/finance.php';

// Include Staff Routes
require __DIR__.'/staff.php';

// Include Transport Routes
require __DIR__.'/transport.php';

// Include Artisan Commands Routes (Local Environment Only)
require __DIR__.'/commands.php';
