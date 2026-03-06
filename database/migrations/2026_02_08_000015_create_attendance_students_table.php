<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated migration for attendance_students table.
     * Includes leave_type_id column.
     */
    public function up(): void
    {
        Schema::create('attendance_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->onDelete('cascade');
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            $table->foreignId('attendance_status_id')
                ->constrained('attendance_statuses')
                ->onDelete('restrict');
            $table->foreignId('student_leave_id')
                ->nullable()
                ->constrained('student_leaves')
                ->nullOnDelete();
            $table->foreignId('leave_type_id')
                ->nullable()
                ->constrained('leave_types')
                ->nullOnDelete()->comment('Leave type directly selected when marking attendance');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('attendance_id');
            $table->index('student_id');
            $table->index('attendance_status_id');
            $table->index('check_in');
            $table->index('check_out');

            // Composite indexes
            $table->index(['attendance_id', 'student_id'], 'unique_student_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_students');
    }
};
