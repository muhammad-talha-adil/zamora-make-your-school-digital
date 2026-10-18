<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * student_inventory_records/items are created before student_account_charges
     * exists (2026_02_28 vs 2026_05_08_000001), so the FK constraint for their
     * student_account_charge_id column has to be added here instead, once the
     * referenced table actually exists.
     */
    public function up(): void
    {
        Schema::table('student_inventory_records', function (Blueprint $table) {
            $table->foreign('student_account_charge_id')
                ->references('id')->on('student_account_charges')
                ->nullOnDelete();
        });

        Schema::table('student_inventory_items', function (Blueprint $table) {
            $table->foreign('student_account_charge_id')
                ->references('id')->on('student_account_charges')
                ->nullOnDelete();
        });

        Schema::table('student_inventory_returns', function (Blueprint $table) {
            $table->foreign('student_account_adjustment_id')
                ->references('id')->on('student_account_adjustments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_inventory_records', function (Blueprint $table) {
            $table->dropForeign(['student_account_charge_id']);
        });

        Schema::table('student_inventory_items', function (Blueprint $table) {
            $table->dropForeign(['student_account_charge_id']);
        });

        Schema::table('student_inventory_returns', function (Blueprint $table) {
            $table->dropForeign(['student_account_adjustment_id']);
        });
    }
};
