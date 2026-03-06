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
        Schema::create('exam_revaluation_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('request_id')->unsigned()->index();
            $table->bigInteger('action_by')->unsigned()->nullable()->index();
            $table->decimal('old_marks', 6, 2)->nullable();
            $table->decimal('new_marks', 6, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('request_id')->references('id')->on('exam_revaluation_requests')->onDelete('cascade');
            $table->foreign('action_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_revaluation_actions');
    }
};
