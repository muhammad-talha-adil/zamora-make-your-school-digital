<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates purchase_payments table for tracking supplier payments.
     */
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->nullable()->constrained('inventory_purchases')->onDelete('set null');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('payment_number')->unique()->comment('Payment reference number (PP-2026-0001)');
            $table->date('payment_date');
            $table->decimal('amount', 10, 2)->comment('Payment amount');
            $table->string('payment_mode')->nullable()->comment('Payment mode (cash, bank, cheque, etc.)');
            $table->string('reference_number')->nullable()->comment('Reference number (cheque no., transaction id, etc.)');
            $table->text('note')->nullable()->comment('Payment notes');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['purchase_id']);
            $table->index(['campus_id', 'payment_date']);
            $table->index(['supplier_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
