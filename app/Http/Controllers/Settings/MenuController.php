<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreMenuRequest;
use App\Http\Requests\Settings\UpdateMenuRequest;
use App\Models\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Menu::with(['parent.parent']);

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

        $menus = $this->transformMenuPaginator(
            $query->forSettingsList()->paginate($perPage, ['*'], 'page', $page)
        );

        Log::info('MenuController index: Fetched '.$menus->count().' menus');

        $parentMenus = $this->getParentMenuOptions();
        Log::info('MenuController index: Fetched '.$parentMenus->count().' parent menus');

        return Inertia::render('settings/Menus/Index', [
            'tableMenus' => $menus,
            'parentMenus' => $parentMenus,
        ]);
    }

    /**
     * API: Display a listing of menus (for filtering).
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Menu::with(['parent.parent']);

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

        $menus = $this->transformMenuPaginator(
            $query->forSettingsList()->paginate($perPage)
        );

        return response()->json($menus);
    }

    public function create(Request $request): Response
    {
        $this->authorize('settings.manage');

        $parentMenus = $this->getParentMenuOptions();

        return Inertia::render('settings/Menus/Create', [
            'parentMenus' => $parentMenus,
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $this->authorize('settings.manage');

        // Ensure only users with 'developer' role can create menus
        if (! $request->user()->roles()->where('name', 'developer')->exists()) {
            abort(403, 'Only developers can create menus.');
        }

        $validated = $request->validated();

        $validated['is_active'] = $validated['is_active'] ?? 1;
        $validated['icon'] = $validated['icon'] ?? 'layout';
        $validated['url'] = $validated['url'] ?? Str::slug($validated['title']);
        $validated['parent_id'] = $validated['parent_id'] ?: null;
        $validated['order'] = $this->normalizeRequestedOrder(
            parentId: $validated['parent_id'],
            type: $validated['type'],
            requestedOrder: $validated['order'] ?? null,
        );

        $this->shiftSiblingOrdersForInsert(
            parentId: $validated['parent_id'],
            type: $validated['type'],
            order: $validated['order'],
        );

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
        $parentMenus = $this->getParentMenuOptions()
            ->filter(fn (array $menu) => $menu['id'] !== (int) $id)
            ->values();

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
        $validated['parent_id'] = $validated['parent_id'] ?: null;
        $this->applySiblingOrderUpdate($menu, $validated);

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
        $menu->update([
            'order' => $this->getNextSiblingOrder($menu->parent_id, $menu->type),
        ]);

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
        $this->closeSiblingOrderGap($menu->parent_id, $menu->type, $menu->order, $menu->id);
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

    private function transformMenuPaginator(LengthAwarePaginator $menus): LengthAwarePaginator
    {
        $menus->setCollection(
            $menus->getCollection()->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'title' => $menu->title,
                'type' => $menu->type,
                'order' => $menu->order,
                'is_active' => $menu->is_active,
                'parent_id' => $menu->parent_id,
                'parent_title' => $menu->parent?->title,
                'parent_hierarchy_label' => $menu->parent ? $this->buildMenuLabel($menu->parent) : null,
                'hierarchy_label' => $this->buildMenuLabel($menu),
                'deleted_at' => $menu->deleted_at,
            ])
        );

        return $menus;
    }

    private function getParentMenuOptions()
    {
        return Menu::query()
            ->with(['parent.parent'])
            ->whereNull('deleted_at')
            ->forSettingsList()
            ->get()
            ->map(fn (Menu $menu) => [
                'id' => $menu->id,
                'title' => $menu->title,
                'type' => $menu->type,
                'parent_id' => $menu->parent_id,
                'order' => $menu->order,
                'hierarchy_label' => $this->buildMenuLabel($menu),
            ])
            ->values();
    }

    private function buildMenuLabel(Menu $menu): string
    {
        $segments = [$menu->title];
        $parent = $menu->parent;

        while ($parent) {
            array_unshift($segments, $parent->title);
            $parent = $parent->parent;
        }

        return implode(' / ', $segments);
    }

    private function siblingQuery(?int $parentId, string $type, ?int $excludeId = null): Builder
    {
        $query = Menu::query()
            ->where('type', $type)
            ->when(
                $parentId,
                fn (Builder $builder) => $builder->where('parent_id', $parentId),
                fn (Builder $builder) => $builder->whereNull('parent_id')
            );

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    private function getNextSiblingOrder(?int $parentId, string $type, ?int $excludeId = null): int
    {
        return ((int) $this->siblingQuery($parentId, $type, $excludeId)->max('order')) + 1;
    }

    private function normalizeRequestedOrder(?int $parentId, string $type, mixed $requestedOrder, ?int $excludeId = null): int
    {
        $siblingsCount = $this->siblingQuery($parentId, $type, $excludeId)->count();
        $requested = (int) $requestedOrder;

        if ($requested <= 0) {
            return $siblingsCount + 1;
        }

        return min($requested, $siblingsCount + 1);
    }

    private function shiftSiblingOrdersForInsert(?int $parentId, string $type, int $order, ?int $excludeId = null): void
    {
        $this->siblingQuery($parentId, $type, $excludeId)
            ->where('order', '>=', $order)
            ->increment('order');
    }

    private function closeSiblingOrderGap(?int $parentId, string $type, int $order, ?int $excludeId = null): void
    {
        $this->siblingQuery($parentId, $type, $excludeId)
            ->where('order', '>', $order)
            ->decrement('order');
    }

    private function applySiblingOrderUpdate(Menu $menu, array &$validated): void
    {
        $oldParentId = $menu->parent_id;
        $oldType = $menu->type;
        $oldOrder = $menu->order;

        $newParentId = $validated['parent_id'];
        $newType = $validated['type'];
        $newOrder = $this->normalizeRequestedOrder(
            parentId: $newParentId,
            type: $newType,
            requestedOrder: $validated['order'] ?? null,
            excludeId: $menu->id,
        );

        $validated['order'] = $newOrder;

        if ($oldParentId === $newParentId && $oldType === $newType) {
            if ($newOrder < $oldOrder) {
                $this->siblingQuery($oldParentId, $oldType, $menu->id)
                    ->whereBetween('order', [$newOrder, $oldOrder - 1])
                    ->increment('order');
            } elseif ($newOrder > $oldOrder) {
                $this->siblingQuery($oldParentId, $oldType, $menu->id)
                    ->whereBetween('order', [$oldOrder + 1, $newOrder])
                    ->decrement('order');
            }

            return;
        }

        $this->closeSiblingOrderGap($oldParentId, $oldType, $oldOrder, $menu->id);
        $this->shiftSiblingOrdersForInsert($newParentId, $newType, $newOrder, $menu->id);
    }
}
