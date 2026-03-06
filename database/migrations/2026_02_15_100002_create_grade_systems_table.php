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
     * - 2026_02_15_100002_create_grade_systems_table.php
     * - 2026_02_15_100018_add_is_active_to_grade_systems_table.php
     */
    public function up(): void
    {
        Schema::create('grade_systems', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->bigInteger('campus_id')->unsigned()->nullable()->index();
            $table->bigInteger('session_id')->unsigned()->nullable()->index();
            $table->string('rounding_mode')->default('half_up');
            $table->unsignedTinyInteger('precision')->default(2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('campus_id')->references('id')->on('campuses')->onDelete('set null');
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('set null');

            // Indexes
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_systems');
    }
};
