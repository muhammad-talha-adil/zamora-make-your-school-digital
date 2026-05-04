<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds purchase_return_id column to purchase_returns table.
     * This is similar to purchase_id in inventory_purchases table.
     */
     public function up(): void
    {
        if (!Schema::hasColumn('purchase_returns', 'purchase_return_id')) {
            Schema::table('purchase_returns', function (Blueprint $table) {
                $table->string('purchase_return_id')->nullable()->unique()->after('id');
                $table->index('purchase_return_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropIndex(['purchase_return_id']);
            $table->dropColumn('purchase_return_id');
        });
    }
};
