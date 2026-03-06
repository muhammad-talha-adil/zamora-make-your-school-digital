<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'campus_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Ensure accessors are included in serialization
    protected $appends = ['campus_name'];

    /**
     * The "booted" method of the model.
     *
     * IMPROVEMENT: Added global scope to ensure only records with is_active=true are shown by default.
     * The SoftDeletes trait already ensures deleted_at IS NULL by default.
     */
    protected static function booted(): void
    {
        // Global scope to only show active types by default
        static::addGlobalScope('active', function ($builder) {
            $builder->where('is_active', true);
        });
    }

    /**
     * Get the campus that owns the type.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the items for this type.
     */
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get items count.
     */
    public function getItemsCountAttribute(): int
    {
        return $this->inventoryItems()->count();
    }

    /**
     * Check if this inventory type is available for all campuses.
     */
    public function isForAllCampuses(): bool
    {
        return $this->campus_id === null;
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
     * Scope for active types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive types.
     */
    public function scopeInactive($query)
    {
        return $query->withoutGlobalScope('active')
            ->where('is_active', false);
    }

    /**
     * Scope for filtering by campus.
     * Includes types that are available for all campuses (campus_id = null).
     */
    public function scopeForCampus($query, $campusId)
    {
        return $query->where(function ($q) use ($campusId) {
            $q->where('campus_id', $campusId)
              ->orWhereNull('campus_id');
        });
    }

    /**
     * Scope for types available for all campuses only.
     */
    public function scopeForAllCampuses($query)
    {
        return $query->whereNull('campus_id');
    }

    /**
     * Scope for filtering by campus (strict - only exact match).
     */
    public function scopeStrictForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for searching by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
    }
}
