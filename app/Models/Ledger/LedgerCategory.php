<?php

namespace App\Models\Ledger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerCategory extends Model
{
    use SoftDeletes;

    protected $table = 'ledger_categories';

    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(LedgerCategory::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(LedgerCategory::class, 'parent_id');
    }

    /**
     * Get ledgers with this category
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'category_id');
    }

    /**
     * Check if category is income type
     */
    public function isIncome(): bool
    {
        return $this->type === 'INCOME';
    }

    /**
     * Check if category is expense type
     */
    public function isExpense(): bool
    {
        return $this->type === 'EXPENSE';
    }

    /**
     * Scope: Income categories only
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'INCOME');
    }

    /**
     * Scope: Expense categories only
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'EXPENSE');
    }

    /**
     * Scope: Active categories only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
