<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slogan',
        'address',
        'phone',
        'bank_name',
        'bank_account_title',
        'bank_account_no',
        'bank_branch',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }
}
