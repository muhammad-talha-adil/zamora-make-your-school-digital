<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the holidays table to store holidays for the school.
     * Holidays can be national (applies to all campuses) or campus-specific.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., Summer Vacation, Eid-ul-Fitr
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('campus_id')
                ->nullable()
                ->constrained('campuses')
                ->nullOnDelete();
            $table->boolean('is_national')->default(false); // True = applies to all campuses
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('start_date');
            $table->index('end_date');
            $table->index('is_national');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
