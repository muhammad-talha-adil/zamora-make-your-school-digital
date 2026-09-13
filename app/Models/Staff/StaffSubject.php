<?php

namespace App\Models\Staff;

use App\Models\StaffProfile;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A subject this person may be given.
 *
 * The timetable and the exam screens should be offering a physics teacher
 * physics, and a school needs to know who can cover a class when somebody is
 * away. Neither was answerable.
 */
class StaffSubject extends Model
{
    protected $fillable = ['staff_profile_id', 'subject_id', 'is_primary'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
