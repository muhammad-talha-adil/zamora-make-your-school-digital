<?php

namespace App\Models\Finance;

use App\Models\Campus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_no',
        'entry_date',
        'source_module',
        'source_type',
        'source_id',
        'campus_id',
        'student_id',
        'status',
        'description',
        'meta',
        'created_by',
        'approved_by',
        'reversed_by',
        'reversal_of',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'meta' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of');
    }

    public function reversalEntries(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of');
    }
}
