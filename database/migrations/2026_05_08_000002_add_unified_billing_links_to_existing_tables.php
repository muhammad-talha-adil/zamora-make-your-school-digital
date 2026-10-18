<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fee_voucher_items', function (Blueprint $table) {
            $table->foreignId('student_account_charge_id')
                ->nullable()
                ->after('fee_voucher_id')
                ->constrained('student_account_charges')
                ->nullOnDelete();
            $table->string('source_module', 50)->nullable()->after('source_type');
            $table->unsignedBigInteger('reference_item_id')->nullable()->after('reference_id');

            $table->index('student_account_charge_id', 'idx_voucher_item_charge');
            $table->index(['source_module', 'reference_item_id'], 'idx_voucher_item_source_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_voucher_items', function (Blueprint $table) {
            $table->dropIndex('idx_voucher_item_charge');
            $table->dropIndex('idx_voucher_item_source_item');
            $table->dropConstrainedForeignId('student_account_charge_id');
            $table->dropColumn(['source_module', 'reference_item_id']);
        });
    }
};
