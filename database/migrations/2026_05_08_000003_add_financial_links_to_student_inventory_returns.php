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
        Schema::table('student_inventory_returns', function (Blueprint $table) {
            $table->enum('financial_effect', ['none', 'credit', 'refund'])
                ->default('none')
                ->after('status');
            $table->foreignId('student_account_adjustment_id')
                ->nullable()
                ->after('record_id')
                ->constrained('student_account_adjustments')
                ->nullOnDelete();

            $table->index(['financial_effect', 'student_id'], 'idx_student_inventory_return_financial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_inventory_returns', function (Blueprint $table) {
            $table->dropIndex('idx_student_inventory_return_financial');
            $table->dropConstrainedForeignId('student_account_adjustment_id');
            $table->dropColumn('financial_effect');
        });
    }
};
