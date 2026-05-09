<?php

namespace App\Models;

use App\Models\Finance\StudentAccountCharge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportStudentAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_enrollment_record_id',
        'campus_id',
        'transport_route_id',
        'transport_stop_id',
        'monthly_fee',
        'effective_from',
        'effective_to',
        'status',
        'generate_dues',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'generate_dues' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollmentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollmentRecord::class, 'student_enrollment_record_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(TransportStop::class, 'transport_stop_id');
    }
}
