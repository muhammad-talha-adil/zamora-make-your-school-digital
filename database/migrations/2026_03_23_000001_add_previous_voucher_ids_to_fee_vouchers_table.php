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
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->json('previous_voucher_ids')->nullable()->after('balance_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->dropColumn('previous_voucher_ids');
        });
    }
};
