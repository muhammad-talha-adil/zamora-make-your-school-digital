<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The receipt's student name and class/section were always read live off
     * `student`/`student_enrollment_record_id` — a name correction or the
     * student's class changing later silently rewrites every past receipt.
     * Every other receipt-like record in the app (inventory issuance, exam
     * registration, purchases) already freezes this kind of detail; this one
     * didn't.
     */
    public function up(): void
    {
        Schema::table('new_fee_payments', function (Blueprint $table) {
            $table->string('student_name_snapshot')->nullable()->after('student_enrollment_record_id');
            $table->string('registration_no_snapshot')->nullable()->after('student_name_snapshot');
            $table->string('class_name_snapshot')->nullable()->after('registration_no_snapshot');
            $table->string('section_name_snapshot')->nullable()->after('class_name_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('new_fee_payments', function (Blueprint $table) {
            $table->dropColumn([
                'student_name_snapshot',
                'registration_no_snapshot',
                'class_name_snapshot',
                'section_name_snapshot',
            ]);
        });
    }
};
