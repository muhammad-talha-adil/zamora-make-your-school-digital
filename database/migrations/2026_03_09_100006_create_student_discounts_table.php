<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student Discounts Migration
 * 
 * Formal student-level discounts linked to discount types.
 * This provides a structured way to track scholarships, concessions,
 * and other discount programs with approval workflows.
 * 
 * Difference from student_fee_assignments:
 * - This table: Formal discount programs with approval workflow
 * - Assignments: Ad-hoc overrides and adjustments
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_discounts', function (Blueprint $table) {
            $table->id();
            
            // Student reference
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            
            $table->foreignId('student_enrollment_record_id')
                ->constrained('student_enrollment_records')
                ->onDelete('cascade');
            
            // Discount type reference
            $table->foreignId('discount_type_id')
                ->constrained('discount_types')
                ->onDelete('restrict');
            
            // Optional: Specific fee head (null = applies to all fees)
            $table->foreignId('fee_head_id')
                ->nullable()
                ->constrained('fee_heads')
                ->onDelete('restrict');
            
            // Discount value
            $table->enum('value_type', ['fixed', 'percent'])->default('percent');
            $table->decimal('value', 12, 2); // Discount amount or percentage
            
            // Effective date range
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            // Approval workflow
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->index();
            
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            // Reason/justification
            $table->text('reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance (with custom short names)
            $table->index(['student_id', 'approval_status'], 'idx_discount_student_status');
            $table->index(['student_enrollment_record_id', 'approval_status'], 'idx_discount_enroll_status');
            $table->index(['discount_type_id', 'approval_status'], 'idx_discount_type_status');
            $table->index(['effective_from', 'effective_to'], 'idx_discount_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_discounts');
    }
};
