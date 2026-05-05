<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee Fine Rules Migration
 *
 * Late payment fine rules. Defines how fines are calculated
 * when vouchers are paid after due date.
 *
 * Examples:
 * - Fixed per day: 50 PKR per day after due date
 * - Fixed once: 500 PKR flat fine after due date
 * - Percent: 2% of voucher amount as fine
 * - Grace period: No fine for first 5 days
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_fine_rules', function (Blueprint $table) {
            $table->id();

            // Scope definition
            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->onDelete('cascade');

            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->onDelete('cascade');

            $table->foreignId('class_id')
                ->nullable()
                ->constrained('school_classes')
                ->onDelete('cascade');

            $table->foreignId('section_id')
                ->nullable()
                ->constrained('sections')
                ->onDelete('cascade');

            // Optional: Specific fee head (null = applies to all fees)
            $table->foreignId('fee_head_id')
                ->nullable()
                ->constrained('fee_heads')
                ->onDelete('restrict');

            // Fine calculation
            $table->enum('fine_type', ['fixed_per_day', 'fixed_once', 'percent'])
                ->index();

            $table->decimal('fine_value', 12, 2); // Amount or percentage
            $table->unsignedSmallInteger('grace_days')->default(0); // Days before fine applies

            // Effective date range
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // Status
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            // Indexes (with custom short names)
            $table->index(['campus_id', 'session_id', 'is_active'], 'idx_fine_rule_scope_active');
            $table->index(['effective_from', 'effective_to'], 'idx_fine_rule_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_fine_rules');
    }
};
