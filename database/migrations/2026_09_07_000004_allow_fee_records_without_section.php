<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a voucher and a fee assignment stand without a section.
 *
 * `student_enrollment_records.section_id` was made nullable because a class
 * need not be split into sections. These two tables copy that value straight
 * from the enrollment but were still NOT NULL, so a student in a section-less
 * class hit an integrity violation during voucher generation. The fee run
 * catches per-student errors and only logs them, so the child was quietly left
 * unbilled month after month.
 *
 * The create migrations have been corrected for fresh installs; this brings
 * databases that already ran them into line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->change();
        });

        Schema::table('student_fee_assignments', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fee_vouchers', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable(false)->change();
        });

        Schema::table('student_fee_assignments', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable(false)->change();
        });
    }
};
