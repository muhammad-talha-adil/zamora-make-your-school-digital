<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Response;

class CacheController extends Controller
{
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

        return redirect()->route('cache.clear.index')
            ->with('success', 'All caches cleared successfully!');
    }

    /**
     * Clear only frontend caches (browser-related).
     */
    public function clearFrontend(Request $request): RedirectResponse
    {
        // Return a response that will force browser refresh
        return redirect('/')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->with('success', 'Frontend cache cleared! Please hard refresh your browser (Ctrl+F5).');
    }

    /**
     * Clear only backend caches.
     */
    public function clearBackend(Request $request): RedirectResponse
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
            'auth:clear-resets' => Artisan::call('auth:clear-resets'),
        ];

        foreach ($commands as $command => $result) {
            $results[$command] = $result === 0 ? 'Success' : 'Failed';
        }

        return redirect()->route('cache.clear.index')
            ->with('success', 'Backend caches cleared successfully!')
            ->with('cache-results', $results);
    }

    /**
     * Rebuild optimized caches.
     */
    public function rebuild(Request $request): RedirectResponse
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

        return redirect()->route('cache.clear.index')
            ->with('success', 'Caches rebuilt successfully!')
            ->with('cache-results', $results);
    }
}
