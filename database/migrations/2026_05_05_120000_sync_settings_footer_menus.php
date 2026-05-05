<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        $now = now();

        $settingsMenu = DB::table('menus')
            ->where('title', 'Settings')
            ->where('type', 'footer')
            ->whereNull('deleted_at')
            ->first();

        if (! $settingsMenu) {
            $settingsId = DB::table('menus')->insertGetId([
                'title' => 'Settings',
                'icon' => 'settings',
                'path' => '/settings',
                'url' => null,
                'is_active' => true,
                'parent_id' => null,
                'order' => 1,
                'type' => 'footer',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $settingsId = $settingsMenu->id;

            DB::table('menus')
                ->where('id', $settingsId)
                ->update([
                    'icon' => $settingsMenu->icon ?: 'settings',
                    'path' => '/settings',
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
        }

        $children = [
            [
                'title' => 'Profile',
                'icon' => 'user',
                'url' => '/settings/profile',
                'order' => 1,
            ],
            [
                'title' => 'Appearance',
                'icon' => 'palette',
                'url' => '/settings/appearance',
                'order' => 2,
            ],
            [
                'title' => 'School Profile',
                'icon' => 'building',
                'url' => '/settings/school-profile',
                'order' => 3,
            ],
            [
                'title' => 'Menu Settings',
                'icon' => 'cog',
                'url' => '/settings/menu-settings',
                'order' => 4,
            ],
        ];

        foreach ($children as $child) {
            $path = '/settings/'.Str::slug($child['title']);

            $existing = DB::table('menus')
                ->where('parent_id', $settingsId)
                ->where('title', $child['title'])
                ->first();

            $payload = [
                'icon' => $child['icon'],
                'path' => $path,
                'url' => $child['url'],
                'is_active' => true,
                'order' => $child['order'],
                'type' => 'footer',
                'parent_id' => $settingsId,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($existing) {
                DB::table('menus')
                    ->where('id', $existing->id)
                    ->update($payload);
            } else {
                DB::table('menus')->insert($payload + [
                    'title' => $child['title'],
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->whereIn('url', [
                '/settings/profile',
                '/settings/appearance',
                '/settings/school-profile',
                '/settings/menu-settings',
            ])
            ->where('type', 'footer')
            ->delete();
    }
};
