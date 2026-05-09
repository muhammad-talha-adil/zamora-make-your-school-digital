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

        Schema::table('student_inventory_records', function (Blueprint $table) {
            $table->enum('billing_status', ['unbilled', 'billed', 'partially_billed'])
                ->default('unbilled')
                ->after('status');
            $table->boolean('is_billable')->default(true)->after('billing_status');
            $table->date('billing_date')->nullable()->after('is_billable');
            $table->foreignId('student_account_charge_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('student_account_charges')
                ->nullOnDelete();

            $table->index(['billing_status', 'is_billable'], 'idx_student_inventory_billing');
            $table->index('student_account_charge_id', 'idx_student_inventory_charge');
        });

        Schema::table('student_inventory_items', function (Blueprint $table) {
            $table->foreignId('student_account_charge_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('student_account_charges')
                ->nullOnDelete();
            $table->boolean('is_billed')->default(false)->after('student_account_charge_id');
            $table->integer('billed_quantity')->default(0)->after('is_billed');
            $table->integer('credited_quantity')->default(0)->after('billed_quantity');

            $table->index(['is_billed', 'billed_quantity'], 'idx_student_inventory_item_billed');
            $table->index('student_account_charge_id', 'idx_student_inventory_item_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_inventory_items', function (Blueprint $table) {
            $table->dropIndex('idx_student_inventory_item_billed');
            $table->dropIndex('idx_student_inventory_item_charge');
            $table->dropConstrainedForeignId('student_account_charge_id');
            $table->dropColumn(['is_billed', 'billed_quantity', 'credited_quantity']);
        });

        Schema::table('student_inventory_records', function (Blueprint $table) {
            $table->dropIndex('idx_student_inventory_billing');
            $table->dropIndex('idx_student_inventory_charge');
            $table->dropConstrainedForeignId('student_account_charge_id');
            $table->dropColumn(['billing_status', 'is_billable', 'billing_date']);
        });

        Schema::table('fee_voucher_items', function (Blueprint $table) {
            $table->dropIndex('idx_voucher_item_charge');
            $table->dropIndex('idx_voucher_item_source_item');
            $table->dropConstrainedForeignId('student_account_charge_id');
            $table->dropColumn(['source_module', 'reference_item_id']);
        });
    }
};
