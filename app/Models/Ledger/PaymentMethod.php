<?php

namespace App\Models\Ledger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use SoftDeletes;
    protected $table = 'payment_methods';

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get ledgers using this payment method
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'payment_method', 'code');
    }

    /**
     * Scope: Active payment methods only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
