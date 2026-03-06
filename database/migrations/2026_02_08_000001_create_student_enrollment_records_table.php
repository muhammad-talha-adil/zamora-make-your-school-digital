<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the student_enrollment_records table to track all enrollment
     * periods for each student. Each time a student is admitted or re-admitted,
     * a new enrollment record is created. When a student leaves, the leave_date
     * is set on the current enrollment record.
     *
     * This allows tracking multiple enrollment cycles when a student leaves,
     * returns through re-admission, and leaves again later.
     */
    public function up(): void
    {
        Schema::create('student_enrollment_records', function (Blueprint $table) {
            $table->id();

            // Foreign key to students table - references the student
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');

            // Academic information
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->onDelete('restrict');
            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->onDelete('restrict');
            $table->foreignId('section_id')
                ->constrained('sections')
                ->onDelete('restrict');
            $table->foreignId('campus_id')
                ->nullable()
                ->constrained('campuses')
                ->nullOnDelete();

            // Enrollment period dates
            $table->date('admission_date'); // When student joined/enrolled
            $table->date('leave_date')->nullable(); // When student left (NULL = currently active)

            // Status at the time of departure (Left, Suspended, Graduated, etc.)
            $table->foreignId('student_status_id')
                ->constrained('student_statuses')
                ->onDelete('restrict');

            // For chaining re-admissions to previous enrollments
            $table->foreignId('previous_enrollment_id')
                ->nullable()
                ->references('id')
                ->on('student_enrollment_records')
                ->onDelete('restrict');

            // Description/reason for departure
            $table->text('description')->nullable();

            // Fee information for this enrollment period
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->decimal('annual_fee', 10, 2)->default(0);

            // Timestamps for record creation
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('student_id');
            $table->index('session_id');
            $table->index('class_id');
            $table->index('section_id');
            $table->index('admission_date');
            $table->index('leave_date');
            $table->index('student_status_id');

            // Composite indexes for efficient querying
            $table->index(['student_id', 'leave_date']); // For finding active enrollments
            $table->index(['session_id', 'class_id', 'section_id']); // For class-wise queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_records');
    }
};
