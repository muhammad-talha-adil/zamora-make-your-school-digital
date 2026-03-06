<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentInventoryReturnItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_inventory_return_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'return_id',
        'item_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'total_amount',
        'item_snapshot',
        'reason_id',
        'custom_reason',
        'return_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'return_price' => 'decimal:2',
        'item_snapshot' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reason_id' => 'integer',
    ];

    /**
     * Get the parent return.
     */
    public function returnRecord(): BelongsTo
    {
        return $this->belongsTo(StudentInventoryReturn::class, 'return_id');
    }

    /**
     * Get the student inventory item.
     */
    public function studentInventoryItem(): BelongsTo
    {
        return $this->belongsTo(StudentInventoryItem::class, 'item_id');
    }

    /**
     * Get the inventory item.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
