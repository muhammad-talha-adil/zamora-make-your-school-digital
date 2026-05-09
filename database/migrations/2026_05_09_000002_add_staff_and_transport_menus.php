<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $staffMenu = Menu::query()
                ->whereNull('parent_id')
                ->where('type', 'main')
                ->where('title', 'Staff')
                ->first();

            if (! $staffMenu) {
                Menu::query()
                    ->whereNull('parent_id')
                    ->where('type', 'main')
                    ->where('order', '>=', 7)
                    ->increment('order');

                $staffMenu = Menu::create([
                    'title' => 'Staff',
                    'icon' => 'briefcase',
                    'type' => 'main',
                    'order' => 7,
                    'is_active' => true,
                ]);

                Menu::create([
                    'title' => 'Dashboard',
                    'icon' => 'layout-dashboard',
                    'type' => 'main',
                    'order' => 1,
                    'parent_id' => $staffMenu->id,
                    'is_active' => true,
                    'url' => '/staff',
                ]);
            }

            $transportMenu = Menu::query()
                ->whereNull('parent_id')
                ->where('type', 'main')
                ->where('title', 'Transport')
                ->first();

            if (! $transportMenu) {
                Menu::query()
                    ->whereNull('parent_id')
                    ->where('type', 'main')
                    ->where('order', '>=', 8)
                    ->increment('order');

                $transportMenu = Menu::create([
                    'title' => 'Transport',
                    'icon' => 'bus',
                    'type' => 'main',
                    'order' => 8,
                    'is_active' => true,
                ]);

                Menu::create([
                    'title' => 'Dashboard',
                    'icon' => 'layout-dashboard',
                    'type' => 'main',
                    'order' => 1,
                    'parent_id' => $transportMenu->id,
                    'is_active' => true,
                    'url' => '/transport',
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $transportMenu = Menu::query()
                ->whereNull('parent_id')
                ->where('type', 'main')
                ->where('title', 'Transport')
                ->first();

            if ($transportMenu) {
                Menu::query()->where('parent_id', $transportMenu->id)->delete();
                $transportMenu->delete();
            }

            $staffMenu = Menu::query()
                ->whereNull('parent_id')
                ->where('type', 'main')
                ->where('title', 'Staff')
                ->first();

            if ($staffMenu) {
                Menu::query()->where('parent_id', $staffMenu->id)->delete();
                $staffMenu->delete();
            }
        });
    }
};
