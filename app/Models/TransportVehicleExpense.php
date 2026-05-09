<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportVehicleExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'campus_id',
        'transport_vehicle_id',
        'expense_type',
        'expense_date',
        'amount',
        'payment_method',
        'reference_no',
        'description',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
