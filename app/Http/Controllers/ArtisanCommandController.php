<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

class ArtisanCommandController extends Controller
{
    /**
     * Commands the free-text `runCommand` endpoint may execute.
     *
     * Deliberately excludes anything destructive (`migrate:fresh`,
     * `migrate:rollback`, `migrate:reset`, `db:wipe`, ...) — those are
     * blocked outright rather than allowed through an arbitrary command
     * string, since guessing intent on a data-dropping command is not
     * an acceptable risk from a web UI.
     *
     * @var list<string>
     */
    private const ALLOWED_FREEFORM_COMMANDS = [
        'optimize:clear',
        'config:clear',
        'cache:clear',
        'route:clear',
        'view:clear',
        'event:clear',
        'clear-compiled',
        'queue:restart',
        'queue:clear',
        'route:list',
        'migrate:status',
    ];

    /**
     * Record who ran what through this UI, for audit purposes.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function logCommandExecution(Request $request, string $command, array $parameters = []): void
    {
        Log::info('Artisan UI command executed', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'command' => $command,
            'parameters' => $parameters,
        ]);
    }

    /**
     * Display the artisan commands dashboard.
     */
    public function index(): Response
    {
        // Get list of available seeders
        $seeders = $this->getAvailableSeeders();
        $pendingMigrations = $this->getPendingMigrations();

        return inertia('ArtisanCommands', [
            'seeders' => $seeders,
            'pendingMigrations' => $pendingMigrations,
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

    private function getPendingMigrations(): array
    {
        $pendingMigrations = [];

        try {
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

            $migrated = DB::table('migrations')->pluck('migration')->toArray();

            foreach ($migrationFiles as $file) {
                if (! in_array($file, $migrated)) {
                    $pendingMigrations[] = $file;
                }
            }
        } catch (\Exception $e) {
            return [];
        }

        return array_values($pendingMigrations);
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'clearCache');

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

        return redirect()->route('artisan.ui')
            ->with('success', 'Cache cleared successfully!')
            ->with('results', $results);
    }

    /**
     * Rebuild application cache.
     */
    public function rebuildCache(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'rebuildCache');

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

        return redirect()->route('artisan.ui')
            ->with('success', 'Cache rebuilt successfully!')
            ->with('results', $results);
    }

    /**
     * Run migrations.
     */
    public function migrate(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'migrate');

        try {
            $output = Artisan::call('migrate');

            return redirect()->route('artisan.ui')
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

        $this->logCommandExecution($request, 'migrate:single', ['migration' => $migration]);

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
        $this->logCommandExecution($request, 'migrate:force');

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
     * `migrate:fresh` drops every table. It is blocked outright from this
     * UI, regardless of role — a developer debugging via a web dashboard
     * accidentally wiping the database is a real, unrecoverable risk, and
     * this command belongs on a terminal with an explicit human decision
     * behind it, not a single click.
     */
    public function migrateFresh(Request $request): RedirectResponse
    {
        Log::warning('Blocked attempt to run migrate:fresh via Artisan UI', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
        ]);

        abort(403, 'migrate:fresh drops all data and cannot be run from this UI.');
    }

    /**
     * See {@see migrateFresh()} — `migrate:fresh --seed` is just as
     * destructive and is blocked for the same reason.
     */
    public function migrateFreshSeed(Request $request): RedirectResponse
    {
        Log::warning('Blocked attempt to run migrate:fresh --seed via Artisan UI', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
        ]);

        abort(403, 'migrate:fresh drops all data and cannot be run from this UI.');
    }

    /**
     * Run the database seeder.
     */
    public function dbSeed(Request $request): RedirectResponse
    {
        $seeder = $request->get('seeder');

        $this->logCommandExecution($request, 'db:seed', ['seeder' => $seeder]);

        try {
            if ($seeder) {
                $output = Artisan::call('db:seed', ['--class' => $seeder]);
            } else {
                $output = Artisan::call('db:seed');
            }

            return redirect()->route('artisan.ui')
                ->with('success', 'Database seeded successfully!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Seeding failed: '.$e->getMessage());
        }
    }

    /**
     * Run a specific seeder by name (URL parameter).
     * Example: /artisan/seed/DatabaseSeeder
     */
    public function runSeeder(Request $request, string $seederName): RedirectResponse
    {
        $this->logCommandExecution($request, 'seed.run', ['seeder' => $seederName]);

        try {
            // Try to find the seeder class
            $seederClass = $this->findSeederClass($seederName);

            if ($seederClass) {
                $output = Artisan::call('db:seed', ['--class' => $seederClass]);

                return redirect()->route('artisan.ui')
                    ->with('success', "Seeder '{$seederName}' executed successfully!")
                    ->with('output', Artisan::output());
            } else {
                return redirect()->route('artisan.ui')
                    ->with('error', "Seeder '{$seederName}' not found!");
            }
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
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
        $this->logCommandExecution($request, 'migrate:rollback');

        try {
            $output = Artisan::call('migrate:rollback');

            return redirect()->route('artisan.ui')
                ->with('success', 'Migration rollback completed!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Rollback failed: '.$e->getMessage());
        }
    }

    /**
     * Reset all migrations.
     */
    public function migrateReset(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'migrate:reset');

        try {
            $output = Artisan::call('migrate:reset');

            return redirect()->route('artisan.ui')
                ->with('success', 'All migrations reset!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Reset failed: '.$e->getMessage());
        }
    }

