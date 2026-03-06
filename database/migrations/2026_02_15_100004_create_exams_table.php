<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Consolidated from:
     * - 2026_02_15_100004_create_exams_table.php
     * - 2026_02_15_100013_add_publish_and_lock_fields_to_exams_table.php
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('session_id')->unsigned()->index();
            $table->bigInteger('exam_type_id')->unsigned()->index();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->datetime('publish_at')->nullable();
            $table->datetime('published_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->datetime('locked_at')->nullable();
            $table->enum('status', ['scheduled', 'active', 'marking', 'published', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('exam_type_id')->references('id')->on('exam_types')->onDelete('restrict');
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['session_id', 'exam_type_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
