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
        Schema::create('grade_system_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('grade_system_id')->unsigned()->index();
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->string('grade_letter');
            $table->decimal('grade_point', 3, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('grade_system_id')->references('id')->on('grade_systems')->onDelete('cascade');

            // Unique index for grade range within a system
            $table->unique(['grade_system_id', 'min_percentage', 'max_percentage'], 'gsi_range_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_system_items');
    }
};
