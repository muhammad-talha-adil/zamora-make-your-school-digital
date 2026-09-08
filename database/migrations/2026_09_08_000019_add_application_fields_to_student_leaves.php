<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a family ask for leave, rather than only the office recording it.
 *
 * `student_leaves` already had a status and an approver, but nothing ever
 * created a leave except staff — so the approval columns described a decision
 * nobody had asked for. A guardian who telephones to say their child will be
 * away for a wedding still has somebody in the office typing it in, and if that
 * person forgets, the child is marked absent and the family is sent a message
 * saying so.
 *
 * There is no separate guardian portal: a guardian signs in to the student's
 * portal, which is why the application is recorded against the child with the
 * user who submitted it noted beside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_leaves', function (Blueprint $table) {
            // Who asked. Null for the office's own entries, which is how every
            // existing row was made.
            $table->foreignId('applied_by')
                ->nullable()
                ->after('description')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('applied_at')->nullable()->after('applied_by');

            // The decision, and when it was taken.
            $table->timestamp('decided_at')->nullable()->after('approved_by');
            $table->text('decision_note')->nullable()->after('decided_at');

            // A doctor's certificate for a sick leave, or a card for a wedding.
            $table->string('attachment_path')->nullable()->after('decision_note');
        });
    }

    public function down(): void
    {
        Schema::table('student_leaves', function (Blueprint $table) {
            $table->dropForeign(['applied_by']);
            $table->dropColumn(['applied_by', 'applied_at', 'decided_at', 'decision_note', 'attachment_path']);
        });
    }
};
