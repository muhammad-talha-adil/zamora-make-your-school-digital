<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A paper the school holds a copy of for a student.
 *
 * Mirrors `App\Models\Staff\StaffDocument`. Unlike the staff version,
 * `student_document_type_id` is a real foreign key — the student document
 * type list is managed from one place only, so an orphaned free-text kind
 * is not a risk here.
 */
class StudentDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id', 'student_document_type_id', 'path',
        'issue_date', 'expiry_date', 'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<StudentDocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(StudentDocumentType::class, 'student_document_type_id');
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
        return $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', $date);
    }
}
