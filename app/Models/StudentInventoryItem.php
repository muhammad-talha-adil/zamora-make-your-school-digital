<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentInventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_inventory_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_inventory_record_id',
        'campus_id',
        'student_id',
        'inventory_item_id',
        'quantity',
        'returned_quantity',
        'unit_price_snapshot',
        'purchase_rate_snapshot',
        'item_name_snapshot',
        'description_snapshot',
        'discount_amount',
        'discount_percentage',
        'assigned_date',
        'status',
        'invoice_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
        'purchase_rate_snapshot' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'assigned_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the student inventory record that owns this item.
     */
    public function studentInventoryRecord(): BelongsTo
    {
        return $this->belongsTo(StudentInventory::class, 'student_inventory_record_id');
    }

    /**
     * Get the campus that owns the student inventory.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the student that owns the inventory.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the inventory item for this student inventory.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the invoice for this student inventory.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate remaining quantity to return.
     */
    public function remainingQuantity(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    /**
     * Get the original unit price before discount.
     */
    public function getOriginalUnitPrice(): float
    {
        return (float) $this->unit_price_snapshot;
    }

    /**
     * Calculate discount amount per unit.
     */
    public function getDiscountPerUnit(): float
    {
        if ($this->discount_percentage > 0) {
            return $this->unit_price_snapshot * ($this->discount_percentage / 100);
        }

        return (float) ($this->discount_amount ?? 0);
    }

    /**
     * Get the final unit price after discount.
     */
    public function getFinalUnitPrice(): float
    {
        return $this->unit_price_snapshot - $this->getDiscountPerUnit();
    }

    /**
     * Calculate total value before discount.
     */
    public function getTotalOriginalValue(): float
    {
        return $this->quantity * $this->unit_price_snapshot;
    }

    /**
     * Calculate total value after discount.
     */
    public function totalValue(): float
    {
        return $this->quantity * $this->getFinalUnitPrice();
    }

    /**
     * Check if has discount.
     */
    public function hasDiscount(): bool
    {
        return $this->discount_amount > 0 || $this->discount_percentage > 0;
    }

    /**
     * Calculate profit for this item (Sale Price - Purchase Rate) × Quantity.
     * This shows the profit made from selling this item to student.
     */
    public function getProfit(): float
    {
        $purchaseRate = (float) ($this->purchase_rate_snapshot ?? 0);
        $salePrice = $this->getFinalUnitPrice();

        return ($salePrice - $purchaseRate) * $this->quantity;
    }

    /**
     * Get profit per unit.
     */
    public function getProfitPerUnit(): float
    {
        $purchaseRate = (float) ($this->purchase_rate_snapshot ?? 0);
        $salePrice = $this->getFinalUnitPrice();

        return $salePrice - $purchaseRate;
    }

    /**
     * Check if this item is sold at a profit.
     */
    public function isProfitable(): bool
    {
        return $this->getProfitPerUnit() > 0;
    }

    /**
     * Calculate cost of goods sold for this item.
     */
    public function getCostOfGoodsSold(): float
    {
        $purchaseRate = (float) ($this->purchase_rate_snapshot ?? 0);

        return $purchaseRate * $this->quantity;
    }

    /**
     * Calculate revenue from this item.
     */
    public function getRevenue(): float
    {
        return $this->quantity * $this->getFinalUnitPrice();
    }

    /**
     * Get profit margin percentage.
     */
    public function getProfitMarginPercentage(): float
    {
        $purchaseRate = (float) ($this->purchase_rate_snapshot ?? 0);
        if ($purchaseRate <= 0) {
            return 0;
        }

        $profitPerUnit = $this->getProfitPerUnit();

        return ($profitPerUnit / $purchaseRate) * 100;
    }

    /**
     * Get formatted profit.
     */
    public function getFormattedProfitAttribute(): string
    {
        return number_format($this->getProfit(), 2);
    }

    /**
     * Get formatted revenue.
     */
    public function getFormattedRevenueAttribute(): string
    {
        return number_format($this->getRevenue(), 2);
    }

    /**
     * Get formatted cost of goods sold.
     */
    public function getFormattedCogsAttribute(): string
    {
        return number_format($this->getCostOfGoodsSold(), 2);
    }
}
