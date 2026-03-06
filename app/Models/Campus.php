<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'is_active',
        'campus_type_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the campus type that owns the campus.
     */
    public function campusType()
    {
        return $this->belongsTo(CampusType::class);
    }

    /**
     * Get the inventory types for this campus.
     */
    public function inventoryTypes()
    {
        return $this->hasMany(InventoryType::class);
    }

    /**
     * Get the inventory items for this campus.
     */
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get the inventory stocks for this campus.
     */
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Get the purchases for this campus.
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the student inventories for this campus.
     */
    public function studentInventories()
    {
        return $this->hasMany(StudentInventory::class);
    }

    /**
     * Get the returns for this campus.
     */
    public function returns()
    {
        return $this->hasMany(ReturnModel::class);
    }
}
