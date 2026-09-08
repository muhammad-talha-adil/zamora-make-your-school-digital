<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a register stand for a class that has no sections.
 *
 * The last of the tables that copy the enrollment's section straight across
 * while being NOT NULL themselves. `student_enrollment_records`, `fee_vouchers`
 * and `student_fee_assignments` were dealt with when the fee module was
 * reviewed; attendance was left because it belonged to this module. Those
 * children could not be marked present at all.
 *
 * The unique index needs the same care it needed on the enrollment table: MySQL
 * treats each NULL as distinct, so `[date, class, section]` stops protecting
 * anything once the section is null and a class could collect a second register
 * for the same day. A generated column that substitutes zero for NULL gives the
 * constraint something real to compare.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->change();
        });

        $this->replaceDailyUniqueIndex();
    }

    /**
     * One register per class per day, whether or not it has sections.
     */
    private function replaceDailyUniqueIndex(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('unique_daily_attendance');
        });

        if (DB::getDriverName() === 'sqlite') {
            // SQLite compares NULLs the same way, but it does support a partial
            // index, which says the same thing in two halves.
            DB::statement(
                'create unique index unique_daily_attendance on attendances
                 (attendance_date, class_id, section_id) where section_id is not null'
            );
            DB::statement(
                'create unique index unique_daily_attendance_no_section on attendances
                 (attendance_date, class_id) where section_id is null'
            );

            return;
        }

        DB::statement(
            'alter table attendances
             add column section_key bigint unsigned as (coalesce(section_id, 0)) stored'
        );

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['attendance_date', 'class_id', 'section_key'], 'unique_daily_attendance');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('drop index if exists unique_daily_attendance');
            DB::statement('drop index if exists unique_daily_attendance_no_section');
        } else {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropUnique('unique_daily_attendance');
            });

            DB::statement('alter table attendances drop column section_key');
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['attendance_date', 'class_id', 'section_id'], 'unique_daily_attendance');
            $table->foreignId('section_id')->nullable(false)->change();
        });
    }
};
