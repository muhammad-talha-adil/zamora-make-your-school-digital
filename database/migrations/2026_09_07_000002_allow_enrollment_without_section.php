<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an enrollment record stand without a section.
 *
 * A class does not have to be split into sections, and the admission form
 * accepts a blank section in that case — but the column was NOT NULL, so those
 * admissions failed at the insert and the student was never created.
 *
 * The create migration has been corrected for fresh installs; this brings
 * databases that already ran it into line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollment_records', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollment_records', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable(false)->change();
        });
    }
};
