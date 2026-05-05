<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Inventory Valuation Model
 *
 * Tracks the value of inventory at different points in time.
 * Supports weighted average cost method for inventory valuation.
 */
class InventoryValuation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_valuations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'inventory_item_id',
        'valuation_date',
        'quantity',
        'unit_cost',
        'total_value',
        'valuation_method',
        'reference_type',
        'reference_id',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valuation_date' => 'date',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Valuation method constants.
     */
    public const METHOD_WEIGHTED_AVERAGE = 'weighted_average';

    public const METHOD_FIFO = 'fifo';

    public const METHOD_LIFO = 'lifo';

    public const METHOD_PURCHASE_cost = 'purchase_cost';

    public const METHOD_SALE_PRICE = 'sale_price';

    /**
     * Get the campus that owns the valuation.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the inventory item for this valuation.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get formatted total value.
     */
    public function getFormattedTotalValueAttribute(): string
    {
        return number_format($this->total_value, 2);
    }

    /**
     * Get formatted unit cost.
     */
    public function getFormattedUnitCostAttribute(): string
    {
        return number_format($this->unit_cost, 2);
    }

    /**
     * Get valuation method label.
     */
    public function getMethodLabelAttribute(): string
    {
        return match ($this->valuation_method) {
            self::METHOD_WEIGHTED_AVERAGE => 'Weighted Average',
            self::METHOD_FIFO => 'First In First Out (FIFO)',
            self::METHOD_LIFO => 'Last In First Out (LIFO)',
            self::METHOD_PURCHASE_cost => 'Purchase Cost',
            self::METHOD_SALE_PRICE => 'Sale Price',
            default => 'Unknown',
        };
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for specific item.
     */
    public function scopeForItem($query, int $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->when($fromDate, fn ($q) => $q->whereDate('valuation_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('valuation_date', '<=', $toDate));
    }

    /**
     * Scope for latest valuation.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('valuation_date', 'desc');
    }

    /**
     * Calculate weighted average cost for an item.
     *
     * Formula: (Total Quantity × Current Average) + (New Quantity × New Rate) / (Total Quantity + New Quantity)
     */
    public static function calculateWeightedAverage(int $campusId, int $itemId, int $newQuantity, float $newRate): float
    {
        $lastValuation = self::forCampus($campusId)
            ->forItem($itemId)
            ->latest()
            ->first();

        if (! $lastValuation) {
            return $newRate;
        }

        $totalQuantity = $lastValuation->quantity + $newQuantity;
        if ($totalQuantity <= 0) {
            return $newRate;
        }

        $totalValue = ($lastValuation->quantity * $lastValuation->unit_cost) + ($newQuantity * $newRate);

        return $totalValue / $totalQuantity;
    }

    /**
     * Create a new valuation record after purchase.
     */
    public static function createAfterPurchase(PurchaseItem $purchaseItem): self
    {
        $campusId = $purchaseItem->purchase->campus_id;
        $itemId = $purchaseItem->inventory_item_id;
        $quantity = $purchaseItem->quantity;
        $rate = (float) $purchaseItem->purchase_rate;

        // Calculate weighted average
        $weightedAvgRate = self::calculateWeightedAverage($campusId, $itemId, $quantity, $rate);

        return self::create([
            'campus_id' => $campusId,
            'inventory_item_id' => $itemId,
            'valuation_date' => now(),
            'quantity' => $quantity,
            'unit_cost' => $weightedAvgRate,
            'total_value' => $quantity * $weightedAvgRate,
            'valuation_method' => self::METHOD_WEIGHTED_AVERAGE,
            'reference_type' => 'purchase',
            'reference_id' => $purchaseItem->purchase_id,
        ]);
    }

    /**
     * Get current inventory value for a campus.
     */
    public static function getCurrentValue(int $campusId): float
    {
        $stocks = InventoryStock::forCampus($campusId)->get();

        $totalValue = 0;
        foreach ($stocks as $stock) {
            $latestValuation = self::forCampus($campusId)
                ->forItem($stock->inventory_item_id)
                ->latest()
                ->first();

            $unitCost = $latestValuation?->unit_cost ?? 0;
            $totalValue += $stock->quantity * $unitCost;
        }

        return $totalValue;
    }

    /**
     * Get current inventory value for a specific item.
     */
    public static function getItemCurrentValue(int $campusId, int $itemId): float
    {
        $stock = InventoryStock::forCampus($campusId)
            ->forItem($itemId)
            ->first();

        if (! $stock || $stock->quantity <= 0) {
            return 0;
        }

        $latestValuation = self::forCampus($campusId)
            ->forItem($itemId)
            ->latest()
            ->first();

        $unitCost = $latestValuation?->unit_cost ?? 0;

        return $stock->quantity * $unitCost;
    }
}
