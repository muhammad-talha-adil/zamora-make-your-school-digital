<?php

namespace App\Models\Exam;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * The exam types a school normally runs.
     *
     * @var array<int, string>
     */
    public const DEFAULT_NAMES = ['Mid Term', 'Final', 'Monthly Test', 'Quiz'];

    /**
     * Adds any usual exam type the school does not have yet.
     *
     * Safe to run again: a type that exists, active or not, is left alone.
     *
     * @return int how many were added
     */
    public static function ensureDefaults(): int
    {
        $added = 0;

        foreach (self::DEFAULT_NAMES as $name) {
            if (self::withTrashed()->where('name', $name)->exists()) {
                continue;
            }

            self::create(['name' => $name, 'is_active' => true]);
            $added++;
        }

        return $added;
    }

    /**
     * Get all exams for this exam type.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
