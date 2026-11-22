<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('new_fee_payments', function (Blueprint $table) {
            $table->decimal('late_fee_amount', 12, 2)->default(0)->after('excess_amount');
            $table->decimal('concession_amount', 12, 2)->default(0)->after('late_fee_amount');
            $table->string('concession_reason')->nullable()->after('concession_amount');
            $table->json('concession_breakdown')->nullable()->after('concession_reason');
            $table->unsignedBigInteger('late_fee_item_id')->nullable()->after('concession_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('new_fee_payments', function (Blueprint $table) {
            $table->dropColumn(['late_fee_amount', 'concession_amount', 'concession_reason', 'concession_breakdown', 'late_fee_item_id']);
        });
    }
};
