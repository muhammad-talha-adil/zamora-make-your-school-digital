<?php

namespace App\Models\Staff;

use App\Models\AttendanceStatus;
use App\Models\Campus;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member of staff's own attendance.
 *
 * This did not exist. `attendances` is a student register — it carries
 * `class_id` and `section_id`, and its rows are `attendance_students`.
 *
 * **A teacher's attendance is a time, not a tick.** "Present" says nothing
 * about somebody who walks in at half past nine every day, and lateness is what
 * a school acts on.
 *
 * `attendance_statuses` is reused rather than copied: it is the list the school
 * maintains, with the weight that decides what half a day is worth, and a
 * second copy would drift from the first.
 */
class StaffAttendance extends Model
{
    protected $fillable = [
        'staff_profile_id', 'attendance_date', 'campus_id', 'attendance_status_id',
        'check_in_at', 'check_out_at', 'minutes_late', 'remarks', 'marked_by', 'is_locked',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        // Times, not datetimes. Casting a TIME column as `datetime` gives it
        // an arbitrary date and Eloquent writes it back — bug 23.
        'check_in_at' => 'string',
        'check_out_at' => 'string',
        'minutes_late' => 'integer',
        'is_locked' => 'boolean',
    ];

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return BelongsTo<AttendanceStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(AttendanceStatus::class, 'attendance_status_id');
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function scopeOn($query, $date)
    {
        // `whereDate`, because a date-cast column never matches a plain
        // Y-m-d string on SQLite — bug 19.
        return $query->whereDate('attendance_date', $date);
    }
}
