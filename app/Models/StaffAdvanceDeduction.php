<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffAdvanceDeduction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_advance_id',
        'payroll_run_item_id',
        'amount',
        'deducted_on',
        'type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deducted_on' => 'date',
        ];
    }

    public function staffAdvance(): BelongsTo
    {
        return $this->belongsTo(StaffAdvance::class);
    }

    public function payrollRunItem(): BelongsTo
    {
        return $this->belongsTo(PayrollRunItem::class);
    }
}
