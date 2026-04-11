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
        Schema::table('student_discounts', function (Blueprint $table) {
            // Flag to identify manual adjustments created during admission
            // These should NOT appear in the regular discount options list
            $table->boolean('is_manual_adjustment')
                ->default(false)
                ->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_discounts', function (Blueprint $table) {
            $table->dropColumn('is_manual_adjustment');
        });
    }
};
