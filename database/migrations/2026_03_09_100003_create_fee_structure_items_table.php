<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Structure Items Migration
 * 
 * Detail lines for fee structures. Each item represents one fee head
 * with its amount and billing rules within a fee structure.
 * 
 * Example:
 * Fee Structure: "Grade 10 Fee Plan 2024"
 *   - Item 1: Tuition Fee = 5000 PKR (monthly, Aug-Jun)
 *   - Item 2: Computer Fee = 500 PKR (monthly, Aug-Jun, optional)
 *   - Item 3: Annual Charges = 3000 PKR (yearly, August only)
 *   - Item 4: Admission Fee = 2000 PKR (once, on admission)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            
            // Parent structure
            $table->foreignId('fee_structure_id')
                ->constrained('fee_structures')
                ->onDelete('cascade');
            
            // Fee head reference
            $table->foreignId('fee_head_id')
                ->constrained('fee_heads')
                ->onDelete('restrict');
            
            // Amount and frequency
            $table->decimal('amount', 12, 2); // Amount in PKR
            $table->enum('frequency', ['monthly', 'yearly', 'once'])->default('monthly');
            
            // Billing rules
            $table->boolean('applicable_on_admission')->default(false); // Charge on admission?
            $table->foreignId('billing_month_id')
                ->nullable()
                ->constrained('months')
                ->onDelete('restrict'); // Specific month for billing
            $table->unsignedSmallInteger('billing_year')->nullable(); // Specific year if needed
            
            // Month range for recurring fees
            $table->foreignId('starts_from_month_id')
                ->nullable()
                ->constrained('months')
                ->onDelete('restrict'); // Start month (e.g., August)
            $table->foreignId('ends_at_month_id')
                ->nullable()
                ->constrained('months')
                ->onDelete('restrict'); // End month (e.g., June)
            
            // Behavior flags
            $table->boolean('is_optional')->default(false); // Can student opt out?
            $table->boolean('is_transport_related')->default(false); // Special handling for transport
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['fee_structure_id', 'fee_head_id']);
            $table->index('frequency');
            $table->index('billing_month_id');
            
            // Unique constraint: one fee head per structure
            $table->unique(['fee_structure_id', 'fee_head_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_items');
    }
};
