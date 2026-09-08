<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The message a guardian is owed when their child is not in school.
 *
 * This is the single most expected thing an attendance module does here, and
 * everything it needs was already on hand: the guardian's phone, the absence,
 * and the moment the register was taken.
 *
 * The row is the record that a message was owed, attempted and delivered — not
 * a queue entry that disappears once handled. A parent who says "nobody told me"
 * is answered from this table, which is why the message text is stored as sent
 * rather than regenerated later from a template that may since have changed.
 *
 * Which gateway actually carries it is a separate concern: the sending driver
 * is configured, and defaults to writing to the log so the pipeline is
 * complete and testable before a vendor is chosen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_absence_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('attendance_student_id')
                ->constrained('attendance_students')
                ->cascadeOnDelete();

            $table->date('absence_date')->index();

            // Who it was addressed to, captured at the time: a family that
            // changes its number later has not changed who was told back then.
            $table->foreignId('guardian_id')
                ->nullable()
                ->constrained('guardians')
                ->nullOnDelete();

            $table->string('recipient_phone', 32)->nullable();
            $table->text('message');

            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();

            // One alert per child per day, however often the register is saved.
            $table->unique(['student_id', 'absence_date'], 'unique_daily_absence_alert');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_absence_alerts');
    }
};
