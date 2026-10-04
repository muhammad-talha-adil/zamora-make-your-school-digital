<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffAdvance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_profile_id',
        'amount',
        'disbursed_date',
        'monthly_deduction_amount',
        'balance_remaining',
        'status',
        'notes',
        'given_by',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'monthly_deduction_amount' => 'decimal:2',
            'balance_remaining' => 'decimal:2',
            'disbursed_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function givenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(StaffAdvanceDeduction::class);
    }
}
