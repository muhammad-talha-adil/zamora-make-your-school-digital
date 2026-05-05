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
     * - 2026_02_15_100009_create_exam_result_headers_table.php
     * - 2026_02_15_100016_update_exam_result_headers_for_exam_link.php
     *
     * Note: This uses direct exam linking (exam_id + campus/class/section) instead of exam_group_id
     */
    public function up(): void
    {
        Schema::create('exam_result_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->bigInteger('student_id')->unsigned()->index();
            $table->string('status')->default('draft'); // draft|submitted|verified|published|locked
            $table->decimal('total_obtained_cache', 7, 2)->nullable();
            $table->decimal('overall_percentage_cache', 5, 2)->nullable();
            $table->bigInteger('overall_grade_item_id_cache')->unsigned()->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('school_classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('overall_grade_item_id_cache')->references('id')->on('grade_system_items')->onDelete('set null');

            // Unique constraint
            $table->unique(['exam_id', 'student_id'], 'erh_exam_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_result_headers');
    }
};
