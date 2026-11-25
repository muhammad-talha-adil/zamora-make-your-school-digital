<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An exam belongs to the campus it was created under, like every other
     * record, so the header campus switcher can narrow the list.
     *
     * Existing exams take the campus of their papers, or failing that the
     * oldest campus; papers with no campus take their exam's.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedBigInteger('campus_id')->nullable()->after('id')->index();
        });

        $fallbackCampusId = DB::table('campuses')->whereNull('deleted_at')->orderBy('id')->value('id');

        foreach (DB::table('exams')->pluck('id') as $examId) {
            $campusId = DB::table('exam_papers')
                ->where('exam_id', $examId)
                ->whereNotNull('campus_id')
                ->orderBy('id')
                ->value('campus_id') ?? $fallbackCampusId;

            DB::table('exams')->where('id', $examId)->update(['campus_id' => $campusId]);
        }

        DB::table('exam_papers')
            ->whereNull('campus_id')
            ->where('scope_type', '!=', 'SCHOOL')
            ->update([
                'campus_id' => DB::raw('(select campus_id from exams where exams.id = exam_papers.exam_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['campus_id']);
            $table->dropColumn('campus_id');
        });
    }
};
