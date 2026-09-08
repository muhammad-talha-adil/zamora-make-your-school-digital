<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allows a student only one open enrollment at a time.
 *
 * An enrollment is open while `leave_date` is null. Closed rows are the
 * student's history and there may be any number of them — a class change
 * mid-year, a leave and a return, a new session — but two open rows at once
 * means the child sits in two classes and can be billed twice.
 *
 * A plain unique on (student_id, session_id) would have been wrong: a child who
 * leaves and returns inside one session legitimately needs a second row for
 * that session.
 *
 * Neither engine expresses this the same way, so each gets its own form:
 * MySQL indexes a generated column that is 1 while open and null once closed
 * (repeated nulls are allowed in a unique index), SQLite uses a partial index.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->closeDuplicateOpenRows();

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('
                ALTER TABLE student_enrollment_records
                ADD COLUMN is_open TINYINT
                    GENERATED ALWAYS AS (CASE WHEN leave_date IS NULL THEN 1 ELSE NULL END) STORED,
                ADD UNIQUE INDEX student_enrollment_records_one_open_unique (student_id, is_open)
            '),
            'sqlite' => DB::statement('
                CREATE UNIQUE INDEX student_enrollment_records_one_open_unique
                ON student_enrollment_records (student_id)
                WHERE leave_date IS NULL
            '),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('
                ALTER TABLE student_enrollment_records
                DROP INDEX student_enrollment_records_one_open_unique,
                DROP COLUMN is_open
            '),
            'sqlite' => DB::statement('DROP INDEX IF EXISTS student_enrollment_records_one_open_unique'),
            default => null,
        };
    }

    /**
     * Closes all but the newest open row per student.
     *
     * Existing data predates the constraint, so any student already carrying
     * several open rows would block the index. The newest is kept open and the
     * rest are closed on the day the next one began, which is the shape the
     * history would have had.
     */
    private function closeDuplicateOpenRows(): void
    {
        if (! Schema::hasTable('student_enrollment_records')) {
            return;
        }

        $duplicated = DB::table('student_enrollment_records')
            ->whereNull('leave_date')
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('student_id');

        foreach ($duplicated as $studentId) {
            $rows = DB::table('student_enrollment_records')
                ->where('student_id', $studentId)
                ->whereNull('leave_date')
                ->orderByDesc('admission_date')
                ->orderByDesc('id')
                ->get(['id', 'admission_date']);

            // index 0 stays open; each older row closes when the next began
            foreach ($rows->slice(1) as $offset => $row) {
                DB::table('student_enrollment_records')
                    ->where('id', $row->id)
                    ->update(['leave_date' => $rows[$offset]->admission_date]);
            }
        }
    }
};
