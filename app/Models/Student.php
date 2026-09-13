<?php

namespace App\Models;

use App\Enums\Fee\WalletDirection;
use App\Models\Fee\FeePayment;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\StudentDiscount;
use App\Models\Fee\StudentFeeAssignment;
use App\Models\Fee\StudentFeeWalletTransaction;
use App\Models\Finance\StudentAccountAdjustment;
use App\Models\Finance\StudentAccountCharge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'registration_no',
        'student_code',
        'admission_no',
        'dob',
        'gender_id',
        'b_form',
        'student_status_id',
        'description',
        'admission_date',
        'image',
    ];

    protected $casts = [
        'dob' => 'date',
        'admission_date' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function studentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class);
    }

    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardians')
            ->withPivot(['id', 'relation_id', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<StudentGuardian, $this>
     */
    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    /**
     * Get student's full name (from related User model)
     */
    public function getNameAttribute(): string
    {
        return $this->user->name ?? 'Student #'.$this->registration_no;
    }

    /**
     * Get student's registration number (alias for registration_no)
     */
    public function getRegistrationNumberAttribute(): string
    {
        return $this->registration_no ?? 'Student #'.$this->id;
    }

    /**
     * Get student's current class name from active enrollment.
     *
     * Read from `currentEnrollment` rather than by querying. This used to run
     * its own query every time it was touched, so a list of fifty children
     * reading `class` and `section` was a hundred queries — and being an
     * accessor rather than a relation, no amount of eager loading helped.
     *
     * Eager-load `currentEnrollment.class` and it now costs nothing.
     */
    public function getClassAttribute(): ?string
    {
        return $this->currentEnrollment?->class?->name;
    }

    /**
     * Get student's current section name from active enrollment.
     */
    public function getSectionAttribute(): ?string
    {
        return $this->currentEnrollment?->section?->name;
    }

    /**
     * Get the student's inventory assignments.
     */
    public function studentInventories(): HasMany
    {
        return $this->hasMany(StudentInventory::class);
    }

    public function transportAssignments(): HasMany
    {
        return $this->hasMany(TransportStudentAssignment::class);
    }

    /**
     * Get primary guardian (father or mother)
     */
    public function primaryGuardian(): BelongsToMany
    {
        return $this->guardians()->where('is_primary', true);
    }

    /**
     * Get the student's leave records.
     * This relationship tracks all departure events for the student.
     */
    public function leaveRecords(): HasMany
    {
        return $this->hasMany(StudentLeaveRecord::class);
    }

    /**
     * Get the student's enrollment records.
     * This relationship tracks all enrollment periods (admission and re-admission).
     *
     * @return HasMany<StudentEnrollmentRecord, $this>
     */
    public function enrollmentRecords(): HasMany
    {
        return $this->hasMany(StudentEnrollmentRecord::class);
    }

    /**
     * Get the student's currently active enrollment.
     *
     * Ordered, because a `hasOne` with no order is a coin toss. The database
     * allows only one open period per child — `enforce_single_open_enrollment`
     * — but an unordered relation would still be undefined behaviour the day
     * that constraint were ever relaxed.
     *
     * @return HasOne<StudentEnrollmentRecord, $this>
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollmentRecord::class)
            ->whereNull('leave_date')
            ->orderByDesc('admission_date')
            ->orderByDesc('id');
    }

    /**
     * Get the student's full enrollment history ordered by date (newest first).
     *
     * @return HasMany<StudentEnrollmentRecord, $this>
     */
    public function enrollmentHistory(): HasMany
    {
        return $this->hasMany(StudentEnrollmentRecord::class)
            ->orderBy('admission_date', 'desc');
    }

    /**
     * Get the student's leaves.
     */
    public function studentLeaves(): HasMany
    {
        return $this->hasMany(StudentLeave::class);
    }

    /**
     * Get the student's attendance records.
     */
    public function attendanceStudents(): HasMany
    {
        return $this->hasMany(AttendanceStudent::class);
    }

    /**
     * Get the student's attendance summaries.
     */
    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }

    /**
     * Get the student's fee vouchers.
     */
    public function feeVouchers(): HasMany
    {
        return $this->hasMany(FeeVoucher::class);
    }

    /**
     * Get the student's fee payments.
     */
    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    /**
     * Get the student's unified account charges across fee, inventory,
     * transport, and future student-billable modules.
     */
    public function accountCharges(): HasMany
    {
        return $this->hasMany(StudentAccountCharge::class);
    }

    /**
     * Get the student's account adjustments such as waivers, refunds,
     * credits, and write-offs.
     */
    public function accountAdjustments(): HasMany
    {
        return $this->hasMany(StudentAccountAdjustment::class);
    }

    /**
     * Get the student's fee assignments (overrides, discounts, etc.).
     */
    public function feeAssignments(): HasMany
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    /**
     * Get the student's discounts.

     *
     * @return HasMany<StudentDiscount, $this>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    /**
     * Get the student's wallet transactions.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(StudentFeeWalletTransaction::class);
    }

    /**
     * Get student's current wallet balance.
     */
    public function getWalletBalanceAttribute(): float
    {
        $credits = $this->walletTransactions()
            ->where('direction', WalletDirection::CREDIT)
            ->sum('amount');

        $debits = $this->walletTransactions()
            ->where('direction', WalletDirection::DEBIT)
            ->sum('amount');

        // `sum()` returns whatever the driver gives back -- an int, a float or
        // a numeric string depending on the column and engine.
        return (float) $credits - (float) $debits;
    }

    /**
     * Narrows a list to the children this user may see.
     *
     * The policy guards one record; this filters a list. Without both, the list
     * screen hands out exactly what the record screen refuses — the lesson from
     * the exam module, written down in docs/WORKING-RULES.md.
     *
     * A child's campus and class live on their open enrolment period, not on
     * the `students` row, so the filter goes through that.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $campusId = $user->campusId();
        $classRestricted = $user->isClassRestricted();

        if ($campusId === null && ! $classRestricted) {
            return $query;
        }

        $assignments = $classRestricted
            ? $user->teachingAssignments()->active()->get(['class_id', 'section_id'])
            : collect();

        if ($classRestricted && $assignments->isEmpty()) {
            // A teacher with no class yet sees nobody, rather than everybody.
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('enrollmentRecords', function ($enrollment) use ($campusId, $assignments) {
            $enrollment->whereNull('leave_date');

            if ($campusId !== null) {
                $enrollment->where('campus_id', $campusId);
            }

            if ($assignments->isEmpty()) {
                return;
            }

            $enrollment->where(function ($outer) use ($assignments) {
                foreach ($assignments as $assignment) {
                    $outer->orWhere(function ($q) use ($assignment) {
                        $q->where('class_id', $assignment->class_id);

                        // A whole-class assignment covers every section in it.
                        if ($assignment->section_id !== null) {
                            $q->where('section_id', $assignment->section_id);
                        }
                    });
                }
            });
        });
    }

    /**
     * The scope's own answer for one child, so a caller can ask without
     * reaching for the policy.
     */
    public function isVisibleTo(?User $user): bool
    {
        return static::query()->whereKey($this->getKey())->visibleTo($user)->exists();
    }
}
