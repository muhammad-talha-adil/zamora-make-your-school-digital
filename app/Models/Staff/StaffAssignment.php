<?php

namespace App\Models\Staff;

use App\Models\Campus;
use App\Models\StaffDepartment;
use App\Models\StaffDesignation;
use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A job one person holds.
 *
 * `staff_profiles.designation_id` is singular, and a school does not employ
 * people that way: the man who drives the van also does the gardening, and the
 * clerk also runs the library. One employee, several jobs.
 *
 * One of them is **primary** — the job this person *is*, which the staff list
 * shows and the salary is agreed against. The database allows only one open
 * primary at a time.
 */
class StaffAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_profile_id',
        'designation_id',
        'department_id',
        'campus_id',
        'is_primary',
        'started_on',
        'ended_on',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'started_on' => 'date',
        'ended_on' => 'date',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<StaffDesignation, $this>
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(StaffDesignation::class);
    }

    /**
     * @return BelongsTo<StaffDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class);
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /** Jobs the person still holds. */
    public function scopeCurrent($query)
    {
        return $query->whereNull('ended_on');
    }
}
