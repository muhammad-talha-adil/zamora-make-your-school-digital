<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A teacher's responsibility for a class.
 *
 * Either for one subject in a section, or — as class teacher — for the section
 * itself. This is what lets the system answer "is this your class?", which
 * before it simply could not.
 */
class TeacherClassAssignment extends Model
{
    protected $fillable = [
        'staff_profile_id',
        'session_id',
        'class_id',
        'section_id',
        'subject_id',
        'is_class_teacher',
        'periods_per_week',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_class_teacher' => 'boolean',
        'is_active' => 'boolean',
        'periods_per_week' => 'integer',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
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

    /*
     * Every scope names its table. These are reached through a
     * `hasManyThrough` from the user, which joins `staff_profiles` — and that
     * table has an `is_active` of its own, so an unqualified column is
     * ambiguous and the query fails outright.
     */

    public function scopeActive($query)
    {
        return $query->where('teacher_class_assignments.is_active', true);
    }

    public function scopeClassTeacher($query)
    {
        return $query->where('teacher_class_assignments.is_class_teacher', true);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('teacher_class_assignments.session_id', $sessionId);
    }

    /**
     * Assignments covering a section, including whole-class ones.
     *
     * A teacher given the class rather than a section — which is how a school
     * with no sections is arranged — covers every section within it.
     */
    public function scopeCoveringSection($query, ?int $classId, ?int $sectionId)
    {
        return $query->where('teacher_class_assignments.class_id', $classId)
            ->where(function ($q) use ($sectionId) {
                $q->whereNull('teacher_class_assignments.section_id');

                if ($sectionId !== null) {
                    $q->orWhere('teacher_class_assignments.section_id', $sectionId);
                }
            });
    }
}
