<?php

namespace App\Models;

use App\Models\Staff\StaffAssignment;
use App\Models\Staff\StaffAttendance;
use App\Models\Staff\StaffDocument;
use App\Models\Staff\StaffEmploymentPeriod;
use App\Models\Staff\StaffLeave;
use App\Models\Staff\StaffQualification;
use App\Models\Staff\StaffSalaryComponent;
use App\Models\Staff\StaffSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A person who works at the school.
 *
 * **One person, one record, many jobs.** The man who drives the van and does
 * the gardening is one employee; `designation_id` below is his *primary* job,
 * and `assignments` holds all of them.
 *
 * This row is also what every other module's campus rule reads:
 * `User::campusId()` returns `staffProfile->campus_id`, so the campus width in
 * Attendance, Exam and Student is only as good as what is here.
 */
class StaffProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_no',

        // The personal file. None of this existed: neither `staff_profiles` nor
        // `users` carried a CNIC, a phone number or a date of birth.
        'cnic',
        'phone',
        'dob',
        'gender_id',
        'blood_group',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'photo',

        'campus_id',
        'department_id',
        'designation_id',
        'employment_type',
        'hire_date',
        'confirmation_date',

        /*
         * The old salary columns.
         *
         * Kept deliberately. Payroll runs off them today, and it keeps running
         * off them until the components in `staff_salary_components` are filled
         * in — a module that stops working halfway through its own rebuild is
         * an outage, not a rebuild.
         */
        'basic_salary',
        'allowance_amount',
        'deduction_amount',

        'payment_method',
        'bank_name',
        'account_no',
        'is_active',
    ];

    protected $casts = [
        'dob' => 'date',
        'hire_date' => 'date',
        'confirmation_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * @return BelongsTo<Gender, $this>
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    /**
     * The primary job's department, kept for the screens that read it directly.
     *
     * @return BelongsTo<StaffDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'department_id');
    }

    /**
     * The primary job.
     *
     * @return BelongsTo<StaffDesignation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(StaffDesignation::class, 'designation_id');
    }

    /**
     * Every job this person holds.
     *
     * @return HasMany<StaffAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    /**
     * The job this person *is* — what the staff list shows.
     *
     * Ordered, because a `hasOne` with no order is a coin toss whatever the
     * unique index says.
     *
     * @return HasOne<StaffAssignment, $this>
     */
    public function primaryAssignment(): HasOne
    {
        return $this->hasOne(StaffAssignment::class)
            ->where('is_primary', true)
            ->whereNull('ended_on')
            ->orderByDesc('started_on')
            ->orderByDesc('id');
    }

    /**
     * @return HasMany<StaffSalaryComponent, $this>
     */
    public function salaryComponents(): HasMany
    {
        return $this->hasMany(StaffSalaryComponent::class);
    }

    /**
     * @return HasMany<StaffQualification, $this>
     */
    public function qualifications(): HasMany
    {
        return $this->hasMany(StaffQualification::class);
    }

    /**
     * Subjects this person may be given.
     *
     * @return HasMany<StaffSubject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(StaffSubject::class);
    }

    /**
     * @return HasMany<StaffDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    /**
     * @return HasMany<StaffAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    /**
     * @return HasMany<StaffLeave, $this>
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(StaffLeave::class);
    }

    /**
     * @return HasMany<StaffEmploymentPeriod, $this>
     */
    public function employmentPeriods(): HasMany
    {
        return $this->hasMany(StaffEmploymentPeriod::class);
    }

    /**
     * The spell they are in now, if they are still here.
     *
     * @return HasOne<StaffEmploymentPeriod, $this>
     */
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(StaffEmploymentPeriod::class)
            ->whereNull('left_on')
            ->orderByDesc('joined_on')
            ->orderByDesc('id');
    }

    /**
     * The classes this person teaches.
     *
     * Already built for attendance A13 and read by three modules' class width.
     *
     * @return HasMany<TeacherClassAssignment, $this>
     */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassAssignment::class);
    }

    /**
     * @return HasMany<PayrollRunItem, $this>
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    /**
     * What the school quotes as the salary.
     *
     * Still read from the old lump columns. Phase 6 moves this onto the
     * components, and until it does both shapes must agree — which is why the
     * columns were not dropped.
     */
    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary + (float) $this->allowance_amount;
    }

    public function getNetSalaryAttribute(): float
    {
        return max(0, $this->gross_salary - (float) $this->deduction_amount);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Narrows a list to the staff this user may see.
     *
     * The policy guards one record; this filters a list. Both halves, together,
     * because a policy without a scope means the list screen hands out what the
     * record screen refuses — the lesson from the exam module.
     *
     * Staff have no class, so there are two widths here rather than three: a
     * teacher who may see staff at all sees their own campus.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $campusId = $user->campusId();

        if ($campusId === null) {
            return $query;
        }

        // A record with no campus is a school-wide post — the principal, the
        // accountant — and stays visible rather than disappearing.
        return $query->where(function ($outer) use ($campusId) {
            $outer->whereNull('campus_id')->orWhere('campus_id', $campusId);
        });
    }
}
