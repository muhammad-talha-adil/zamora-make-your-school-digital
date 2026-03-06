<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_adjustments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'inventory_item_id',
        'user_id',
        'type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'reason',
        'reference_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Adjustment type constants.
     */
    public const TYPE_ADD = 'add';
    public const TYPE_SUBTRACT = 'subtract';
    public const TYPE_SET = 'set';

    /**
     * Get the campus that owns the adjustment.
     */
    public function campus(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the inventory item for this adjustment.
     */
    public function inventoryItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the user who created this adjustment.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for item.
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    /**
     * Scope for adjustment type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->when($fromDate, fn($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('created_at', '<=', $toDate));
    }

    /**
     * Get type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ADD => 'Add Stock',
            self::TYPE_SUBTRACT => 'Subtract Stock',
            self::TYPE_SET => 'Set Quantity',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted quantity change.
     */
    public function getQuantityChange(): string
    {
        return match ($this->type) {
            self::TYPE_ADD => '+' . number_format($this->quantity),
            self::TYPE_SUBTRACT => '-' . number_format($this->quantity),
            self::TYPE_SET => number_format($this->quantity),
            default => number_format($this->quantity),
        };
    }

    /**
     * Check if this is a stock increase.
     */
    public function isIncrease(): bool
    {
        return $this->type === self::TYPE_ADD;
    }

    /**
     * Check if this is a stock decrease.
     */
    public function isDecrease(): bool
    {
        return $this->type === self::TYPE_SUBTRACT;
    }
}
