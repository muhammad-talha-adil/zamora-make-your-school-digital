<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use App\Models\School;
use App\Models\Subscription;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        $themes = ThemeSetting::all()->keyBy('mode')->map(function ($setting) {
            $setting->colors_json = is_array($setting->colors_json) ? $setting->colors_json : json_decode($setting->colors_json, true) ?? [];

            return $setting;
        });

        $school = School::first();
        $user = $request->user();

        // A null `role` means visible to everyone; otherwise the menu entry
        // only renders for one of a comma-separated list of roles - e.g. the
        // developer-only Subscription page, or the owner/developer-only
        // Activity Log page.
        $allMenus = Menu::active()->orderBy('type')->orderBy('order')->get()
            ->filter(fn (Menu $menu) => $menu->role === null || $user?->hasAnyRole(explode(',', $menu->role)))
            ->values();

        if ($this->isPortalOnlyViewer($user)) {
            $allMenus = $this->restrictToPortalMenus($allMenus);
        }
        $menuData = [
            'main' => $this->buildMenuTree($allMenus->where('type', 'main')->whereNull('parent_id'), $allMenus),
            'footer' => $this->buildMenuTree($allMenus->where('type', 'footer')->whereNull('parent_id'), $allMenus),
        ];

        return [
            ...parent::share($request),
            'name' => $school?->name ?? config('app.name'),
            'school' => $school,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'output' => fn () => $request->session()->get('output'),
                'results' => fn () => $request->session()->get('results'),
                'cacheCleared' => fn () => $request->session()->get('cache-cleared'),
                'cacheResults' => fn () => $request->session()->get('cache-results'),
            ],
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles.permissions') : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'themes' => $themes,
            'theme_mode' => $mode,
            'menus' => $menuData,
            'subscriptionWarning' => $this->buildSubscriptionWarning($user),
        ];
    }

    /**
     * Shown only to owner/campus_admin/super_admin, within 10 days of
     * whichever expiry currently governs access - never to students,
     * guardians, or staff.
     *
     * @return array{daysRemaining: int, status: string}|null
     */
    protected function buildSubscriptionWarning(?User $user): ?array
    {
        if (! $user || ! $user->hasAnyRole(['owner', 'campus_admin', 'super_admin'])) {
            return null;
        }

        $subscription = Subscription::current();
        $days = $subscription->daysUntilExpiry();

        if ($days === null || $days > 10) {
            return null;
        }

        return [
            'daysRemaining' => $days,
            'status' => $subscription->status,
        ];
    }

    /**
     * True only when every role the viewer holds is `student` and/or
     * `guardian` - never for a role combination that also carries an admin
     * role, so nobody's menu is narrowed by accident.
     */
    protected function isPortalOnlyViewer(?User $user): bool
    {
        if (! $user || ! $user->hasAnyRole(['student', 'guardian'])) {
            return false;
        }

        return $user->roles->pluck('name')->diff(['student', 'guardian'])->isEmpty();
    }

    /**
     * The existing admin sidebar was built assuming every menu row is
     * visible to whichever roles the `role` column allows, with no concept
     * of a denylist. Rather than retrofitting a `role` value onto every one
     * of the ~15+ existing admin menu rows (tedious, and one missed row
     * quietly leaks an admin page to a family account), a pure
     * student/guardian viewer instead gets a second, narrower pass here:
     * keep only menu items whose own `url` points into the portal or the
     * two footer settings pages every user needs (password/theme), plus
     * whichever parent container rows those items live under so the menu
     * tree does not orphan them.
     *
     * @param  Collection<int, Menu>  $menus
     * @return Collection<int, Menu>
     */
    protected function restrictToPortalMenus(Collection $menus): Collection
    {
        $allowedUrls = ['/settings/profile', '/settings/appearance'];

        $isAllowed = fn (Menu $menu): bool => $menu->url
            && (Str::startsWith($menu->url, '/portal') || in_array($menu->url, $allowedUrls, true));

        $visible = $menus->filter($isAllowed);

        // One climb is enough for this app's two-level (parent/child) menu
        // tree, but loop until nothing new is added so a deeper nesting
        // never silently orphans a visible child under an excluded parent.
        do {
            $parentIds = $visible->pluck('parent_id')->filter()->unique();
            $newParents = $menus->whereIn('id', $parentIds)->reject(fn (Menu $menu) => $visible->contains('id', $menu->id));
            $visible = $visible->merge($newParents);
        } while ($newParents->isNotEmpty());

        return $visible->values();
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
