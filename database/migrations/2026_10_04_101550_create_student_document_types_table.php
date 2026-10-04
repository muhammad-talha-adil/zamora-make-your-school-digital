<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The kinds of paper a school files against a student (B-Form, CNIC copy,
 * birth certificate, previous school leaving certificate, and so on).
 *
 * `name` carries a plain lookup index rather than a raw unique index, the
 * same fix already applied to `staff_document_types`: with soft deletes the
 * physical row for a deleted type still exists, so a raw unique index would
 * throw a DB integrity error the moment a type with the same name was
 * recreated. The Form Request owns uniqueness among active rows instead.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_document_types');
    }
};
