<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `name` was uniquely indexed at the DB layer, but with soft deletes the
 * physical row for a deleted type still exists, so recreating a type with
 * the same name threw a DB integrity error even once the app-level unique
 * rule was scoped to non-trashed rows via `whereNull('deleted_at')`.
 * Replace the raw unique index with a plain lookup index and let the
 * Form Request own uniqueness among active rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff_document_types', function (Blueprint $table) {
            $table->dropUnique('staff_document_types_name_unique');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_document_types', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->unique('name');
        });
    }
};
