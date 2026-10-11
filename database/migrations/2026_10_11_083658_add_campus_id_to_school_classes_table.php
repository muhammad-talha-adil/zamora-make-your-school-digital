<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Classes become campus-specific: "Class 9" at Campus A is a different
     * row from "Class 9" at Campus B, each with its own sections. Nullable
     * at the DB level (a handful of soft-deleted/legacy rows may have none),
     * but every new class is required to have one — enforced in
     * StoreSchoolClassRequest, not here.
     */
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->after('id')->constrained('campuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
        });
    }
};
