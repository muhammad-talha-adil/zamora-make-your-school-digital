<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

class CacheController extends Controller
{
    /**
     * Record who ran what through this UI, for audit purposes.
     *
     * @param  list<string>  $commands
     */
    private function logCommandExecution(Request $request, array $commands): void
    {
        Log::info('Artisan UI cache command executed', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'commands' => $commands,
        ]);
    }

    /**
     * Show the cache clear confirmation page.
     */
    public function index(): Response
    {
        return inertia('CacheClear');
    }

    /**
     * Clear all caches (frontend and backend).
     */
    public function clear(Request $request): RedirectResponse
    {
        $results = [];

        $this->logCommandExecution($request, ['optimize:clear', 'config:clear', 'cache:clear', 'route:clear', 'view:clear', 'event:clear', 'clear-compiled', 'auth:clear-resets']);

        // Backend cache clearing commands
        $commands = [
            'optimize:clear' => Artisan::call('optimize:clear'),
            'config:clear' => Artisan::call('config:clear'),
            'cache:clear' => Artisan::call('cache:clear'),
            'route:clear' => Artisan::call('route:clear'),
            'view:clear' => Artisan::call('view:clear'),
            'event:clear' => Artisan::call('event:clear'),
            'clear-compiled' => Artisan::call('clear-compiled'),
            'auth:clear-resets' => Artisan::call('auth:clear-resets'),
        ];

        foreach ($commands as $command => $result) {
            $results[$command] = $result === 0 ? 'Success' : 'Failed';
        }

        // Set cache headers to force browser to refresh
        $request->session()->flash('cache-cleared', true);
        $request->session()->flash('cache-results', $results);

        return redirect()->route('artisan.cache.clear.index')
            ->with('success', 'All caches cleared successfully!');
    }

    /**
     * Clear only frontend caches (browser-related).
     */
    public function clearFrontend(Request $request): RedirectResponse
    {
        $request->session()->flash('cache-cleared', true);

        return redirect()->route('artisan.cache.clear.index')
            ->with('success', 'Frontend cache cleared! Please hard refresh your browser (Ctrl+F5).');
    }

    /**
     * Clear only backend caches.
     */
    public function clearBackend(Request $request): RedirectResponse
    {
        $results = [];

        $this->logCommandExecution($request, ['optimize:clear', 'config:clear', 'cache:clear', 'route:clear', 'view:clear', 'event:clear', 'clear-compiled', 'auth:clear-resets']);

        $commands = [
            'optimize:clear' => Artisan::call('optimize:clear'),
            'config:clear' => Artisan::call('config:clear'),
            'cache:clear' => Artisan::call('cache:clear'),
            'route:clear' => Artisan::call('route:clear'),
            'view:clear' => Artisan::call('view:clear'),
            'event:clear' => Artisan::call('event:clear'),
            'clear-compiled' => Artisan::call('clear-compiled'),
            'auth:clear-resets' => Artisan::call('auth:clear-resets'),
        ];

        foreach ($commands as $command => $result) {
            $results[$command] = $result === 0 ? 'Success' : 'Failed';
        }

        return redirect()->route('artisan.cache.clear.index')
            ->with('success', 'Backend caches cleared successfully!')
            ->with('cache-results', $results);
    }

    /**
     * Rebuild optimized caches.
     */
    public function rebuild(Request $request): RedirectResponse
    {
        $results = [];

        $this->logCommandExecution($request, ['config:cache', 'route:cache', 'view:cache', 'optimize']);

        $commands = [
            'config:cache' => Artisan::call('config:cache'),
            'route:cache' => Artisan::call('route:cache'),
            'view:cache' => Artisan::call('view:cache'),
            'optimize' => Artisan::call('optimize'),
        ];

        foreach ($commands as $command => $result) {
            $results[$command] = $result === 0 ? 'Success' : 'Failed';
        }

        return redirect()->route('artisan.cache.clear.index')
            ->with('success', 'Caches rebuilt successfully!')
            ->with('cache-results', $results);
    }
}
