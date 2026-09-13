<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The parent who walked in and has not admitted a child yet.
 *
 * A family visits in March, asks about Class 5, and leaves. Today that
 * conversation is a name on a page in a diary, and when they come back in June
 * the school types everything again — if it remembers them at all.
 *
 * This is the record of that visit, and it turns into an admission without
 * anybody retyping it.
 *
 * It is deliberately **not** a student. An enquiry is not a child on the roll:
 * it has no enrolment period, no fee, no register, and creating one would put
 * a family who never came back into every list in this system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_enquiries', function (Blueprint $table) {
            $table->id();

            // What the school is told at the counter. Only the name and one way
            // of reaching them is required, because that is all a walk-in
            // conversation reliably produces.
            $table->string('student_name');
            $table->date('dob')->nullable();
            $table->foreignId('gender_id')->nullable()->constrained('genders')->nullOnDelete();

            $table->string('guardian_name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Which class they are asking about, and where.
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();

            // open | contacted | visited | admitted | closed
            $table->string('status')->default('open');
            $table->text('notes')->nullable();

            // The follow-up a school actually runs on: "ring them on Tuesday".
            $table->date('follow_up_on')->nullable();

            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();

            // Set when the enquiry becomes an admission, so a school can see
            // how many of March's visitors turned into children.
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('phone');
            $table->index('follow_up_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_enquiries');
    }
};
