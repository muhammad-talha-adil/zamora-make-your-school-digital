<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the students table with all fields in a single migration.
     * Combined from:
     * - 2026_02_05_000024_create_students_table.php
     * - 2026_02_07_000010_add_extra_fields_to_students_table.php
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('registration_no')->unique()->nullable();
            $table->string('student_code')->unique();
            $table->string('admission_no')->unique();
            $table->date('dob');
            $table->foreignId('gender_id')->constrained('genders')->onDelete('restrict');
            $table->string('b_form')->unique()->nullable();
            $table->foreignId('student_status_id')->constrained('student_statuses')->onDelete('restrict');
            $table->text('description')->nullable();
            $table->date('admission_date')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('student_code');
            $table->index('admission_no');
            $table->index('dob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
