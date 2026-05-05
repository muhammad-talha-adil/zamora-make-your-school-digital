<?php

use App\Http\Controllers\ArtisanCommandController;
use App\Http\Controllers\CacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Artisan Command Routes
|--------------------------------------------------------------------------
|
| These routes allow execution of various artisan commands via HTTP requests.
| WARNING: These routes should only be accessible in local/development environments!
| In production, these routes can be a serious security risk.
|
*/

// Only allow these routes in local environment
if (app()->environment('local')) {
    Route::prefix('artisan')->name('artisan.')->group(function () {

        // Dashboard - Show all available commands
        Route::get('/', [ArtisanCommandController::class, 'index'])->name('commands.index');

        // ============ Cache Commands (from web.php) ============
        Route::prefix('cache')->name('cache.')->group(function () {
            Route::get('/', [CacheController::class, 'index'])->name('clear.index');
            Route::post('/clear', [CacheController::class, 'clear'])->name('clear');
            Route::post('/clear/backend', [CacheController::class, 'clearBackend'])->name('clear.backend');
            Route::post('/clear/frontend', [CacheController::class, 'clearFrontend'])->name('clear.frontend');
            Route::post('/rebuild', [CacheController::class, 'rebuild'])->name('rebuild');
        });

        // ============ Additional Cache Commands ============
        Route::post('/cache/clear', [ArtisanCommandController::class, 'clearCache'])->name('cache.clear');
        Route::post('/cache/rebuild', [ArtisanCommandController::class, 'rebuildCache'])->name('cache.rebuild');

        // ============ Migration Commands ============
        Route::post('/migrate', [ArtisanCommandController::class, 'migrate'])->name('migrate');
        Route::post('/migrate/single', [ArtisanCommandController::class, 'migrateSingle'])->name('migrate.single');
        Route::post('/migrate/force', [ArtisanCommandController::class, 'migrateForce'])->name('migrate.force');
        Route::post('/migrate/fresh', [ArtisanCommandController::class, 'migrateFresh'])->name('migrate.fresh');
        Route::post('/migrate/fresh/seed', [ArtisanCommandController::class, 'migrateFreshSeed'])->name('migrate.fresh.seed');
        Route::post('/migrate/rollback', [ArtisanCommandController::class, 'migrateRollback'])->name('migrate.rollback');
        Route::post('/migrate/reset', [ArtisanCommandController::class, 'migrateReset'])->name('migrate.reset');
        Route::post('/migrate/status', [ArtisanCommandController::class, 'migrateStatus'])->name('migrate.status');

        // ============ Seeder Commands ============
        Route::post('/db/seed', [ArtisanCommandController::class, 'dbSeed'])->name('db.seed');

        // Run specific seeder by name in URL
        // Example: /artisan/seed/MenuSeeder
        Route::post('/seed/{seederName}', [ArtisanCommandController::class, 'runSeeder'])->name('seed.run');

        // ============ Make Commands ============
        Route::post('/make/migration', [ArtisanCommandController::class, 'makeMigration'])->name('make.migration');
        Route::post('/make/seeder', [ArtisanCommandController::class, 'makeSeeder'])->name('make.seeder');
        Route::post('/make/controller', [ArtisanCommandController::class, 'makeController'])->name('make.controller');
        Route::post('/make/model', [ArtisanCommandController::class, 'makeModel'])->name('make.model');

        // ============ Queue Commands ============
        Route::post('/queue/work', [ArtisanCommandController::class, 'queueWork'])->name('queue.work');
        Route::post('/queue/clear', [ArtisanCommandController::class, 'queueClear'])->name('queue.clear');
        Route::post('/queue/restart', [ArtisanCommandController::class, 'queueRestart'])->name('queue.restart');

        // ============ Route Commands ============
        Route::post('/routes', [ArtisanCommandController::class, 'routeList'])->name('routes.list');

        // ============ Vendor Commands ============
        Route::post('/vendor/publish', [ArtisanCommandController::class, 'vendorPublish'])->name('vendor.publish');

        // ============ Custom Command ============
        Route::post('/run/{command}', [ArtisanCommandController::class, 'runCommand'])->name('run.command');
    });
}
