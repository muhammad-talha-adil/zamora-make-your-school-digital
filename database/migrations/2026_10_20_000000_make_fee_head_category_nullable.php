<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Category is no longer a field the user fills in when creating a Fee Head
 * (Name + Frequency now fully describe it) - switched from a strict DB enum
 * to a plain nullable string so an uncategorised fee head is simply left
 * blank instead of needing an arbitrary default value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_heads', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fee_heads', function (Blueprint $table) {
            $table->string('category', 50)->nullable(false)->default('misc')->change();
        });
    }
};
