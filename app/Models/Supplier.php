<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_number',
        'opening_balance',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Ensure accessors are included in serialization
    protected $appends = ['campus_name'];

    /**
     * Get the campus that owns the supplier.
     */
    public function campus(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the display name for campus (including "All Campuses").
     */
    public function getCampusNameAttribute(): string
    {
        if ($this->campus_id === null) {
            return 'All Campuses';
        }
        return $this->campus?->name ?? 'Unknown';
    }

    /**
     * Get the purchases for this supplier.
     */
    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get total purchase amount from this supplier.
     */
    public function getTotalPurchasesAttribute(): float
    {
        return $this->purchases()->sum('total_amount');
    }

    /**
     * Get total paid amount to this supplier.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->purchases()->withSum('payments', 'amount');
    }

    /**
     * Get balance with supplier.
     */
    public function getBalanceAttribute(): float
    {
        return $this->opening_balance + $this->total_purchases;
    }

    /**
     * Scope for active suppliers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Search scope.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('contact_person', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }
}
