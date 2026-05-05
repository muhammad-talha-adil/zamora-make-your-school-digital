<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoice_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'item_type',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'discount_percentage',
        'total_amount',
        'discount_snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'item_type' => 'string',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_snapshot' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the invoice that owns this item.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the student fee if this is a fee item.
     */
    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class, 'item_id')->where('item_type', 'fee');
    }

    /**
     * Get the student inventory if this is an inventory item.
     */
    public function studentInventory()
    {
        return $this->belongsTo(StudentInventory::class, 'item_id')->where('item_type', 'inventory');
    }

    /**
     * Get the polymorphic item.
     */
    public function item()
    {
        if ($this->item_type === 'fee') {
            return $this->studentFee();
        } elseif ($this->item_type === 'inventory') {
            return $this->studentInventory();
        }

        return null;
    }

    /**
     * Check if this item is a fee.
     */
    public function isFee(): bool
    {
        return $this->item_type === 'fee';
    }

    /**
     * Check if this item is an inventory.
     */
    public function isInventory(): bool
    {
        return $this->item_type === 'inventory';
    }

    /**
     * Get the discount snapshot as an array.
     */
    public function getDiscountInfo(): array
    {
        return $this->discount_snapshot ?? [
            'amount' => 0,
            'percentage' => 0,
        ];
    }
}
