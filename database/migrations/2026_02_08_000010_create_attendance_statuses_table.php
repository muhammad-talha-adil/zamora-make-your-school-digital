<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the attendance_statuses table to store different
     * attendance statuses like Present, Absent, Leave, Late, etc.
     */
    public function up(): void
    {
        Schema::create('attendance_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Present, Absent, Leave, Late
            $table->string('code', 10)->unique(); // e.g., P, A, L, LT

            // The share of a day a student on this status is counted present:
            // 1.00 present, 0.50 half day, 0.00 absent.
            $table->decimal('weight', 3, 2)->default(0);

            // Whether the day counts towards the days the child was expected.
            // Approved leave is shown in its own column and still expected, so
            // this is only false for statuses that mean the school was shut for
            // that child in particular.
            $table->boolean('counts_as_expected')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_statuses');
    }
};
