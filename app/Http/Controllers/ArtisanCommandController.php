<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArtisanCommandController extends Controller
{
    /**
     * Display the artisan commands dashboard.
     */
    public function index()
    {
        // Get list of available seeders
        $seeders = $this->getAvailableSeeders();

        return inertia('ArtisanCommands', [
            'seeders' => $seeders,
        ]);
    }

    /**
     * Get list of available seeders.
     */
    private function getAvailableSeeders(): array
    {
        $seederPath = database_path('seeders');
        $seeders = [];

        if (is_dir($seederPath)) {
            $files = scandir($seederPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $seeders[] = pathinfo($file, PATHINFO_FILENAME);
                }
            }
        }

        return $seeders;
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request): RedirectResponse
    {
        $results = [];

        $commands = [
            'optimize:clear' => Artisan::call('optimize:clear'),
            'config:clear' => Artisan::call('config:clear'),
            'cache:clear' => Artisan::call('cache:clear'),
            'route:clear' => Artisan::call('route:clear'),
            'view:clear' => Artisan::call('view:clear'),
            'event:clear' => Artisan::call('event:clear'),
            'clear-compiled' => Artisan::call('clear-compiled'),
        ];

        foreach ($commands as $command => $result) {
            $results[$command] = $result === 0 ? 'Success' : 'Failed';
        }

        return redirect()->route('artisan.commands.index')
            ->with('success', 'Cache cleared successfully!')
            ->with('results', $results);
    }

    /**
     * Rebuild application cache.
     */
    public function rebuildCache(Request $request): RedirectResponse
    {
        $results = [];

        $commands = [
            'config:cache' => Artisan::call('config:cache'),
            'route:cache' => Artisan::call('route:cache'),
            'view:cache' => Artisan::call('view:cache'),
            'optimize' => Artisan::call('optimize'),
        ];

        foreach ($commands as $command => $result) {
            $results[$command] = $result === 0 ? 'Success' : 'Failed';
        }

        return redirect()->route('artisan.commands.index')
            ->with('success', 'Cache rebuilt successfully!')
            ->with('results', $results);
    }

    /**
     * Run migrations.
     */
    public function migrate(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Migrations completed successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Migration failed: '.$e->getMessage());
        }
    }

    /**
     * Run a single migration.
     */
    public function migrateSingle(Request $request): RedirectResponse
    {
        $migration = $request->get('migration');

        if (! $migration) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Migration name is required!');
        }

        try {
            $output = Artisan::call('migrate', ['--path' => 'database/migrations/'.$migration.'.php']);

            return redirect()->route('artisan.ui')
                ->with('success', 'Migration executed successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Migration failed: '.$e->getMessage());
        }
    }

    /**
     * Run migrations with force flag.
     */
    public function migrateForce(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate', ['--force' => true]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Migrations (force) completed successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Migration failed: '.$e->getMessage());
        }
    }

    /**
     * Drop all tables and re-run migrations.
     */
    public function migrateFresh(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate:fresh');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Migrate:fresh completed successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Migrate:fresh failed: '.$e->getMessage());
        }
    }

    /**
     * Drop all tables, re-run migrations with seed.
     */
    public function migrateFreshSeed(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate:fresh', ['--seed' => true]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Migrate:fresh --seed completed successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Migrate:fresh --seed failed: '.$e->getMessage());
        }
    }

    /**
     * Run the database seeder.
     */
    public function dbSeed(Request $request): RedirectResponse
    {
        $seeder = $request->get('seeder');

        try {
            if ($seeder) {
                $output = Artisan::call('db:seed', ['--class' => $seeder]);
            } else {
                $output = Artisan::call('db:seed');
            }

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Database seeded successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Seeding failed: '.$e->getMessage());
        }
    }

    /**
     * Run a specific seeder by name (URL parameter).
     * Example: /artisan/seed/DatabaseSeeder
     */
    public function runSeeder(Request $request, string $seederName): RedirectResponse
    {
        try {
            // Try to find the seeder class
            $seederClass = $this->findSeederClass($seederName);

            if ($seederClass) {
                $output = Artisan::call('db:seed', ['--class' => $seederClass]);

                return redirect()->route('artisan.commands.index')
                    ->with('success', "Seeder '{$seederName}' executed successfully!")
                    ->with('output', Artisan::output());
            } else {
                return redirect()->route('artisan.commands.index')
                    ->with('error', "Seeder '{$seederName}' not found!");
            }
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Seeding failed: '.$e->getMessage());
        }
    }

    /**
     * Find the seeder class by name.
     */
    private function findSeederClass(string $name): ?string
    {
        $seedersPath = database_path('seeders');

        if (! is_dir($seedersPath)) {
            return null;
        }

        $files = scandir($seedersPath);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = pathinfo($file, PATHINFO_FILENAME);
                if (strtolower($className) === strtolower($name)) {
                    return "Database\\Seeders\\{$className}";
                }
            }
        }

        return null;
    }

    /**
     * Rollback the last database migration.
     */
    public function migrateRollback(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate:rollback');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Migration rollback completed!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Rollback failed: '.$e->getMessage());
        }
    }

    /**
     * Reset all migrations.
     */
    public function migrateReset(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate:reset');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'All migrations reset!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Reset failed: '.$e->getMessage());
        }
    }

    /**
     * Show migration status.
     */
    public function migrateStatus(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('migrate:status');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Migration status retrieved!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Status check failed: '.$e->getMessage());
        }
    }

    /**
     * Create a new database table from a model.
     */
    public function makeMigration(Request $request): RedirectResponse
    {
        $name = $request->get('name');

        if (! $name) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Migration name is required!');
        }

        try {
            $output = Artisan::call('make:migration', ['name' => $name]);

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Migration created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Failed to create migration: '.$e->getMessage());
        }
    }

    /**
     * Create a new seeder.
     */
    public function makeSeeder(Request $request): RedirectResponse
    {
        $name = $request->get('name');

        if (! $name) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Seeder name is required!');
        }

        try {
            $output = Artisan::call('make:seeder', ['name' => $name]);

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Seeder created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Failed to create seeder: '.$e->getMessage());
        }
    }

    /**
     * Create a new controller.
     */
    public function makeController(Request $request): RedirectResponse
    {
        $name = $request->get('name');

        if (! $name) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Controller name is required!');
        }

        try {
            $output = Artisan::call('make:controller', ['name' => $name]);

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Controller created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Failed to create controller: '.$e->getMessage());
        }
    }

    /**
     * Create a new model.
     */
    public function makeModel(Request $request): RedirectResponse
    {
        $name = $request->get('name');

        if (! $name) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Model name is required!');
        }

        try {
            $output = Artisan::call('make:model', ['name' => $name]);

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Model created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Failed to create model: '.$e->getMessage());
        }
    }

    /**
     * Run queue worker.
     */
    public function queueWork(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('queue:work');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Queue work started!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Queue work failed: '.$e->getMessage());
        }
    }

    /**
     * Clear queue.
     */
    public function queueClear(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('queue:clear');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Queue cleared!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Queue clear failed: '.$e->getMessage());
        }
    }

    /**
     * Restart queue worker.
     */
    public function queueRestart(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('queue:restart');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Queue restarted!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Queue restart failed: '.$e->getMessage());
        }
    }

    /**
     * List all routes.
     */
    public function routeList(Request $request): RedirectResponse
    {
        try {
            $output = Artisan::call('route:list');

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Routes listed!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Route list failed: '.$e->getMessage());
        }
    }

    /**
     * Publish vendor packages.
     */
    public function vendorPublish(Request $request): RedirectResponse
    {
        $tag = $request->get('tag', 'all');

        try {
            $output = Artisan::call('vendor:publish', ['--tag' => $tag]);

            return redirect()->route('artisan.commands.index')
                ->with('success', 'Vendor published!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', 'Vendor publish failed: '.$e->getMessage());
        }
    }

    /**
     * Run any custom artisan command.
     */
    public function runCommand(Request $request, string $command): RedirectResponse
    {
        try {
            $output = Artisan::call($command);

            return redirect()->route('artisan.commands.index')
                ->with('success', "Command '{$command}' executed!")
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.commands.index')
                ->with('error', "Command '{$command}' failed: ".$e->getMessage());
        }
    }
}
