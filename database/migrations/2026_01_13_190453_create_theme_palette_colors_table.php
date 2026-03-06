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
        Schema::create('theme_palette_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_palette_id')->constrained('theme_palettes')->onDelete('cascade');
            $table->string('slot');
            $table->string('hex');
            $table->string('label')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['theme_palette_id', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_palette_colors');
    }
};
