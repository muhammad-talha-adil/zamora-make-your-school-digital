<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_purchase_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_id',
        'inventory_item_id',
        'quantity',
        'purchase_rate',
        'sale_rate',
        'total',
        'item_snapshot', // IMPROVEMENT: Added snapshot field for audit
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'purchase_rate' => 'decimal:2',
        'sale_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'item_snapshot' => 'array', // IMPROVEMENT: Cast snapshot as array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the purchase that owns this item.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the inventory item for this purchase item.
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the snapshot item name.
     */
    public function getSnapshotItemName(): ?string
    {
        return $this->item_snapshot['item_name'] ?? null;
    }

    /**
     * Get the snapshot purchase rate.
     */
    public function getSnapshotPurchaseRate(): ?float
    {
        return $this->item_snapshot['purchase_rate'] ?? null;
    }

    /**
     * Get the snapshot captured at.
     */
    public function getSnapshotCapturedAt(): ?string
    {
        return $this->item_snapshot['captured_at'] ?? null;
    }

    /**
     * Check if snapshot is stale (item has been updated).
     */
    public function isSnapshotStale(): bool
    {
        if (! $this->item_snapshot) {
            return false;
        }

        // Rates are now stored in the purchase item itself, not in inventory item
        return false;
    }
}
