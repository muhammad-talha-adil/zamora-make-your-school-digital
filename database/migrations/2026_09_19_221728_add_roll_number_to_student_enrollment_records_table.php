<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a per-class roll number to each enrollment period (issue #85).
     * It is scoped to the class+section+session of that enrollment, not to
     * the student globally, since a child's roll number changes when they
     * move class or section.
     */
    public function up(): void
    {
        Schema::table('student_enrollment_records', function (Blueprint $table) {
            $table->unsignedInteger('roll_number')->nullable()->after('section_id');

            // Same roll number cannot repeat within the same open class/section/session.
            $table->index(['session_id', 'class_id', 'section_id', 'roll_number'], 'enrollment_roll_number_scope_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollment_records', function (Blueprint $table) {
            $table->dropIndex('enrollment_roll_number_scope_index');
            $table->dropColumn('roll_number');
        });
    }
};
