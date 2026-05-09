<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffDesignation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class, 'designation_id');
    }
}
