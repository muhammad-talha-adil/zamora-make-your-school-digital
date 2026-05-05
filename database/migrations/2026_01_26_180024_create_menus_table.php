<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Consolidated from:
     * - 2026_01_26_180024_create_menus_table.php
     * - 2026_01_26_195738_add_soft_deletes_to_menus_table.php
     * - 2026_02_05_100000_fix_inventory_menu_routes.php
     */
    public function up(): void
    {
        // Create menus table with all columns including softDeletes
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('path');
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->enum('type', ['main', 'footer'])->default('main');
            $table->timestamps();
            $table->softDeletes();
        });

        // Fix Dashboard submenu under Inventory
        $dashboardMenu = DB::table('menus')
            ->where('title', 'Dashboard')
            ->where('parent_id', function ($query) {
                $query->select('id')->from('menus')->where('title', 'Inventory')->limit(1);
            })
            ->first();

        if ($dashboardMenu) {
            DB::table('menus')
                ->where('id', $dashboardMenu->id)
                ->update(['url' => '/inventory']);
        }

        // Fix Stock Adjustments submenu under Inventory
        $stockAdjustmentsMenu = DB::table('menus')
            ->where('title', 'Stock Adjustments')
            ->where('parent_id', function ($query) {
                $query->select('id')->from('menus')->where('title', 'Inventory')->limit(1);
            })
            ->first();

        if ($stockAdjustmentsMenu) {
            DB::table('menus')
                ->where('id', $stockAdjustmentsMenu->id)
                ->update(['url' => '/inventory/adjustments']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
