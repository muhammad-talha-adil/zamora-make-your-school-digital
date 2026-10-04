<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors `student_document_types.is_required`: flags the staff document
 * kinds a school expects on every member of staff's file.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff_document_types', function (Blueprint $table) {
            $table->boolean('is_required')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_document_types', function (Blueprint $table) {
            $table->dropColumn('is_required');
        });
    }
};
