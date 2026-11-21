<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Description (classes, sections, subjects, sessions) and the exam type
     * short name were only ever edited on the settings screens - nothing reads them.
     */
    public function up(): void
    {
        foreach (['school_classes', 'sections', 'subjects', 'academic_sessions'] as $tableName) {
            if (Schema::hasColumn($tableName, 'description')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('description'));
            }
        }

        if (Schema::hasColumn('exam_types', 'short_name')) {
            Schema::table('exam_types', function (Blueprint $table) {
                if (Schema::hasIndex('exam_types', 'exam_types_short_name_unique')) {
                    $table->dropUnique('exam_types_short_name_unique');
                }
                $table->dropColumn('short_name');
            });
        }
    }

    public function down(): void
    {
        foreach (['school_classes', 'sections', 'subjects', 'academic_sessions'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->text('description')->nullable());
        }

        Schema::table('exam_types', fn (Blueprint $table) => $table->string('short_name')->nullable());
    }
};
