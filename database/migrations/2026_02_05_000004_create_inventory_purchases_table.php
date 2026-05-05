<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Consolidated migration for inventory_purchases table.
     * Includes all alterations:
     * - Original: supplier_id, total_amount
     * - Added: purchase_id (human-readable ID like PR-2026-0001)
     * - Added: idempotency_key (prevent duplicate submissions)
     * - Added: payment tracking fields (paid_amount, payment_status, payment_date, due_date)
     *
     * Note: Data fix methods removed - they referenced tables created after this migration.
     */
    public function up(): void
    {
        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_id')->nullable()->unique()->comment('Human-readable purchase ID (e.g., PR-2026-0001)');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->date('purchase_date');
            $table->decimal('total_amount', 12, 2)->comment('Total purchase amount');
            $table->text('note')->nullable();
            $table->string('idempotency_key')->nullable()->unique()->comment('Unique key to prevent duplicate submissions');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('Amount paid to supplier');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->comment('Payment status');
            $table->date('payment_date')->nullable()->comment('Date of payment');
            $table->date('due_date')->nullable()->comment('Payment due date');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['campus_id', 'purchase_date']);
            $table->index(['supplier_id', 'purchase_date']);
            $table->index('purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_purchases');
    }
};
