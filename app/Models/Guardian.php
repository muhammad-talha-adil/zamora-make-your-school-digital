<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /**
     * @return BelongsTo<User, $this>
     */
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

    /**
     * @return HasMany<StudentGuardian, $this>
     */
    public function studentGuardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }
}
