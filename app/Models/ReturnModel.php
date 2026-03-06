<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_returns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'student_inventory_id',
        'quantity',
        'return_date',
        'note',
        'item_snapshot',
        'item_name_snapshot',
        'description_snapshot',
        'unit_price_snapshot',
        'discount_snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'return_date' => 'date',
        'item_snapshot' => 'array',
        'discount_snapshot' => 'array',
        'unit_price_snapshot' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the campus that owns the return.
     */
    public function campus(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the student inventory that owns the return.
     */
    public function studentInventory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StudentInventory::class, 'student_inventory_id');
    }

    /**
     * Get the associated inventory item through student inventory.
     */
    public function inventoryItem(): ?InventoryItem
    {
        return $this->studentInventory?->inventoryItem();
    }

    /**
     * Get the associated student through student inventory.
     */
    public function student(): ?Student
    {
        return $this->studentInventory?->student();
    }

    /**
     * Get the associated inventory stock.
     */
    public function inventoryStock(): ?InventoryStock
    {
        if (!$this->studentInventory) {
            return null;
        }

        return InventoryStock::where('campus_id', $this->campus_id)
            ->where('inventory_item_id', $this->studentInventory->inventory_item_id)
            ->first();
    }

    /**
     * Get the item name from snapshot or student inventory.
     */
    public function getItemName(): ?string
    {
        return $this->item_name_snapshot
            ?? $this->item_snapshot['item_name'] ?? null
            ?? $this->studentInventory?->item_name_snapshot;
    }

    /**
     * Get the description from snapshot.
     */
    public function getDescription(): ?string
    {
        return $this->description_snapshot
            ?? $this->item_snapshot['description'] ?? null
            ?? $this->studentInventory?->description_snapshot;
    }

    /**
     * Get the unit price from snapshot.
     */
    public function getUnitPrice(): ?float
    {
        return $this->unit_price_snapshot
            ?? $this->item_snapshot['unit_price'] ?? null
            ?? $this->studentInventory?->unit_price_snapshot;
    }

    /**
     * Get discount info from snapshot.
     */
    public function getDiscountSnapshot(): ?array
    {
        return $this->discount_snapshot
            ?? $this->item_snapshot['discount'] ?? null;
    }

    /**
     * Get the final unit price (after discount if applicable).
     */
    public function getFinalUnitPrice(): float
    {
        $unitPrice = $this->getUnitPrice() ?? 0;
        $discountSnapshot = $this->getDiscountSnapshot();

        if ($discountSnapshot) {
            $discountAmount = $discountSnapshot['discount_amount'] ?? 0;
            $discountPercentage = $discountSnapshot['discount_percentage'] ?? 0;

            if ($discountPercentage > 0) {
                $unitPrice -= $unitPrice * ($discountPercentage / 100);
            } else {
                $unitPrice -= $discountAmount;
            }
        }

        return max(0, $unitPrice);
    }

    /**
     * Calculate total value of this return.
     *
     * IMPROVEMENT: Calculate total return value.
     */
    public function getTotalValue(): float
    {
        return $this->quantity * $this->getFinalUnitPrice();
    }

    /**
     * Get the captured at timestamp from snapshot.
     */
    public function getCapturedAt(): ?string
    {
        return $this->item_snapshot['captured_at'] ?? null;
    }

    /**
     * Check if snapshot is stale.
     */
    public function isSnapshotStale(): bool
    {
        if (!$this->item_snapshot) {
            return false;
        }

        $currentName = $this->studentInventory?->inventoryItem?->name;
        $snapshotName = $this->item_snapshot['item_name'] ?? null;

        return $currentName !== $snapshotName;
    }

    /**
     * Check if this is a partial return.
     */
    public function isPartialReturn(): bool
    {
        if (!$this->studentInventory) {
            return false;
        }

        return $this->quantity < $this->studentInventory->remainingQuantity();
    }

    /**
     * Check if this completes the return.
     */
    public function completesReturn(): bool
    {
        if (!$this->studentInventory) {
            return false;
        }

        return $this->quantity >= $this->studentInventory->remainingQuantity();
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
        return $query->whereHas('studentInventory', fn($q) => $q->where('student_id', $studentId));
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->when($fromDate, fn($q) => $q->whereDate('return_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('return_date', '<=', $toDate));
    }

    /**
     * Scope for this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('return_date', now()->month);
    }

    /**
     * Scope for this year.
     */
    public function scopeThisYear($query)
    {
        return $query->whereYear('return_date', now()->year);
    }

    /**
     * Create return snapshot for audit.
     *
     * IMPROVEMENT: Static method to create comprehensive snapshot.
     */
    public static function createSnapshot(StudentInventory $studentInventory): array
    {
        return [
            'item_name' => $studentInventory->item_name_snapshot,
            'description' => $studentInventory->description_snapshot,
            'original_quantity' => $studentInventory->quantity,
            'unit_price' => $studentInventory->unit_price_snapshot,
            'purchase_rate' => $studentInventory->purchase_rate_snapshot,
            'assigned_date' => $studentInventory->assigned_date,
            'discount' => [
                'has_discount' => $studentInventory->hasDiscount(),
                'discount_amount' => $studentInventory->discount_amount,
                'discount_percentage' => $studentInventory->discount_percentage,
                'discount_per_unit' => $studentInventory->getDiscountPerUnit(),
                'total_discount' => $studentInventory->getTotalDiscountAmount(),
            ],
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get formatted return date.
     */
    public function getFormattedDate(): string
    {
        return $this->return_date->format('d M Y');
    }

    /**
     * Get formatted quantity.
     */
    public function getFormattedQuantity(): string
    {
        return number_format($this->quantity);
    }

    /**
     * Get formatted total value.
     */
    public function getFormattedTotalValue(): string
    {
        return number_format($this->getTotalValue(), 2);
    }

    /**
     * Get student info for response.
     */
    public function getStudentInfo(): array
    {
        return [
            'id' => $this->studentInventory?->student_id,
            'name' => $this->student?->name,
            'registration_number' => $this->student?->registration_number,
        ];
    }

    /**
     * Get item info for response.
     */
    public function getItemInfo(): array
    {
        return [
            'id' => $this->studentInventory?->inventory_item_id,
            'name' => $this->getItemName(),
            'description' => $this->getDescription(),
        ];
    }

    /**
     * Get discount info for response.
     */
    public function getDiscountInfo(): array
    {
        $discount = $this->getDiscountSnapshot();

        return [
            'has_discount' => $discount['has_discount'] ?? false,
            'discount_amount' => $discount['discount_amount'] ?? 0,
            'discount_percentage' => $discount['discount_percentage'] ?? 0,
            'total_discount' => $discount['total_discount'] ?? 0,
        ];
    }
}
