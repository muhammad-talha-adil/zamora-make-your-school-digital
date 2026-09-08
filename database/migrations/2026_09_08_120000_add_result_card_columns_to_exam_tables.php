<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a result card needs, and the module did not record.
 *
 * The exam module could work out a percentage and could not answer the three
 * questions every result card in this country answers: **did the child pass**,
 * **what position did they come**, and **what were the grace marks**. Nor could
 * it say that a subject was taken as an extra and should not count towards the
 * total.
 *
 * All of it is recorded rather than inferred, because a parent standing at the
 * office counter with the card in their hand asks about the specific number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            /*
             * What this exam is worth in the annual result. "First term 25%,
             * mid 25%, annual 50%" is the normal arrangement here, and nothing
             * weighted one exam against another, so an annual result could not
             * be assembled from the terms at all.
             *
             * Null means the exam is not part of the annual reckoning.
             */
            $table->decimal('result_weight', 5, 2)->nullable()->after('status');

            /*
             * The aggregate a child must reach to pass overall, on top of
             * passing each subject. 33% is the usual figure. Null means only
             * the per-subject rule applies.
             */
            $table->decimal('aggregate_pass_percentage', 5, 2)->nullable()->after('result_weight');

            /*
             * The most grace a school will give on one paper. Schools here set
             * a limit — "up to 3 marks" — precisely so it does not become a
             * negotiation. Null means no limit is configured.
             */
            $table->decimal('grace_marks_limit', 5, 2)->nullable()->after('aggregate_pass_percentage');
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            /*
             * core       counts towards the total, and everybody sits it
             * elective   counts, but only the children who chose it sit it —
             *            Computer instead of Biology, normal from Class 9
             * additional an extra subject: marked and graded, and deliberately
             *            **not** counted towards the total or the position
             *
             * `is_exempt` was the nearest thing available and it is not the
             * same statement: exempt says the child was not required to sit the
             * paper, not that the paper does not count.
             */
            $table->string('subject_role')->default('core')->after('subject_id');
        });

        Schema::table('exam_result_lines', function (Blueprint $table) {
            // Snapshotted from the paper, and overridable per child: the same
            // subject is core for one and additional for another.
            $table->string('subject_role')->default('core')->after('exam_paper_id');

            /*
             * Grace is kept **separate from the obtained marks**, never merged
             * into them. A child failing by two marks is lifted to the pass
             * mark rather than held back a year — and when the parent compares
             * the card with the answer sheet, the school has to be able to say
             * "you scored 31, we gave 2".
             */
            $table->decimal('grace_marks', 5, 2)->nullable()->after('obtained_marks');
            $table->string('grace_reason')->nullable()->after('grace_marks');
            $table->unsignedBigInteger('grace_by')->nullable()->after('grace_reason');
            $table->timestamp('grace_at')->nullable()->after('grace_by');

            // Null where the paper is not counted at all — exempt, or an
            // additional subject.
            $table->boolean('is_pass')->nullable()->after('percentage_cache');

            $table->foreign('grace_by')->references('id')->on('users')->nullOnDelete();
            $table->index('subject_role');
        });

        Schema::table('exam_result_headers', function (Blueprint $table) {
            // pass | fail | pending — pending while a counted paper is still
            // unmarked, because a result that is not finished has not failed.
            $table->string('result_status')->nullable()->after('status');
            $table->unsignedSmallInteger('failed_subject_count')->default(0)->after('result_status');

            /*
             * "Position: 3rd of 42" is on every result card here and nothing
             * computed it. Held in both widths because a school with three
             * sections prints both, and `ranked_out_of` so the card can say
             * what the position is out of without counting the section again.
             */
            $table->unsignedInteger('position_in_section')->nullable()->after('overall_grade_item_id_cache');
            $table->unsignedInteger('position_in_class')->nullable()->after('position_in_section');
            $table->unsignedInteger('ranked_out_of')->nullable()->after('position_in_class');

            // The class teacher's line at the bottom of the card.
            $table->text('remarks')->nullable()->after('ranked_out_of');
        });
    }

    public function down(): void
    {
        Schema::table('exam_result_headers', function (Blueprint $table) {
            $table->dropColumn([
                'result_status', 'failed_subject_count',
                'position_in_section', 'position_in_class', 'ranked_out_of', 'remarks',
            ]);
        });

        Schema::table('exam_result_lines', function (Blueprint $table) {
            $table->dropForeign(['grace_by']);
            $table->dropIndex(['subject_role']);
            $table->dropColumn([
                'subject_role', 'grace_marks', 'grace_reason', 'grace_by', 'grace_at', 'is_pass',
            ]);
        });

        Schema::table('exam_papers', function (Blueprint $table) {
            $table->dropColumn('subject_role');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['result_weight', 'aggregate_pass_percentage', 'grace_marks_limit']);
        });
    }
};
