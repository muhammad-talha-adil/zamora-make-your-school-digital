<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the attendance_summaries table to store pre-computed
     * attendance summaries for reporting purposes.
     */
    public function up(): void
    {
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');
            $table->foreignId('session_id')
                ->constrained('academic_sessions')
                ->onDelete('restrict');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('present_count')->default(0);
            $table->unsignedSmallInteger('absent_count')->default(0);
            $table->unsignedSmallInteger('leave_count')->default(0);
            $table->unsignedSmallInteger('late_count')->default(0);
            $table->unsignedSmallInteger('half_day_count')->default(0);
            $table->unsignedSmallInteger('total_days')->default(0);
            $table->unsignedSmallInteger('expected_days')->default(0);
            $table->decimal('present_equivalent', 6, 2)->default(0);

            // The register a summary was last built from, so a stale row is
            // recognisable without recomputing it.
            $table->timestamp('computed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('student_id');
            $table->index('session_id');
            $table->index('month');
            $table->index('year');

            // Composite unique constraint: one summary row per student per month.
            $table->unique(['student_id', 'session_id', 'month', 'year'], 'unique_monthly_summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};
