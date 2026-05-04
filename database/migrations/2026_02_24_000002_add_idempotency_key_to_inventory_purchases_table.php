<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds idempotency_key column to prevent duplicate purchase submissions.
     */
     public function up(): void
    {
        if (!Schema::hasColumn('inventory_purchases', 'idempotency_key')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->string('idempotency_key')->nullable()->unique()->after('note')->comment('Unique key to prevent duplicate submissions');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_purchases', function (Blueprint $table) {
            $table->dropColumn('idempotency_key');
        });
    }
};
