<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop exam_groups and exam_offerings tables as they are being replaced
     * by scope-based exam_papers and direct exam linking.
     */
    public function up(): void
    {
        // Drop exam_groups table first (depends on exam_offerings)
        Schema::dropIfExists('exam_groups');
        
        // Then drop exam_offerings table
        Schema::dropIfExists('exam_offerings');
    }

    /**
     * Reverse the migrations.
     * Recreate the tables (for rollback purposes only - data will be lost)
     */
    public function down(): void
    {
        // Recreate exam_offerings table
        Schema::create('exam_offerings', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('campus_id');
            $table->unsignedBigInteger('grade_system_id')->nullable();
            $table->enum('result_visibility', ['draft', 'published', 'hidden'])->default('draft');
            $table->datetime('publish_at')->nullable();
            $table->datetime('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('cascade');
            $table->foreign('grade_system_id')->references('id')->on('grade_systems')->onDelete('set null');
        });
        
        // Recreate exam_groups table
        Schema::create('exam_groups', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('exam_offering_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('exam_offering_id')->references('id')->on('exam_offerings')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('school_classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });
    }
};
