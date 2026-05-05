<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStock extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'inventory_item_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'campus_id',
        'inventory_item_id',
    ];

    /**
     * Default low stock threshold.
     */
    public const DEFAULT_LOW_STOCK_THRESHOLD = 10;

    /**
     * Get the campus that owns the inventory stock.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the inventory item that owns the stock.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the student inventories for this stock.
     */
    public function studentInventories(): HasMany
    {
        return $this->hasMany(StudentInventory::class, 'inventory_item_id', 'inventory_item_id')
            ->where('campus_id', $this->campus_id);
    }

    /**
     * Get the campus ID for this stock.
     */
    public function getCampusId(): int
    {
        return $this->campus_id;
    }

    /**
     * Get the inventory item ID for this stock.
     */
    public function getInventoryItemId(): int
    {
        return $this->inventory_item_id;
    }

    /**
     * Get the low stock threshold.
     *
     * IMPROVEMENT: Returns threshold with default if not set.
     */
    public function getLowStockThreshold(): int
    {
        return $this->low_stock_threshold ?? self::DEFAULT_LOW_STOCK_THRESHOLD;
    }

    /**
     * Set the low stock threshold.
     */
    public function setLowStockThreshold(int $threshold): self
    {
        $this->low_stock_threshold = $threshold;

        return $this;
    }

    /**
     * Check if stock is below threshold.
     *
     * IMPROVEMENT: Helper method to check if stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->available_quantity < $this->getLowStockThreshold();
    }

    /**
     * Check if stock is out.
     */
    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Check if stock is available for given quantity.
     */
    public function isAvailable(int $quantity = 1): bool
    {
        return $this->available_quantity >= $quantity;
    }

    /**
     * Get stock status level.
     *
     * IMPROVEMENT: Returns status level (critical, warning, healthy).
     */
    public function getStockStatusLevel(): string
    {
        $threshold = $this->getLowStockThreshold();

        if ($this->available_quantity <= 0) {
            return 'critical';
        }

        if ($this->available_quantity < $threshold / 2) {
            return 'critical';
        }

        if ($this->available_quantity < $threshold) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Get remaining stock before hitting threshold.
     *
     * IMPROVEMENT: Calculate how many more items until low stock.
     */
    public function getStockUntilLow(): int
    {
        return max(0, $this->available_quantity - $this->getLowStockThreshold());
    }

    /**
     * Reserve stock quantity.
     *
     * IMPROVEMENT: Helper method to reserve stock.
     */
    public function reserveStock(int $quantity): bool
    {
        if (! $this->isAvailable($quantity)) {
            return false;
        }

        $this->reserved_quantity = ($this->reserved_quantity ?? 0) + $quantity;
        // available_quantity is auto-calculated by database, just update quantity and reserved_quantity

        return $this->save();
    }

    /**
     * Release reserved stock.
     *
     * IMPROVEMENT: Helper method to release reserved stock.
     */
    public function releaseStock(int $quantity): bool
    {
        $quantity = min($quantity, $this->reserved_quantity ?? 0);

        if ($quantity <= 0) {
            return false;
        }

        $this->reserved_quantity = ($this->reserved_quantity ?? 0) - $quantity;
        // available_quantity is auto-calculated by database

        return $this->save();
    }

    /**
     * Add quantity to stock.
     *
     * IMPROVEMENT: Helper method to add quantity.
     */
    public function addQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        $this->quantity = ($this->quantity ?? 0) + $quantity;
        // available_quantity is auto-calculated by database

        return $this->save();
    }

    /**
     * Remove quantity from stock.
     *
     * IMPROVEMENT: Helper method to remove quantity with reservation check.
     */
    public function removeQuantity(int $quantity): bool
    {
        if ($quantity <= 0 || $this->quantity < $quantity) {
            return false;
        }

        $this->quantity = ($this->quantity ?? 0) - $quantity;
        $this->reserved_quantity = min($this->reserved_quantity ?? 0, $this->quantity);
        // available_quantity is auto-calculated by database

        return $this->save();
    }

    /**
     * Deduct quantity and reserve for assignment.
     *
     * IMPROVEMENT: Combined method for assignment workflow.
     */
    public function deductForAssignment(int $quantity): bool
    {
        if (! $this->isAvailable($quantity)) {
            return false;
        }

        // For assignment: quantity represents items given to student, so we deduct from available
        $this->quantity = ($this->quantity ?? 0) - $quantity;
        $this->reserved_quantity = ($this->reserved_quantity ?? 0) + $quantity;
        // available_quantity is auto-calculated by database as quantity - reserved_quantity

        return $this->save();
    }

    /**
     * Restore quantity from return.
     *
     * IMPROVEMENT: Combined method for return workflow.
     */
    public function restoreFromReturn(int $quantity): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        // When returning: add back to quantity and reduce reserved
        $this->quantity = ($this->quantity ?? 0) + $quantity;
        $this->reserved_quantity = max(0, ($this->reserved_quantity ?? 0) - $quantity);
        // available_quantity is auto-calculated by database

        return $this->save();
    }

    /**
     * Get formatted quantity.
     */
    public function getFormattedQuantity(): string
    {
        return number_format($this->quantity);
    }

    /**
     * Get formatted available quantity.
     */
    public function getFormattedAvailableQuantity(): string
    {
        return number_format($this->available_quantity);
    }

    /**
     * Get formatted reserved quantity.
     */
    public function getFormattedReservedQuantity(): string
    {
        return number_format($this->reserved_quantity ?? 0);
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for low stock.
     */
    public function scopeLowStock($query, int $threshold = self::DEFAULT_LOW_STOCK_THRESHOLD)
    {
        return $query->having('available_quantity', '<', $threshold);
    }

    /**
     * Scope for out of stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('available_quantity', '<=', 0);
    }

    /**
     * Scope for available stock.
     */
    public function scopeAvailable($query, int $quantity = 1)
    {
        return $query->where('available_quantity', '>=', $quantity);
    }

    /**
     * Scope for specific item.
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    /**
     * Scope for items with inventory type.
     */
    public function scopeWithInventoryType($query)
    {
        return $query->with(['inventoryItem:id,name,inventory_type_id', 'inventoryItem.inventoryType:id,name']);
    }

    /**
     * Get the inventory type ID if available.
     */
    public function getInventoryTypeId(): ?int
    {
        return $this->inventoryItem?->inventory_type_id;
    }

    /**
     * Get the inventory type name if available.
     */
    public function getInventoryTypeName(): ?string
    {
        return $this->inventoryItem?->inventoryType?->name;
    }
}
