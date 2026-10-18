<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_result_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('result_header_id')->unsigned()->index();
            $table->bigInteger('exam_paper_id')->unsigned()->index();
            $table->string('subject_role')->default('core');
            $table->decimal('obtained_marks', 6, 2)->nullable();
            $table->decimal('grace_marks', 5, 2)->nullable();
            $table->string('grace_reason')->nullable();
            $table->unsignedBigInteger('grace_by')->nullable();
            $table->timestamp('grace_at')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_exempt')->default(false);
            $table->text('remarks')->nullable();
            $table->decimal('total_marks_snapshot', 6, 2);
            $table->decimal('passing_marks_snapshot', 6, 2);
            $table->decimal('percentage_cache', 5, 2)->nullable();
            $table->boolean('is_pass')->nullable();
            $table->bigInteger('grade_item_id_cache')->unsigned()->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('result_header_id')->references('id')->on('exam_result_headers')->onDelete('cascade');
            $table->foreign('exam_paper_id')->references('id')->on('exam_papers')->onDelete('cascade');
            $table->foreign('grade_item_id_cache')->references('id')->on('grade_system_items')->onDelete('set null');
            $table->foreign('grace_by')->references('id')->on('users')->nullOnDelete();

            // Unique constraint
            $table->unique(['result_header_id', 'exam_paper_id'], 'erl_header_paper_unique');
            $table->index('subject_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_result_lines');
    }
};
