<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolidated from:
     * - 2026_02_15_100008_create_exam_student_registrations_table.php
     * - 2026_02_15_100015_update_exam_student_registrations_for_exam_link.php
     * 
     * Note: This uses direct exam linking (exam_id + campus/class/section) instead of exam_group_id
     */
    public function up(): void
    {
        Schema::create('exam_student_registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->bigInteger('student_id')->unsigned()->index();
            $table->bigInteger('enrollment_id')->unsigned()->nullable()->index();
            $table->string('roll_no_snapshot')->nullable();
            $table->string('status')->default('registered'); // registered|withdrawn
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('school_classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('enrollment_id')->references('id')->on('student_enrollments')->onDelete('set null');

            // Unique constraint
            $table->unique(['exam_id', 'student_id'], 'esr_exam_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_student_registrations');
    }
};
