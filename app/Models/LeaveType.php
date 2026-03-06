<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all student leaves with this type.
     */
    public function studentLeaves(): HasMany
    {
        return $this->hasMany(StudentLeave::class);
    }

    /**
     * Scope for active leave types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
