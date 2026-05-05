<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Heads Migration
 *
 * Master table for all fee heads (Tuition, Admission, Transport, Fine, etc.)
 * This is the foundation of the fee system - all fee charges reference a fee head.
 *
 * Examples:
 * - Tuition Fee (monthly, recurring)
 * - Admission Fee (one-time, non-recurring)
 * - Transport Fee (monthly, optional)
 * - Computer Lab Fee (monthly, optional)
 * - Annual Charges (yearly, recurring)
 * - Late Payment Fine (one-time, non-recurring)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_heads', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('name', 100); // e.g., "Tuition Fee", "Transport Fee"
            $table->string('code', 50)->unique(); // e.g., "TUITION", "TRANSPORT"

            // Category classification
            $table->enum('category', [
                'monthly',
                'annual',
                'one_time',
                'transport',
                'fine',
                'discount',
                'misc',
            ])->index();

            // Behavior flags
            $table->boolean('is_recurring')->default(false); // Does it repeat?
            $table->enum('default_frequency', ['monthly', 'yearly', 'once'])->default('once');
            $table->boolean('is_optional')->default(false); // Can students opt out?

            // Display and status
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['is_active', 'category']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_heads');
    }
};
