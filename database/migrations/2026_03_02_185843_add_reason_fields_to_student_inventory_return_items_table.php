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
        Schema::table('student_inventory_return_items', function (Blueprint $table) {
            $table->foreignId('reason_id')->nullable()->constrained('reasons')->nullOnDelete();
            $table->string('custom_reason', 500)->nullable()->after('reason_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_inventory_return_items', function (Blueprint $table) {
            $table->dropForeign(['reason_id']);
            $table->dropColumn(['reason_id', 'custom_reason']);
        });
    }
};
