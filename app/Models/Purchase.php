<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_purchases';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_id',
        'idempotency_key',
        'campus_id',
        'supplier_id',
        'purchase_date',
        'total_amount',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            // Generate purchase_id if not set
            if (empty($purchase->purchase_id)) {
                $purchase->purchase_id = static::generatePurchaseId();
            }
        });
    }

    /**
     * Generate a unique purchase ID.
     */
    public static function generatePurchaseId(): string
    {
        $year = date('Y');
        
        // Get the last purchase for this year
        $lastPurchase = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchase && preg_match('/PR-\\d{4}-(\\d+)/', $lastPurchase->purchase_id, $matches)) {
            $counter = intval($matches[1]) + 1;
        } else {
            $counter = 1;
        }

        return 'PR-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the campus that owns the purchase.
     */
    public function campus(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the supplier for this purchase.
     */
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase items for this purchase.
     */
    public function purchaseItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the inventory items for this purchase.
     */
    public function inventoryItems(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'inventory_purchase_items')
            ->withPivot(['quantity', 'purchase_rate', 'total', 'item_snapshot'])
            ->using(PurchaseItem::class);
    }

    /**
     * Calculate the total amount from purchase items.
     *
     * IMPROVEMENT: Helper method to recalculate total from items.
     */
    public function recalculateTotal(): float
    {
        return $this->purchaseItems()->sum('total');
    }

    /**
     * Check if purchase has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->trashed();
    }

    /**
     * Get cancellation reason if any.
     */
    public function getCancellationReason(): ?string
    {
        if (!$this->isCancelled()) {
            return null;
        }

        // Parse cancellation reason from note
        if (preg_match('/\[CANCELLED\].*?:\s*(.*)/i', $this->note ?? '', $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
