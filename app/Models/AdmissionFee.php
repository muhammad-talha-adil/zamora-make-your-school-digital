<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdmissionFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'admission_date',
        'admission_fee',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'admission_fee' => 'decimal:2',
        'payment_status' => 'string',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
