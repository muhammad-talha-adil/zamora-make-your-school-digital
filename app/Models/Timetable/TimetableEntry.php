<?php

namespace App\Models\Timetable;

use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\StaffProfile;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A subject, taught by a teacher, in one period on one weekday for a section.
 */
class TimetableEntry extends Model
{
    /**
     * ISO weekday numbers (1 = Monday) to their names.
     *
     * @var array<int, string>
     */
    public const WEEKDAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $fillable = [
        'campus_id',
        'session_id',
        'class_id',
        'section_id',
        'subject_id',
        'staff_profile_id',
        'period_id',
        'weekday',
        'room',
    ];

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
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
     * @return BelongsTo<SchoolClass, $this>
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<TimetablePeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'period_id');
    }

    /**
     * Narrows a list to the entries this user may see.
     *
     * Kept beside `TimetableEntryPolicy` on purpose: they must agree. A family
     * sees only their own children's sections; a teacher without campus-wide
     * reach sees only the sections they have been given.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            $campusId = $user->campusId();

            return $campusId !== null ? $query->where('campus_id', $campusId) : $query;
        }

        if ($user->student || $user->guardian) {
            return self::restrictToFamily($query, $user);
        }

        $campusId = $user->campusId();

        if ($campusId !== null) {
            $query->where('campus_id', $campusId);
        }

        if (! $user->isClassRestricted()) {
            return $query;
        }

        $assignments = $user->teachingAssignments()->active()->get(['class_id', 'section_id']);

        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($outer) use ($assignments) {
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
    }

    private static function restrictToFamily($query, User $user)
    {
        $students = $user->student
            ? collect([$user->student])
            : $user->guardian->students()->get();

        $enrollments = $students
            ->map(fn ($student) => $student->currentEnrollment)
            ->filter();

        if ($enrollments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($outer) use ($enrollments) {
            foreach ($enrollments as $enrollment) {
                $outer->orWhere(function ($q) use ($enrollment) {
                    $q->where('class_id', $enrollment->class_id)
                        ->where('session_id', $enrollment->session_id);

                    if ($enrollment->section_id !== null) {
                        $q->where('section_id', $enrollment->section_id);
                    } else {
                        $q->whereNull('section_id');
                    }
                });
            }
        });
    }
}
