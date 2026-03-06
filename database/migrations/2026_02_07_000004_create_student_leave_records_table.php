<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the student_leave_records table to track
     * all student departure events in the system. Each time a student
     * leaves the institution (withdrawal, absence, graduation, suspension),
     * a new record is created to maintain a complete audit trail.
     *
     * This allows tracking multiple departure cycles when a student leaves,
     * returns through re-admission, and leaves again later.
     */
    public function up(): void
    {
        Schema::create('student_leave_records', function (Blueprint $table) {
            $table->id();

            // Foreign key to students table - references the student who left
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('restrict');

            // Date when the student left the institution
            $table->date('leave_date');

            // Status at the time of departure (Left, Suspended, Graduated, etc.)
            $table->foreignId('student_status_id')
                ->constrained('student_statuses')
                ->onDelete('restrict');

            // Description/reason for departure
            $table->text('description')->nullable();

            // Timestamps for record creation
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('student_id');
            $table->index('leave_date');
            $table->index('student_status_id');

            // Composite index for querying leave history by student and date
            $table->index(['student_id', 'leave_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_leave_records');
    }
};
