<?php

use Illuminate\Support\Facades\DB;
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

// Include Artisan Commands Routes (Local Environment Only)
require __DIR__.'/commands.php';

// Artisan Commands UI Page (Local Environment Only)
if (app()->environment('local')) {
    Route::get('/artisan', function () {
        // Get list of available seeders
        $seeders = [];
        $seederPath = database_path('seeders');
        if (is_dir($seederPath)) {
            $files = scandir($seederPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $seeders[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }

        // Get pending migrations (not yet migrated)
        $pendingMigrations = [];
        $migratedMigrations = [];

        try {
            // Get all migration files
            $migrationPath = database_path('migrations');
            $migrationFiles = [];

            if (is_dir($migrationPath)) {
                $files = scandir($migrationPath);
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                        $migrationFiles[] = pathinfo($file, PATHINFO_FILENAME);
                    }
                }
            }

            // Get already migrated migrations
            $migrated = DB::table('migrations')->pluck('migration')->toArray();

            // Find pending migrations
            foreach ($migrationFiles as $file) {
                if (! in_array($file, $migrated)) {
                    $pendingMigrations[] = $file;
                }
            }

            $pendingMigrations = array_values($pendingMigrations);
        } catch (Exception $e) {
            // Table might not exist yet
            $pendingMigrations = [];
        }

        return Inertia::render('ArtisanCommands', [
            'seeders' => $seeders,
            'pendingMigrations' => $pendingMigrations,
        ]);
    })->name('artisan.ui');
}
