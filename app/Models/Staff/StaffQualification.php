<?php

namespace App\Models\Staff;

use App\Models\StaffProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a member of staff studied.
 *
 * A school is asked for this by the board and by parents, and it is what
 * decides whether somebody may be given a subject.
 */
class StaffQualification extends Model
{
    protected $fillable = [
        'staff_profile_id', 'title', 'institution', 'year_completed', 'grade',
    ];

    protected function casts(): array
    {
        return ['year_completed' => 'integer'];
    }

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }
}
