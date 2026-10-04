<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A paper the school holds a copy of for a student, filed against one of
 * the kinds in `student_document_types`.
 *
 * `expiry_date` is nullable and a column of its own, matching
 * `staff_documents.expires_on` — most student papers (B-Form, CNIC copy)
 * never expire, but the few that do (a medical certificate, say) are the
 * ones a school is caught out by, and nobody notices that in a folder.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');

            $table->foreignId('student_document_type_id')
                ->constrained('student_document_types')
                ->onDelete('restrict');

            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('path')->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('student_document_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
