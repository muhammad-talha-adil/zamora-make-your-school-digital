<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the attendances table to track daily attendance records.
     * Each record represents an attendance session for a specific class/section on a date.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date');
            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->onDelete('restrict');
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->onDelete('restrict');
            $table->foreignId('class_id')
                ->constrained('school_classes')
                ->onDelete('restrict');
            $table->foreignId('section_id')
                ->constrained('sections')
                ->onDelete('restrict');
            $table->foreignId('taken_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_locked')->default(false); // Prevents modification after being locked
            $table->timestamps();

            // Indexes
            $table->index('attendance_date');
            $table->index('campus_id');
            $table->index('session_id');
            $table->index('is_locked');

            // Composite indexes
            $table->index(['attendance_date', 'campus_id', 'class_id', 'section_id']);
            $table->unique(['attendance_date', 'class_id', 'section_id'], 'unique_daily_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
