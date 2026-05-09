<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payment_allocations', function (Blueprint $table) {
            $table->foreignId('fee_voucher_item_id')
                ->nullable()
                ->after('fee_voucher_id')
                ->constrained('fee_voucher_items')
                ->nullOnDelete();
            $table->foreignId('student_account_charge_id')
                ->nullable()
                ->after('fee_voucher_item_id')
                ->constrained('student_account_charges')
                ->nullOnDelete();
            $table->string('source_module', 50)->nullable()->after('student_account_charge_id');

            $table->index(['student_account_charge_id', 'allocation_date'], 'idx_payment_alloc_charge_date');
            $table->index(['fee_voucher_item_id', 'allocation_date'], 'idx_payment_alloc_item_date');
        });
    }

    public function down(): void
    {
        Schema::table('fee_payment_allocations', function (Blueprint $table) {
            $table->dropIndex('idx_payment_alloc_charge_date');
            $table->dropIndex('idx_payment_alloc_item_date');
            $table->dropColumn('source_module');
            $table->dropConstrainedForeignId('student_account_charge_id');
            $table->dropConstrainedForeignId('fee_voucher_item_id');
        });
    }
};
