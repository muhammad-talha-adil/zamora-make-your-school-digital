<?php

namespace App\Http\Middleware;

use App\Models\ThemeSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'system';
        View::share('appearance', $appearance);

        // Determine mode for theme
        $mode = $appearance === 'system' ? 'light' : $appearance;

        // Both palettes are shared so the layout can emit the `:root` and
        // `.dark` blocks together. The in-page appearance toggle only adds or
        // removes the `dark` class, so the mode it switches to has to already
        // be present in the stylesheet.
        $themes = ThemeSetting::all()->keyBy('mode');

        View::share('theme', $themes->get($mode));
        View::share('themes', $themes);
        View::share('theme_mode', $mode);

        return $next($request);
    }
}
