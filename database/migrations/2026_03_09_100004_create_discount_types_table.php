<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount Types Migration
 * 
 * Master table for standard discount/concession types offered by the school.
 * 
 * Examples:
 * - Sibling Discount: 10% off for 2nd child, 20% off for 3rd child
 * - Merit Scholarship: 50% off for top performers
 * - Staff Child Discount: 100% waiver for staff children
 * - Financial Aid: Variable amount based on need
 * - Early Payment Discount: 5% off if paid before due date
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_types', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('name', 100); // e.g., "Sibling Discount", "Merit Scholarship"
            $table->string('code', 50)->unique(); // e.g., "SIBLING", "MERIT"
            
            // Default value configuration
            $table->enum('value_type', ['fixed', 'percent'])->default('percent');
            $table->decimal('default_value', 10, 2)->nullable(); // Default discount amount/percentage
            
            // Description and status
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_types');
    }
};
