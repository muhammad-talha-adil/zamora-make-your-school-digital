<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This table's own docblock already calls a voucher "a historical
     * snapshot" — true for its amounts (fee_voucher_items stores literal
     * values), but the student's name/registration number were always read
     * live off the `student` relation. Same gap as new_fee_payments had: a
     * later name correction silently rewrites a voucher/challan already
     * printed and handed to a parent.
     */
    public function up(): void
    {
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->string('student_name_snapshot')->nullable()->after('student_enrollment_record_id');
            $table->string('registration_no_snapshot')->nullable()->after('student_name_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->dropColumn(['student_name_snapshot', 'registration_no_snapshot']);
        });
    }
};
