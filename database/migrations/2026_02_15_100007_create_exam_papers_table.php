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
     * - 2026_02_15_100007_create_exam_papers_table.php
     * - 2026_02_15_100014_update_exam_papers_for_scope_based_design.php
     * 
     * Note: This uses the scope-based design (exam_id + campus/class/section) instead of exam_group_id
     */
    public function up(): void
    {
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('class_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->enum('scope_type', ['SCHOOL', 'CLASS', 'SECTION'])->default('SCHOOL');
            $table->bigInteger('subject_id')->unsigned()->index();
            $table->date('paper_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_marks', 6, 2);
            $table->decimal('passing_marks', 6, 2);
            $table->decimal('paper_weight', 6, 3)->nullable();
            $table->string('status')->default('scheduled'); // scheduled|held|cancelled
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('school_classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');

            // Additional indexes
            $table->index(['exam_id', 'paper_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_papers');
    }
};
