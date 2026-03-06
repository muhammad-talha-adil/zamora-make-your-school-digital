<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cnic',
        'phone',
        'occupation',
        'address',
    ];

    protected $casts = [
        'phone' => 'string',
        'cnic' => 'string',
        'occupation' => 'string',
        'address' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardians')
            ->withPivot(['id', 'relation_id', 'is_primary'])
            ->withTimestamps();
    }

    public function studentGuardians()
    {
        return $this->hasMany(StudentGuardian::class);
    }
}
