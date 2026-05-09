<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'campus_id',
        'payroll_month_id',
        'payroll_year',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'processed_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'processed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(Month::class, 'payroll_month_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }
}
