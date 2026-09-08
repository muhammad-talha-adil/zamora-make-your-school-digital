<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One attendance row per student per register.
 *
 * The create migration declared this as a plain index under the name
 * `unique_student_attendance`, so the name promised a constraint the database
 * never had. Two teachers saving the same class at the same moment each found
 * no existing row and each inserted one, and from then on the student was both
 * present and absent — with every count, report and percentage built on top of
 * that quietly wrong.
 *
 * The create migration is corrected for fresh installs; this cleans up whatever
 * duplicates the running database already holds and adds the real constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicates();

        Schema::table('attendance_students', function (Blueprint $table) {
            $table->dropIndex('unique_student_attendance');
        });

        Schema::table('attendance_students', function (Blueprint $table) {
            $table->unique(['attendance_id', 'student_id'], 'unique_student_attendance');
        });
    }

    /**
     * Keeps the most recently written row for each student on each register.
     *
     * The last one saved is what the teacher last chose, so it is the one that
     * reflects their intent.
     */
    private function removeDuplicates(): void
    {
        $duplicates = DB::table('attendance_students')
            ->select('attendance_id', 'student_id', DB::raw('COUNT(*) as total'))
            ->groupBy('attendance_id', 'student_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $keepId = DB::table('attendance_students')
                ->where('attendance_id', $duplicate->attendance_id)
                ->where('student_id', $duplicate->student_id)
                ->orderByDesc('id')
                ->value('id');

            DB::table('attendance_students')
                ->where('attendance_id', $duplicate->attendance_id)
                ->where('student_id', $duplicate->student_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::table('attendance_students', function (Blueprint $table) {
            $table->dropUnique('unique_student_attendance');
        });

        Schema::table('attendance_students', function (Blueprint $table) {
            $table->index(['attendance_id', 'student_id'], 'unique_student_attendance');
        });
    }
};
