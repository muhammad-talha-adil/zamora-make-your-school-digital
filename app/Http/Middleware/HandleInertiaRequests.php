<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $mode = $this->getMode($request);
        $themes = \App\Models\ThemeSetting::all()->keyBy('mode')->map(function ($setting) {
            $setting->colors_json = is_array($setting->colors_json) ? $setting->colors_json : json_decode($setting->colors_json, true) ?? [];
            return $setting;
        });

        $school = \App\Models\School::first();

        $allMenus = Menu::active()->orderBy('type')->orderBy('order')->get();
        $menuData = [
            'main' => $this->buildMenuTree($allMenus->where('type', 'main')->whereNull('parent_id'), $allMenus),
            'footer' => $this->buildMenuTree($allMenus->where('type', 'footer')->whereNull('parent_id'), $allMenus),
        ];

        return [
            ...parent::share($request),
            'name' => $school?->name ?? config('app.name'),
            'school' => $school,
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles.permissions') : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'themes' => $themes,
            'theme_mode' => $mode,
            'menus' => $menuData,
        ];
    }

    protected function getMode(Request $request): string
    {
        $appearance = $request->cookie('appearance') ?? 'system';

        if ($appearance === 'system') {
            // For SSR, default to light; frontend will adjust
            return 'light';
        }

        return $appearance;
    }

    protected function buildMenuTree($parentMenus, $allMenus)
    {
        return $parentMenus->map(function ($menu) use ($allMenus) {
            $children = $allMenus->where('parent_id', $menu->id);
            return [
                'id' => $menu->id,
                'title' => $menu->title,
                'icon' => $menu->icon,
                'href' => $menu->href,
                'children' => $this->buildMenuTree($children, $allMenus),
            ];
        })->values()->toArray();
    }
}
