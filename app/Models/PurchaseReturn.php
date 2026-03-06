<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchase_returns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'campus_id',
        'purchase_id',
        'supplier_id',
        'user_id',
        'purchase_return_id',
        'return_number',
        'return_date',
        'total_amount',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
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
     * Get the original purchase.
     */
    public function purchase(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the supplier.
     */
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who created this return.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the return items.
     */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /**
     * Get the inventory items through return items.
     */
    public function inventoryItems(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'purchase_return_items')
            ->withPivot(['quantity', 'unit_price', 'total', 'item_snapshot', 'reason'])
            ->using(PurchaseReturnItem::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            // Generate purchase_return_id if not set
            if (empty($return->purchase_return_id)) {
                $return->purchase_return_id = static::generatePurchaseReturnId();
            }
        });
    }

    /**
     * Generate a unique purchase return ID.
     */
    public static function generatePurchaseReturnId(): string
    {
        $year = date('Y');
        
        // Get the last return for this year
        $lastReturn = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReturn && preg_match('/RET-\d{4}-(\d+)/', $lastReturn->purchase_return_id, $matches)) {
            $counter = intval($matches[1]) + 1;
        } else {
            $counter = 1;
        }

        return 'RET-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate return number.
     */
    public static function generateReturnNumber(Campus $campus): string
    {
        $prefix = 'RET';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastReturn = self::where('campus_id', $campus->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->max('id');
        
        $sequence = str_pad(($lastReturn ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        
        return sprintf('%s-%s%s-%s', $prefix, $year, $month, $sequence);
    }

    /**
     * Scope for campus.
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope for supplier.
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
        return $query->when($fromDate, fn($q) => $q->whereDate('return_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('return_date', '<=', $toDate));
    }

    /**
     * Calculate total from items.
     */
    public function recalculateTotal(): float
    {
        return $this->items()->sum('total');
    }
}
