<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentInventory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_inventory_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_inventory_id',
        'campus_id',
        'student_id',
        'total_amount',
        'total_discount',
        'final_amount',
        'assigned_date',
        'status',
        'invoice_id',
        'student_class_id',
        'student_section_id',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'assigned_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_PARTIAL_RETURN = 'partial_return';

    public const STATUS_RETURNED = 'returned';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($studentInventory) {
            // Generate student_inventory_id if not set
            if (empty($studentInventory->student_inventory_id)) {
                $studentInventory->student_inventory_id = static::generateStudentInventoryId();
            }
        });
    }

    /**
     * Generate a unique student inventory ID.
     */
    public static function generateStudentInventoryId(): string
    {
        $year = date('Y');

        // Get the last student inventory for this year
        $lastInventory = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInventory && preg_match('/SI-\d{4}-(\d+)/', $lastInventory->student_inventory_id, $matches)) {
            $counter = intval($matches[1]) + 1;
        } else {
            $counter = 1;
        }

        return 'SI-'.$year.'-'.str_pad($counter, 4, '0', STR_PAD_LEFT);
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
     * Get the inventory items for this student inventory.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StudentInventoryItem::class, 'student_inventory_record_id');
    }

    /**
     * Get the inventory items (alias for items).
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(StudentInventoryItem::class, 'student_inventory_record_id');
    }

    /**
     * Get the first inventory item (for backward compatibility).
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'student_class_id');
    }

    /**
     * Get the student's section at time of assignment.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'student_section_id');
    }

    /**
     * Get the invoice for this student inventory.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get total quantity assigned.
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Get total quantity returned.
     */
    public function getTotalReturnedQuantityAttribute(): int
    {
        return $this->items()->sum('returned_quantity');
    }

    /**
     * Get remaining quantity to return.
     */
    public function remainingQuantity(): int
    {
        return max(0, $this->getTotalQuantityAttribute() - $this->getTotalReturnedQuantityAttribute());
    }

    /**
     * Check if item is fully returned.
     */
    public function isFullyReturned(): bool
    {
        return $this->remainingQuantity() <= 0;
    }

    /**
     * Check if item has any returns.
     */
    public function hasReturns(): bool
    {
        return $this->getTotalReturnedQuantityAttribute() > 0;
    }

    /**
     * Get all return records for this inventory.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnModel::class, 'student_inventory_id');
    }

    /**
     * Scope for assigned status.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', self::STATUS_ASSIGNED);
    }

    /**
     * Scope for partial return status.
     */
    public function scopePartialReturn($query)
    {
        return $query->where('status', self::STATUS_PARTIAL_RETURN);
    }

    /**
     * Scope for fully returned status.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    /**
     * Scope for pending returns (assigned or partial).
     */
    public function scopePendingReturn($query)
    {
        return $query->whereIn('status', [self::STATUS_ASSIGNED, self::STATUS_PARTIAL_RETURN]);
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for student.
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->when($fromDate, fn ($q) => $q->whereDate('assigned_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('assigned_date', '<=', $toDate));
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_PARTIAL_RETURN => 'Partially Returned',
            self::STATUS_RETURNED => 'Fully Returned',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'blue',
            self::STATUS_PARTIAL_RETURN => 'yellow',
            self::STATUS_RETURNED => 'green',
            default => 'gray',
        };
    }

    /**
     * Get the first item (for backward compatibility).
     */
    public function getFirstItem(): ?StudentInventoryItem
    {
        return $this->items->first();
    }

    /**
     * Get discount info for the first item.
     */
    public function getDiscountInfo(): array
    {
        $firstItem = $this->getFirstItem();
        if (! $firstItem) {
            return [
                'has_discount' => false,
                'discount_amount' => 0,
                'discount_percentage' => 0,
                'discount_per_unit' => 0,
                'total_discount' => 0,
                'original_price' => 0,
                'final_price' => 0,
            ];
        }

        return [
            'has_discount' => $firstItem->hasDiscount(),
            'discount_amount' => $firstItem->discount_amount,
            'discount_percentage' => $firstItem->discount_percentage,
            'discount_per_unit' => $firstItem->getDiscountPerUnit(),
            'total_discount' => $firstItem->getDiscountPerUnit() * $firstItem->quantity,
            'original_price' => $firstItem->unit_price_snapshot,
            'final_price' => $firstItem->getFinalUnitPrice(),
        ];
    }

    /**
     * Get total value (computed from items).
     */
    public function totalValue(): float
    {
        return $this->items->sum(fn ($item) => $item->totalValue());
    }

    /**
     * Get remaining value.
     */
    public function remainingValue(): float
    {
        return $this->items->sum(fn ($item) => $item->remainingQuantity() * $item->getFinalUnitPrice());
    }

    /**
     * Check if has discount (checks first item).
     */
    public function hasDiscount(): bool
    {
        return $this->items->contains(fn ($item) => $item->hasDiscount());
    }

    /**
     * Get original unit price from first item.
     */
    public function getOriginalUnitPrice(): float
    {
        return (float) ($this->getFirstItem()?->unit_price_snapshot ?? 0);
    }

    /**
     * Get discount per unit from first item.
     */
    public function getDiscountPerUnit(): float
    {
        return $this->getFirstItem()?->getDiscountPerUnit() ?? 0;
    }

    /**
     * Get final unit price from first item.
     */
    public function getFinalUnitPrice(): float
    {
        return $this->getFirstItem()?->getFinalUnitPrice() ?? 0;
    }

    /**
     * Get total discount amount.
     */
    public function getTotalDiscountAmount(): float
    {
        return $this->items->sum(fn ($item) => $item->getDiscountPerUnit() * $item->quantity);
    }

    /**
     * Get total original value.
     */
    public function getTotalOriginalValue(): float
    {
        return $this->items->sum(fn ($item) => $item->getTotalOriginalValue());
    }

    /**
     * Get item name snapshot from first item.
     */
    public function getItemNameSnapshotAttribute(): ?string
    {
        return $this->getFirstItem()?->item_name_snapshot;
    }

    /**
     * Get description snapshot from first item.
     */
    public function getDescriptionSnapshotAttribute(): ?string
    {
        return $this->getFirstItem()?->description_snapshot;
    }

    /**
     * Get unit price snapshot from first item.
     */
    public function getUnitPriceSnapshotAttribute(): ?float
    {
        return $this->getFirstItem()?->unit_price_snapshot;
    }

    /**
     * Get quantity from first item.
     */
    public function getQuantityAttribute(): int
    {
        return $this->getFirstItem()?->quantity ?? 0;
    }

    /**
     * Get returned quantity from first item.
     */
    public function getReturnedQuantityAttribute(): int
    {
        return $this->getFirstItem()?->returned_quantity ?? 0;
    }

    /**
     * Get inventory item id from first item.
     */
    public function getInventoryItemIdAttribute(): ?int
    {
        return $this->getFirstItem()?->inventory_item_id;
    }

    /**
     * Get discount amount from first item.
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->getFirstItem()?->discount_amount ?? 0;
    }

    /**
     * Get discount percentage from first item.
     */
    public function getDiscountPercentageAttribute(): float
    {
        return $this->getFirstItem()?->discount_percentage ?? 0;
    }

    /**
     * Calculate total profit from all items in this inventory assignment.
     * Profit = (Sale Price - Purchase Rate) × Quantity for each item.
     */
    public function getTotalProfit(): float
    {
        return $this->items->sum(fn ($item) => $item->getProfit());
    }

    /**
     * Calculate total cost of goods sold for all items.
     */
    public function getTotalCostOfGoodsSold(): float
    {
        return $this->items->sum(fn ($item) => $item->getCostOfGoodsSold());
    }

    /**
     * Calculate total revenue from this inventory assignment.
     */
    public function getTotalRevenue(): float
    {
        return $this->items->sum(fn ($item) => $item->getRevenue());
    }

    /**
     * Get average profit margin percentage across all items.
     */
    public function getAverageProfitMargin(): float
    {
        $totalCost = $this->getTotalCostOfGoodsSold();
        if ($totalCost <= 0) {
            return 0;
        }

        return ($this->getTotalProfit() / $totalCost) * 100;
    }

    /**
     * Check if this inventory assignment is profitable.
     */
    public function isProfitable(): bool
    {
        return $this->getTotalProfit() > 0;
    }

    /**
     * Get formatted total profit.
     */
    public function getFormattedTotalProfitAttribute(): string
    {
        return number_format($this->getTotalProfit(), 2);
    }

    /**
     * Get formatted total revenue.
     */
    public function getFormattedTotalRevenueAttribute(): string
    {
        return number_format($this->getTotalRevenue(), 2);
    }

    /**
     * Get formatted total cost of goods sold.
     */
    public function getFormattedTotalCogsAttribute(): string
    {
        return number_format($this->getTotalCostOfGoodsSold(), 2);
    }

    /**
     * Scope for profitable inventories.
     */
    public function scopeProfitable($query)
    {
        return $query->whereHas('items', function ($q) {
            $q->havingRaw('(SUM(unit_price_snapshot - COALESCE(purchase_rate_snapshot, 0)) * SUM(quantity)) > 0');
        });
    }

    /**
     * Scope for date range with profit calculation.
     */
    public function scopeDateRangeWithProfit($query, $fromDate, $toDate)
    {
        return $query->whereBetween('assigned_date', [$fromDate, $toDate]);
    }
}
