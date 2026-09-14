<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the owner/developer-only "Activity Log" entry under the footer
     * Settings menu, restricted with the `role` column (which now accepts a
     * comma-separated list) so it only ever renders for those two roles; the
     * controller behind it enforces the same restriction independently.
     */
    public function up(): void
    {
        if (Menu::query()->where('title', 'Activity Log')->where('type', 'footer')->exists()) {
            return;
        }

        $settings = Menu::query()
            ->where('title', 'Settings')
            ->where('type', 'footer')
            ->whereNull('parent_id')
            ->first();

        if (! $settings) {
            return;
        }

        $nextOrder = ((int) Menu::query()
            ->where('parent_id', $settings->id)
            ->where('type', 'footer')
            ->max('order')) + 1;

        Menu::create([
            'title' => 'Activity Log',
            'icon' => 'history',
            'type' => 'footer',
            'role' => 'owner,developer',
            'order' => $nextOrder,
            'parent_id' => $settings->id,
            'is_active' => true,
            'url' => '/settings/activity-log',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::query()->where('title', 'Activity Log')->where('type', 'footer')->where('role', 'owner,developer')->delete();
    }
};
