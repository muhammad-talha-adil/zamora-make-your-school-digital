<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'campus_id',
        'inventory_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Ensure accessors are included in serialization
    protected $appends = ['campus_name', 'inventory_type_name'];

    /**
     * The "booted" method of the model.
     *
     * IMPROVEMENT: Added global scope to ensure only records with is_active=true are shown by default.
     * The SoftDeletes trait already ensures deleted_at IS NULL by default.
     */
    protected static function booted(): void
    {
        // Global scope to only show active items by default
        static::addGlobalScope('active', function ($builder) {
            $builder->where('is_active', true);
        });
    }

    /**
     * Get the campus that owns the item.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the type that owns the item.
     */
    public function inventoryType()
    {
        return $this->belongsTo(InventoryType::class, 'inventory_type_id');
    }

    /**
     * Get the inventory stocks for this item.
     */
    public function inventoryStock()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Get the purchases items for this item.
     */
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the student inventories for this item.
     */
    public function studentInventory()
    {
        return $this->hasMany(StudentInventory::class);
    }

    /**
     * Get total available stock quantity.
     */
    public function getTotalStockQuantityAttribute(): int
    {
        return $this->inventoryStock()->sum('available_quantity');
    }

    /**
     * Get total reserved stock quantity.
     */
    public function getTotalReservedQuantityAttribute(): int
    {
        return $this->inventoryStock()->sum('reserved_quantity');
    }

    /**
     * Get total quantity (available + reserved).
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->inventoryStock()->sum('quantity');
    }

    /**
     * Get the display name for campus.
     */
    public function getCampusNameAttribute(): string
    {
        return $this->campus?->name ?? 'Unknown';
    }

    /**
     * Get the display name for inventory type.
     */
    public function getInventoryTypeNameAttribute(): string
    {
        return $this->inventoryType?->name ?? 'Unknown';
    }

    /**
     * Check if item has low stock.
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->total_stock_quantity < $threshold;
    }

    /**
     * Check if item is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->total_stock_quantity <= 0;
    }

    /**
     * Scope for active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive items.
     */
    public function scopeInactive($query)
    {
        return $query->withoutGlobalScope('active')
            ->where('is_active', false);
    }

    /**
     * Scope for filtering by campus.
     */
    public function scopeForCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for filtering by type.
     */
    public function scopeForType($query, $typeId)
    {
        return $query->where('inventory_type_id', $typeId);
    }

    /**
     * Scope for searching by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%']);
    }

    /**
     * Scope for low stock items.
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->whereHas('inventoryStock', function ($q) use ($threshold) {
            $q->groupBy('inventory_item_id')
                ->havingRaw('COALESCE(SUM(available_quantity), 0) < ?', [$threshold]);
        });
    }

    /**
     * Scope for out of stock items.
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereDoesntHave('inventoryStock')
            ->orWhereHas('inventoryStock', function ($q) {
                $q->groupBy('inventory_item_id')
                    ->havingRaw('COALESCE(SUM(available_quantity), 0) <= 0');
            });
    }
}
