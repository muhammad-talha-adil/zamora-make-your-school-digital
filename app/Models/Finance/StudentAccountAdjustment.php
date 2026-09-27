<?php

namespace App\Models\Finance;

use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAccountAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_account_charge_id',
        'voucher_id',
        'voucher_item_id',
        'adjustment_type',
        'source_module',
        'source_id',
        'amount',
        'reason',
        'meta',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(StudentAccountCharge::class, 'student_account_charge_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(FeeVoucher::class, 'voucher_id');
    }

    public function voucherItem(): BelongsTo
    {
        return $this->belongsTo(FeeVoucherItem::class, 'voucher_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Narrows a list to what this user may see.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $campusId = $user->campusId();

        if ($campusId !== null) {
            $query->whereHas('student.currentEnrollment', function ($q) use ($campusId) {
                $q->where('campus_id', $campusId);
            });
        }

        if (! $user->isClassRestricted()) {
            return $query;
        }

        $assignments = $user->teachingAssignments()->active()->get(['class_id', 'section_id']);

        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('student.currentEnrollment', function ($outer) use ($assignments) {
            foreach ($assignments as $assignment) {
                $outer->orWhere(function ($q) use ($assignment) {
                    $q->where('class_id', $assignment->class_id);

                    if ($assignment->section_id !== null) {
                        $q->where('section_id', $assignment->section_id);
                    }
                });
            }
        });
    }
}
