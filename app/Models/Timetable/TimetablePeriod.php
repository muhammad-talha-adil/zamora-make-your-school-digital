<?php

namespace App\Models\Timetable;

use App\Models\Campus;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One slot in the school day, for a campus in a session.
 *
 * A break is a period too, so it shows up as a row in every grid, but nothing
 * may be scheduled into it.
 */
class TimetablePeriod extends Model
{
    protected $fillable = [
        'campus_id',
        'session_id',
        'name',
        'start_time',
        'end_time',
        'sort_order',
        'is_break',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_break' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * @return HasMany<TimetableEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class, 'period_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * A campus-restricted user sees only their own campus's periods.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $campusId = $user->campusId();

        return $campusId !== null ? $query->where('campus_id', $campusId) : $query;
    }
}
