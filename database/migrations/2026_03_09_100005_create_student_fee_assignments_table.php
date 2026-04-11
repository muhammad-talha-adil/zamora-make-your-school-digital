<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student Fee Assignments Migration
 * 
 * Student-specific fee overrides, extra charges, waivers, or custom settings.
 * This table allows individual students to have different fee amounts than
 * the standard fee structure.
 * 
 * Use Cases:
 * - Override: Student pays 6000 instead of standard 5000 for tuition
 * - Discount: Student gets 1000 PKR off transport fee
 * - Extra Charge: Student pays extra 500 for special coaching
 * - Waiver: Student doesn't pay computer fee at all
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_assignments', function (Blueprint $table) {
            $table->id();
            
            // Student reference
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            
            $table->foreignId('student_enrollment_record_id')
                ->constrained('student_enrollment_records')
                ->onDelete('cascade');
            
            // Scope (cached from enrollment for performance)
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->onDelete('restrict');
            
            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->onDelete('restrict');
            
            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->onDelete('restrict');
            
            $table->foreignId('section_id')
                ->constrained('sections')
                ->onDelete('restrict');
            
            // Fee head being overridden
            $table->foreignId('fee_head_id')
                ->constrained('fee_heads')
                ->onDelete('restrict');
            
            // Assignment details
            $table->enum('assignment_type', ['override', 'discount', 'extra_charge', 'waiver'])
                ->index();
            
            $table->enum('value_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('amount', 12, 2); // Amount or percentage value
            
            // Effective date range
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            // Status and approval
            $table->boolean('is_active')->default(true)->index();
            $table->text('reason')->nullable(); // Why this assignment?
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance (with custom short names)
            $table->index(['student_id', 'is_active'], 'idx_assign_student_active');
            $table->index(['student_enrollment_record_id', 'is_active'], 'idx_assign_enroll_active');
            $table->index(['session_id', 'campus_id', 'class_id', 'section_id'], 'idx_assign_scope');
            $table->index(['fee_head_id', 'assignment_type'], 'idx_assign_head_type');
            $table->index(['effective_from', 'effective_to'], 'idx_assign_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_assignments');
    }
};