    /**
     * Show migration status.
     */
    public function migrateStatus(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'migrate:status');

        try {
            $output = Artisan::call('migrate:status');

            return redirect()->route('artisan.ui')
                ->with('success', 'Migration status retrieved!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
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
            return redirect()->route('artisan.ui')
                ->with('error', 'Migration name is required!');
        }

        $this->logCommandExecution($request, 'make:migration', ['name' => $name]);

        try {
            $output = Artisan::call('make:migration', ['name' => $name]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Migration created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
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
            return redirect()->route('artisan.ui')
                ->with('error', 'Seeder name is required!');
        }

        $this->logCommandExecution($request, 'make:seeder', ['name' => $name]);

        try {
            $output = Artisan::call('make:seeder', ['name' => $name]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Seeder created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
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
            return redirect()->route('artisan.ui')
                ->with('error', 'Controller name is required!');
        }

        $this->logCommandExecution($request, 'make:controller', ['name' => $name]);

        try {
            $output = Artisan::call('make:controller', ['name' => $name]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Controller created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
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
            return redirect()->route('artisan.ui')
                ->with('error', 'Model name is required!');
        }

        $this->logCommandExecution($request, 'make:model', ['name' => $name]);

        try {
            $output = Artisan::call('make:model', ['name' => $name]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Model created!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Failed to create model: '.$e->getMessage());
        }
    }

    /**
     * Run queue worker.
     */
    public function queueWork(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'queue:work');

        try {
            $output = Artisan::call('queue:work');

            return redirect()->route('artisan.ui')
                ->with('success', 'Queue work started!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Queue work failed: '.$e->getMessage());
        }
    }

    /**
     * Clear queue.
     */
    public function queueClear(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'queue:clear');

        try {
            $output = Artisan::call('queue:clear');

            return redirect()->route('artisan.ui')
                ->with('success', 'Queue cleared!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Queue clear failed: '.$e->getMessage());
        }
    }

    /**
     * Restart queue worker.
     */
    public function queueRestart(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'queue:restart');

        try {
            $output = Artisan::call('queue:restart');

            return redirect()->route('artisan.ui')
                ->with('success', 'Queue restarted!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Queue restart failed: '.$e->getMessage());
        }
    }

    /**
     * List all routes.
     */
    public function routeList(Request $request): RedirectResponse
    {
        $this->logCommandExecution($request, 'route:list');

        try {
            $output = Artisan::call('route:list');

            return redirect()->route('artisan.ui')
                ->with('success', 'Routes listed!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Route list failed: '.$e->getMessage());
        }
    }

    /**
     * Publish vendor packages.
     */
    public function vendorPublish(Request $request): RedirectResponse
    {
        $tag = $request->get('tag', 'all');

        $this->logCommandExecution($request, 'vendor:publish', ['tag' => $tag]);

        try {
            $output = Artisan::call('vendor:publish', ['--tag' => $tag]);

            return redirect()->route('artisan.ui')
                ->with('success', 'Vendor published!')
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', 'Vendor publish failed: '.$e->getMessage());
        }
    }

    /**
     * Run any custom artisan command, restricted to an explicit allowlist.
     *
     * Free-text command execution is inherently dangerous; rather than try
     * to detect destructive commands, only the commands in
     * {@see ALLOWED_FREEFORM_COMMANDS} may be run this way.
     */
    public function runCommand(Request $request, string $command): RedirectResponse
    {
        if (! in_array($command, self::ALLOWED_FREEFORM_COMMANDS, true)) {
            Log::warning('Blocked attempt to run disallowed command via Artisan UI', [
                'user_id' => $request->user()?->id,
                'user_email' => $request->user()?->email,
                'command' => $command,
            ]);

            abort(403, "Command '{$command}' is not on the allowed command list.");
        }

        $this->logCommandExecution($request, $command);

        try {
            $output = Artisan::call($command);

            return redirect()->route('artisan.ui')
                ->with('success', "Command '{$command}' executed!")
                ->with('output', Artisan::output());
        } catch (\Exception $e) {
            return redirect()->route('artisan.ui')
                ->with('error', "Command '{$command}' failed: ".$e->getMessage());
        }
    }
}
