<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The staff module's foundation.
 *
 * Everything the module can ever do is decided here, so this is the migration
 * to argue with rather than the ones after it.
 *
 * **Nothing existing is dropped.** `staff_profiles.designation_id`,
 * `basic_salary`, `allowance_amount` and `deduction_amount` all stay exactly
 * where they are, and payroll keeps running off them while the new shape is
 * filled in beside them. A module that stops working halfway through its own
 * rebuild is not a rebuild, it is an outage.
 *
 * Four things the plan (Part 11) says must change, and how:
 *
 *  1. **`designation_id` is singular.** A man who drives the van and does the
 *     gardening is one employee with two jobs. `staff_assignments` holds them.
 *  2. **`allowance_amount` is one lump.** A school that cannot say *which*
 *     allowance cannot answer a member of staff who asks. `salary_heads` names
 *     them; `staff_salary_components` sets the amounts.
 *  3. **The teacher is only their classes.** `staff_qualifications` and
 *     `staff_subjects` hold the rest.
 *  4. **The personal file is thin** — `staff_profiles` has no CNIC, no phone,
 *     no date of birth, and neither does `users`. Added here.
 *
 * And two things the plan needs that do not exist at all: the staff member's
 * own attendance, and their leave.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->personalFile();
        $this->jobs();
        $this->salary();
        $this->file();
        $this->attendanceAndLeave();
        $this->employmentPeriods();
    }

    /**
     * What a school keeps about a person.
     *
     * The CNIC is the identity here — it is on every form a school files, and
     * it is what tells two people called Muhammad Ali apart. Nullable, because
     * the records already in the table do not have one and a school should not
     * be locked out of its own system until it goes and finds forty of them.
     */
    private function personalFile(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('cnic', 15)->nullable()->after('employee_no');
            $table->string('phone', 20)->nullable()->after('cnic');
            $table->date('dob')->nullable()->after('phone');
            $table->foreignId('gender_id')->nullable()->after('dob')
                ->constrained('genders')->nullOnDelete();
            $table->string('blood_group', 5)->nullable()->after('gender_id');
            $table->text('address')->nullable()->after('blood_group');

            // The number somebody rings when a member of staff is taken ill at
            // work, which is the only reason this is on the record at all.
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone');

            $table->string('photo')->nullable()->after('emergency_contact_relation');

            $table->index('cnic');
            $table->index('phone');
        });
    }

    /**
     * One person, many jobs.
     *
     * The blocking fix. `designation_id` on the profile stays and becomes the
     * *primary* job — what the list and the ID card show — while the full set
     * lives here.
     */
    private function jobs(): void
    {
        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('designation_id')->constrained('staff_designations')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('staff_departments')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();

            /*
             * One job is the primary one: the job this person *is*, which the
             * staff list shows and the salary is agreed against.
             *
             * Only one may be primary at a time, and that is a database rule
             * rather than a promise — the same partial-uniqueness shape used for
             * one open enrolment per child.
             */
            $table->boolean('is_primary')->default(false);

            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['staff_profile_id', 'ended_on']);
        });

        $this->onePrimaryJobPerPerson();
    }

    /**
     * Salary, in named parts.
     *
     * **Phase 0's decision is written into this shape.** One salary for the
     * person, not one per job: the components hang off `staff_profiles`, never
     * off `staff_assignments`. A school that wants to pay for a second job adds
     * an allowance head for it, which is the second option offered and the one
     * that does not create two salaries for one human being.
     */
    private function salary(): void
    {
        Schema::create('salary_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->nullable()->unique();

            // allowance | deduction — what the head does to the total.
            $table->string('type');

            // Whether it is part of the figure a school quotes as "the salary".
            $table->boolean('is_part_of_gross')->default(true);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });

        Schema::create('staff_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('salary_head_id')->constrained('salary_heads')->restrictOnDelete();

            $table->decimal('amount', 12, 2)->default(0);

            /*
             * A raise is a new row, not an edit.
             *
             * A payroll run for June must be able to say what the allowance was
             * in June, and it cannot if July's increase overwrote it.
             */
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['staff_profile_id', 'effective_from']);
        });
    }

    /**
     * The personal file: what they studied, what they may teach, and the papers
     * the school holds copies of.
     */
    private function file(): void
    {
        Schema::create('staff_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('institution')->nullable();
            $table->unsignedSmallInteger('year_completed')->nullable();
            $table->string('grade')->nullable();
            $table->timestamps();
        });

        /*
         * Which subjects a teacher may be given.
         *
         * The exam and timetable screens should be offering a physics teacher
         * physics, and a school should be able to see who can cover a class
         * when somebody is away.
         */
        Schema::create('staff_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['staff_profile_id', 'subject_id'], 'staff_subject_unique');
        });

        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();

            // cnic | degree | contract | police_verification | medical | other
            $table->string('kind');
            $table->string('title');
            $table->string('path')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('issued_on')->nullable();

            // A contract or a police verification that lapses is the thing a
            // school is fined for, so the date is worth a column of its own.
            $table->date('expires_on')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['staff_profile_id', 'kind']);
            $table->index('expires_on');
        });
    }

    /**
     * The staff member's own attendance, and their leave.
     *
     * Neither exists today: `attendances` is a student register — it carries
     * `class_id` and `section_id`, and its rows are `attendance_students`.
     *
     * `attendance_statuses` **is** reused. It is the list the school maintains
     * (P, A, L, LT) with the weight that decides what half a day is worth, and
     * a second copy of it would drift from the first.
     */
    private function attendanceAndLeave(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('campus_id')->nullable()->constrained('campuses')->nullOnDelete();
            $table->foreignId('attendance_status_id')->constrained('attendance_statuses')->restrictOnDelete();

            /*
             * A teacher's attendance is a **time**, not a tick.
             *
             * Lateness is what a school acts on, and "present" tells it nothing
             * about somebody who walks in at half past nine every day.
             */
            $table->time('check_in_at')->nullable();
            $table->time('check_out_at')->nullable();
            $table->unsignedSmallInteger('minutes_late')->default(0);

            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            // One row per person per day, held by the database rather than by
            // the code remembering to check.
            $table->unique(['staff_profile_id', 'attendance_date'], 'staff_attendance_day_unique');
            $table->index(['attendance_date', 'campus_id']);
        });

        Schema::create('staff_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->nullable()->unique();

            // The entitlement a school gives per year. Null means "as needed",
            // which is how unpaid leave works.
            $table->unsignedSmallInteger('days_per_year')->nullable();

            // Unpaid leave is a payroll deduction; paid leave is not.
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->foreignId('staff_leave_type_id')->constrained('staff_leave_types')->restrictOnDelete();

            $table->date('from_date');
            $table->date('to_date');

            // Halves are ordinary — a morning off for a hospital appointment.
            $table->decimal('days', 5, 2)->default(1);

            $table->text('reason')->nullable();

            // pending | approved | rejected | cancelled
            $table->string('status')->default('pending');

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['staff_profile_id', 'from_date']);
            $table->index('status');
        });
    }

    /**
     * Joining, leaving, and coming back.
     *
     * The same shape that already works for a child's enrolment, and for the
     * same reason: a teacher who leaves in June and returns in September has
     * two periods and one record, and every historical question — what was
     * their salary then, were they here for that payroll — reads the period,
     * not the profile.
     */
    private function employmentPeriods(): void
    {
        Schema::create('staff_employment_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained('staff_profiles')->cascadeOnDelete();
            $table->date('joined_on');
            $table->date('left_on')->nullable();

            // resigned | terminated | retired | contract_ended | other
            $table->string('leaving_reason')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('previous_period_id')->nullable()
                ->constrained('staff_employment_periods')->nullOnDelete();

            $table->timestamps();

            $table->index(['staff_profile_id', 'left_on']);
        });

        $this->oneOpenEmploymentPeriod();
    }

    /**
     * Only one job may be the primary one.
     */
    private function onePrimaryJobPerPerson(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('
                ALTER TABLE staff_assignments
                ADD COLUMN primary_marker TINYINT
                    GENERATED ALWAYS AS (CASE WHEN is_primary = 1 AND ended_on IS NULL THEN 1 ELSE NULL END) STORED,
                ADD UNIQUE INDEX staff_assignments_one_primary (staff_profile_id, primary_marker)
            '),
            'sqlite' => DB::statement('
                CREATE UNIQUE INDEX staff_assignments_one_primary
                ON staff_assignments (staff_profile_id)
                WHERE is_primary = 1 AND ended_on IS NULL
            '),
            default => null,
        };
    }

    /**
     * Only one employment period may be open.
     */
    private function oneOpenEmploymentPeriod(): void
    {
        // The generated column is added by ALTER here and not inside CREATE.
        // That works because the constraint is put on immediately, before this
        // table has any foreign keys pointing *at* it — the restriction we hit
        // before was adding one to a table that already had them.
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('
                ALTER TABLE staff_employment_periods
                ADD COLUMN is_open TINYINT
                    GENERATED ALWAYS AS (CASE WHEN left_on IS NULL THEN 1 ELSE NULL END) STORED,
                ADD UNIQUE INDEX staff_employment_periods_one_open (staff_profile_id, is_open)
            '),
            'sqlite' => DB::statement('
                CREATE UNIQUE INDEX staff_employment_periods_one_open
                ON staff_employment_periods (staff_profile_id)
                WHERE left_on IS NULL
            '),
            default => null,
        };
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_leaves');
        Schema::dropIfExists('staff_leave_types');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('staff_subjects');
        Schema::dropIfExists('staff_qualifications');
        Schema::dropIfExists('staff_salary_components');
        Schema::dropIfExists('salary_heads');
        Schema::dropIfExists('staff_assignments');
        Schema::dropIfExists('staff_employment_periods');

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gender_id');
            $table->dropColumn([
                'cnic', 'phone', 'dob', 'blood_group', 'address',
                'emergency_contact_name', 'emergency_contact_phone',
                'emergency_contact_relation', 'photo',
            ]);
        });
    }
};
