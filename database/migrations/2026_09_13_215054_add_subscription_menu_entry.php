<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the developer-only "Subscription" entry to the main menu. It is
     * restricted with the new `role` column, so it only ever renders in the
     * sidebar for a `developer`-role user; the controller behind it enforces
     * the same restriction independently.
     */
    public function up(): void
    {
        if (Menu::query()->where('title', 'Subscription')->where('type', 'main')->exists()) {
            return;
        }

        $nextOrder = ((int) Menu::query()
            ->whereNull('parent_id')
            ->where('type', 'main')
            ->max('order')) + 1;

        Menu::create([
            'title' => 'Subscription',
            'icon' => 'key-round',
            'type' => 'main',
            'role' => 'developer',
            'order' => $nextOrder,
            'is_active' => true,
            'url' => '/settings/subscription',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::query()->where('title', 'Subscription')->where('type', 'main')->where('role', 'developer')->delete();
    }
};
