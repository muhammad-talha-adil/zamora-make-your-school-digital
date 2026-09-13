<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A family who asked about a place, before there is a child on the roll.
 *
 * Deliberately not a `Student`: an enquiry has no enrolment period, no fee and
 * no register, and making one a student would put every family who never came
 * back into every list in this system.
 */
class AdmissionEnquiry extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_VISITED = 'visited';

    public const STATUS_ADMITTED = 'admitted';

    /** They said no, or stopped answering. */
    public const STATUS_CLOSED = 'closed';

    /**
     * The statuses an enquiry is still worth chasing under.
     *
     * @var array<int, string>
     */
    public const LIVE_STATUSES = [self::STATUS_OPEN, self::STATUS_CONTACTED, self::STATUS_VISITED];

    protected $fillable = [
        'student_name',
        'dob',
        'gender_id',
        'guardian_name',
        'phone',
        'email',
        'address',
        'campus_id',
        'class_id',
        'session_id',
        'status',
        'notes',
        'follow_up_on',
        'handled_by',
        'student_id',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'follow_up_on' => 'date',
            'converted_at' => 'datetime',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * The child this enquiry became, once it did.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isConverted(): bool
    {
        return $this->student_id !== null;
    }

    /**
     * Enquiries still worth chasing.
     */
    public function scopeLive($query)
    {
        return $query->whereIn('status', self::LIVE_STATUSES)->whereNull('student_id');
    }

    /**
     * Enquiries due to be rung today or overdue.
     */
    public function scopeDue($query, ?string $on = null)
    {
        return $query->live()
            ->whereNotNull('follow_up_on')
            ->whereDate('follow_up_on', '<=', $on ?? now()->toDateString());
    }

    /**
     * Narrows a list to the campuses this user may see.
     *
     * An enquiry has no enrolment, so the campus on the enquiry itself is what
     * decides. One with no campus named is a walk-in nobody has placed yet, and
     * stays visible to everybody who may see enquiries at all — hiding it would
     * lose the family.
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

        return $query->where(function ($outer) use ($campusId) {
            $outer->whereNull('campus_id')->orWhere('campus_id', $campusId);
        });
    }
}
