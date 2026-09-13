<?php

namespace App\Models\Staff;

use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A paper the school holds a copy of.
 *
 * The expiry date is a column of its own because a lapsed police verification
 * or contract is the thing a school is fined for, and nobody notices it in a
 * folder.
 */
class StaffDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_profile_id', 'kind', 'title', 'path', 'reference_no',
        'issued_on', 'expires_on', 'uploaded_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Papers that have run out, or are about to. */
    public function scopeExpiringBy($query, $date)
    {
        return $query->whereNotNull('expires_on')->whereDate('expires_on', '<=', $date);
    }
}
