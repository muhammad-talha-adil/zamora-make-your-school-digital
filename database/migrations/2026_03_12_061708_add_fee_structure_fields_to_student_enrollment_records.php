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
        if (!Schema::hasColumn('student_enrollment_records', 'fee_structure_id')) {
            Schema::table('student_enrollment_records', function (Blueprint $table) {
                // Link to fee structure
                $table->foreignId('fee_structure_id')
                    ->nullable()
                    ->constrained('fee_structures')
                    ->onDelete('set null')
                    ->after('student_status_id');

                // Fee mode: how the fee was determined
                // 'structure' = use fee structure as-is
                // 'discount' = use fee structure with discounts applied
                // 'manual' = manual fee entry (override)
                $table->enum('fee_mode', ['structure', 'discount', 'manual'])
                    ->nullable()
                    ->after('fee_structure_id');

                // Custom fee entries (JSON) - for manual override mode
                // Format: [{"fee_head_id": 1, "amount": 800, "reason": "Custom rate for this student"}]
                $table->json('custom_fee_entries')
                    ->nullable()
                    ->after('fee_mode');

                // Manual discount percentage (calculated from original vs custom amount)
                // For Option C: when user enters custom amount, we calculate implied discount
                $table->decimal('manual_discount_percentage', 5, 2)
                    ->nullable()
                    ->after('custom_fee_entries');

                // Reason for manual adjustment
                $table->text('manual_discount_reason')
                    ->nullable()
                    ->after('manual_discount_percentage');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollment_records', function (Blueprint $table) {
            $table->dropForeign(['fee_structure_id']);
            $table->dropColumn([
                'fee_structure_id',
                'fee_mode',
                'custom_fee_entries',
                'manual_discount_percentage',
                'manual_discount_reason',
            ]);
        });
    }
};
