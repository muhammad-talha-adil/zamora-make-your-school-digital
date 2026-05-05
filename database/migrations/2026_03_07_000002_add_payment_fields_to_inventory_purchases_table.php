<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds payment tracking fields to inventory_purchases table.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_purchases', 'paid_amount')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('total_amount')->comment('Amount paid to supplier');
                $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('paid_amount')->comment('Payment status');
                $table->date('payment_date')->nullable()->after('payment_status')->comment('Date of payment');
                $table->date('due_date')->nullable()->after('payment_date')->comment('Payment due date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_purchases', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'payment_status', 'payment_date', 'due_date']);
        });
    }
};
