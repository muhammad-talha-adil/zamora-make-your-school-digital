<?php

namespace App\Models\Staff;

use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A spell of employment: joining, leaving, and coming back.
 *
 * The same shape that already works for a child's enrolment, and for the same
 * reason. A teacher who leaves in June and returns in September has **two
 * periods and one record**, and every historical question — were they here for
 * that payroll, what was their salary then — reads the period rather than the
 * profile.
 *
 * The database allows only one open period per person.
 */
class StaffEmploymentPeriod extends Model
{
    public const REASON_RESIGNED = 'resigned';

    public const REASON_TERMINATED = 'terminated';

    public const REASON_RETIRED = 'retired';

    public const REASON_CONTRACT_ENDED = 'contract_ended';

    protected $fillable = [
        'staff_profile_id', 'joined_on', 'left_on',
        'leaving_reason', 'notes', 'previous_period_id',
    ];

    protected $casts = [
        'joined_on' => 'date',
        'left_on' => 'date',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<StaffEmploymentPeriod, $this>
     */
    public function previousPeriod(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_period_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('left_on');
    }

    /** Periods covering a date, for "were they here then". */
    public function scopeCoveringDate($query, $date)
    {
        return $query->whereDate('joined_on', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('left_on')->orWhereDate('left_on', '>=', $date);
            });
    }
}
