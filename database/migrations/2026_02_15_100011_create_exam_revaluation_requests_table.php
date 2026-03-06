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
        Schema::create('exam_revaluation_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('exam_result_line_id')->unsigned()->index();
            $table->bigInteger('requested_by')->unsigned()->nullable()->index();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending|in_review|approved|rejected|applied
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('exam_result_line_id')->references('id')->on('exam_result_lines')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraint - one active request per result line
            $table->unique(['exam_result_line_id'], 'err_result_line_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_revaluation_requests');
    }
};
