<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreMenuRequest;
use App\Http\Requests\Settings\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Menu::with('parent');

        // Handle status filter
        $status = $request->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Handle trashed filter
        if ($request->get('trashed') === '1') {
            $query->onlyTrashed();
        }

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $menus = $query->ordered()
            ->paginate($perPage, ['*'], 'page', $page);

        \Log::info('MenuController index: Fetched ' . $menus->count() . ' menus');

        $parentMenus = Menu::active()->ordered()->get(['id', 'title']);
        \Log::info('MenuController index: Fetched ' . $parentMenus->count() . ' parent menus');

        return Inertia::render('settings/Menus/Index', [
            'tableMenus' => $menus,
            'parentMenus' => $parentMenus,
        ]);
    }

    /**
     * API: Display a listing of menus (for filtering).
     */
    public function apiIndex(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Menu::with('parent');

        // Handle status filter
        $status = $request->get('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Handle trashed filter
        if ($request->get('trashed') === '1') {
            $query->onlyTrashed();
        }

        $perPage = $request->get('per_page', 10);

        $menus = $query->ordered()
            ->paginate($perPage);

        return response()->json($menus);
    }

    public function create(Request $request): Response
    {
        $this->authorize('settings.manage');

        $parentMenus = Menu::active()->ordered()->get(['id', 'title']);

        return Inertia::render('settings/Menus/Create', [
            'parentMenus' => $parentMenus,
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        // Ensure only users with 'developer' role can create menus
        if (!$request->user()->roles()->where('name', 'developer')->exists()) {
            abort(403, 'Only developers can create menus.');
        }

        $validated = $request->validated();

        $validated['is_active'] = $validated['is_active'] ?? 1;
        $validated['icon'] = $validated['icon'] ?? 'layout';
        $validated['url'] = $validated['url'] ?? Str::slug($validated['title']);
        if ($validated['parent_id'] === 'self') {
            $validated['parent_id'] = null;
        }

        Menu::create($validated);

        return redirect()->route('menus.index')->with('success', 'Menu created successfully.');
    }

    public function show($id): Response
    {
        $this->authorize('settings.manage');

        $menu = Menu::with(['parent', 'children'])->findOrFail($id);

        return Inertia::render('settings/Menus/Show', [
            'menu' => $menu,
        ]);
    }

    public function edit($id): Response
    {
        $this->authorize('settings.manage');

        $menu = Menu::findOrFail($id);
        $parentMenus = Menu::where('id', '!=', $id)->active()->ordered()->get(['id', 'title']);

        return Inertia::render('settings/Menus/Edit', [
            'menu' => $menu,
            'parentMenus' => $parentMenus,
        ]);
    }

    public function update(UpdateMenuRequest $request, $id): RedirectResponse
    {
        $this->authorize('settings.manage');

        $menu = Menu::findOrFail($id);

        $validated = $request->validated();

        $menu->update($validated);

        return redirect()->route('menus.index')->with('success', 'Menu updated successfully.');
    }

    /**
     * Restore the specified resource from trash.
     */
    public function restore(int $id): RedirectResponse
    {
        $this->authorize('settings.manage');

        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->restore();

        return redirect()->route('menus.index')->with('success', 'Menu restored successfully.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $this->authorize('settings.manage');

        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->forceDelete();

        return redirect()->route('menus.index')->with('success', 'Menu permanently deleted.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy($id): RedirectResponse
    {
        $this->authorize('settings.manage');

        $menu = Menu::findOrFail($id);
        $menu->delete();

        return redirect()->route('menus.index')->with('success', 'Menu deleted successfully.');
    }

    /**
     * Inactivate the specified resource.
     */
    public function inactivate($id): RedirectResponse
    {
        $this->authorize('settings.manage');

        $menu = Menu::findOrFail($id);
        $menu->update(['is_active' => false]);

        return redirect()->route('menus.index')->with('success', 'Menu inactivated successfully.');
    }

    public function activate($id): RedirectResponse
    {
        $this->authorize('settings.manage');

        $menu = Menu::findOrFail($id);
        $menu->update(['is_active' => true]);

        return redirect()->route('menus.index')->with('success', 'Menu activated successfully.');
    }
}