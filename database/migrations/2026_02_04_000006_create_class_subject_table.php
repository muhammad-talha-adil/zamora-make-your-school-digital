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
     * - 2026_02_04_000006_create_class_subject_table.php
     * - 2026_02_16_000001_add_section_id_to_class_subject_table.php
     */
    public function up(): void
    {
        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Unique constraint with section_id (allows NULL for class-level assignments)
            $table->unique(['class_id', 'section_id', 'subject_id'], 'class_subject_unique');
        });

        // Add foreign key for section_id (needs to be added after table creation)
        Schema::table('class_subject', function (Blueprint $table) {
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subject');
    }
};
