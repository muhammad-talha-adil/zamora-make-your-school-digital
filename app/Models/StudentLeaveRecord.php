<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLeaveRecord extends Model
{
    protected $fillable = [
        'student_id',
        'leave_date',
        'student_status_id',
        'description',
    ];

    protected $casts = [
        'leave_date' => 'date',
    ];

    /**
     * Get the student that this leave record belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the status associated with this leave record.
     */
    public function studentStatus(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class);
    }
}
