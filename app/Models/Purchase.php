<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Payment status constants.
     */
    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    public const PAYMENT_STATUS_PARTIAL = 'partial';

    public const PAYMENT_STATUS_PAID = 'paid';

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
        'paid_amount',
        'payment_status',
        'payment_date',
        'due_date',
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
        'paid_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
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

        return 'PR-'.$year.'-'.str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the campus that owns the purchase.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the supplier for this purchase.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the purchase items for this purchase.
     */
    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Get the inventory items for this purchase.
     */
    public function inventoryItems(): BelongsToMany
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
        if (! $this->isCancelled()) {
            return null;
        }

        // Parse cancellation reason from note
        if (preg_match('/\[CANCELLED\].*?:\s*(.*)/i', $this->note ?? '', $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get the payments for this purchase.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /**
     * Get remaining amount to be paid.
     */
    public function getDueAmountAttribute(): float
    {
        return max(0, ($this->total_amount ?? 0) - ($this->paid_amount ?? 0));
    }

    /**
     * Check if purchase is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return ($this->paid_amount ?? 0) >= ($this->total_amount ?? 0);
    }

    /**
     * Check if purchase is unpaid.
     */
    public function isUnpaid(): bool
    {
        return ($this->paid_amount ?? 0) <= 0;
    }

    /**
     * Check if purchase is partially paid.
     */
    public function isPartialPaid(): bool
    {
        return ! $this->isFullyPaid() && ! $this->isUnpaid();
    }

    /**
     * Check if purchase is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return ! $this->isFullyPaid() && $this->due_date->isPast();
    }

    /**
     * Get payment status label.
     */
    public function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PAID => 'Paid',
            self::PAYMENT_STATUS_PARTIAL => 'Partial',
            self::PAYMENT_STATUS_UNPAID => 'Unpaid',
            default => 'Unknown',
        };
    }

    /**
     * Update payment status based on paid amount.
     */
    public function updatePaymentStatus(): void
    {
        if ($this->isFullyPaid()) {
            $this->payment_status = self::PAYMENT_STATUS_PAID;
            if (! $this->payment_date) {
                $this->payment_date = now();
            }
        } elseif (($this->paid_amount ?? 0) > 0) {
            $this->payment_status = self::PAYMENT_STATUS_PARTIAL;
        } else {
            $this->payment_status = self::PAYMENT_STATUS_UNPAID;
        }
        $this->save();
    }

    /**
     * Record a payment for this purchase.
     */
    public function recordPayment(float $amount, ?string $paymentMode = null, ?string $reference = null): PurchasePayment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'payment_mode' => $paymentMode,
            'reference_number' => $reference,
            'payment_date' => now(),
        ]);

        // Update paid_amount
        $this->paid_amount = ($this->paid_amount ?? 0) + $amount;
        $this->updatePaymentStatus();

        return $payment;
    }

    /**
     * Get total purchase cost (sum of all items' purchase_rate * quantity).
     */
    public function getTotalCostAttribute(): float
    {
        return $this->purchaseItems()->sum('total');
    }

    /**
     * Scope for unpaid purchases.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_UNPAID);
    }

    /**
     * Scope for partial payments.
     */
    public function scopePartial($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PARTIAL);
    }

    /**
     * Scope for fully paid purchases.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    /**
     * Scope for overdue purchases.
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_status', '!=', self::PAYMENT_STATUS_PAID)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now());
    }

    /**
     * Scope for purchases by supplier.
     */
    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $fromDate, $toDate)
    {
        return $query->when($fromDate, fn ($q) => $q->whereDate('purchase_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('purchase_date', '<=', $toDate));
    }
}
