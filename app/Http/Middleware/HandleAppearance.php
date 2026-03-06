<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'system';
        View::share('appearance', $appearance);

        // Determine mode for theme
        $mode = $appearance === 'system' ? 'light' : $appearance;
        $theme = \App\Models\ThemeSetting::where('mode', $mode)->first();
        View::share('theme', $theme);
        View::share('theme_mode', $mode);

        return $next($request);
    }
}
