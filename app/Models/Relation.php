<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Relation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }
}
