<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The gate: thumb scanners and card readers.
 *
 * Most schools of any size already have a machine at the gate, and its records
 * are typed into a register by hand — or not typed at all. This is the backend
 * that lets those punches be taken in: somewhere to put them, a way to match
 * them to a child, and a record of what could not be matched.
 *
 * **Deliberately no device driver.** Every make of scanner speaks its own
 * protocol, and choosing one before a school has bought one is guesswork. What
 * is built here is everything on this side of that choice: whatever eventually
 * reads the machine hands `AttendancePunchImporter` a list of punches, and the
 * rest already works.
 *
 * Punches are kept raw and unedited. A punch the system could not match is not
 * thrown away — it is the evidence that a child's card is not registered, or
 * that somebody else used it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campus_id')
                ->constrained('campuses')
                ->cascadeOnDelete();

            $table->string('code', 40)->unique();
            $table->string('name');

            // biometric | rfid | card — what the machine reads.
            $table->string('device_type', 20)->default('biometric');

            $table->string('location')->nullable();
            $table->string('serial_no', 80)->nullable();

            // Where it is pointed: a machine at the gate records arrivals, one
            // at the exit records departures, and one machine may do both.
            $table->string('reads', 10)->default('both'); // in | out | both

            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });

        /*
         * What the machine calls a person.
         *
         * A scanner knows a thumb by its own enrolment number, and a card by
         * the number printed on it. Neither is the student's registration
         * number, so the two have to be tied together once.
         */
        Schema::create('attendance_device_identities', function (Blueprint $table) {
            $table->id();

            // Whom the identity belongs to. A school scans staff at the same
            // gate as children, so both are possible and exactly one is set.
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('staff_profile_id')
                ->nullable()
                ->constrained('staff_profiles')
                ->cascadeOnDelete();

            $table->string('identifier', 80);
            $table->string('identity_type', 20)->default('biometric'); // biometric | rfid | card

            $table->date('issued_on')->nullable();

            // A lost card is deactivated, not deleted: the punches it made are
            // still evidence of who came through the gate and when.
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['identity_type', 'identifier'], 'unique_device_identity');
            $table->index('student_id');
            $table->index('staff_profile_id');
        });

        Schema::create('attendance_device_punches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained('attendance_devices')
                ->nullOnDelete();

            // Exactly as the machine reported it, never rewritten. A punch that
            // matched nobody is the evidence that a card is unregistered.
            $table->string('identifier', 80)->index();
            $table->timestamp('punched_at');
            $table->string('direction', 10)->nullable(); // in | out

            // What it was matched to, once it was.
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            $table->foreignId('staff_profile_id')
                ->nullable()
                ->constrained('staff_profiles')
                ->nullOnDelete();

            // pending | applied | unmatched | duplicate | ignored
            $table->string('status', 20)->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // The same punch imported twice — a re-read of the machine's log —
            // is one punch, not two.
            $table->unique(['identifier', 'punched_at', 'device_id'], 'unique_device_punch');
            $table->index(['status', 'punched_at'], 'idx_punch_status_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_device_punches');
        Schema::dropIfExists('attendance_device_identities');
        Schema::dropIfExists('attendance_devices');
    }
};
