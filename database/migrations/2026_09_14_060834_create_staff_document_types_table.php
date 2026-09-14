<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The kinds of paper a school files against a member of staff.
 *
 * `staff_documents.kind` has held a free-text value since the staff module's
 * foundation (cnic | degree | contract | police_verification | medical |
 * other, by convention only — nowhere enforced). This table gives a school a
 * real list to add to over time ("Experience Letter", say) without a code
 * change, while `kind` itself stays a plain string: no foreign key is added
 * to it, so a document filed under a kind that is later deactivated, or one
 * of the original free-text values, is never orphaned.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_document_types');
    }
};
