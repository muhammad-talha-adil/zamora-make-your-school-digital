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
        Schema::table('new_fee_payments', function (Blueprint $table) {
            $table->json('allocation_snapshot')->nullable()->after('concession_breakdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('new_fee_payments', function (Blueprint $table) {
            $table->dropColumn('allocation_snapshot');
        });
    }
};
