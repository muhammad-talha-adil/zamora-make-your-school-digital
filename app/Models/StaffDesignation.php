<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffDesignation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'role',
        'permission_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permission_template' => 'array',
    ];

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class, 'designation_id');
    }
}
