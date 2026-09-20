<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'level',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SchoolClass $schoolClass): void {
            if (blank($schoolClass->code)) {
                $schoolClass->code = self::generateCode($schoolClass->name);
            }
        });
    }

    /**
     * A short, unique reference code derived from the name — office staff never
     * type one, but reports still get something stable to key on.
     */
    public static function generateCode(string $name): string
    {
        $base = Str::of($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->limit(20, '');
        $base = $base->isEmpty() ? 'CLS' : (string) $base;

        $code = $base;
        $suffix = 1;

        while (self::withTrashed()->where('code', $code)->exists()) {
            $code = $base.'_'.(++$suffix);
        }

        return $code;
    }

    /**
     * Get the sections that belong to this class.
     */
    public function sections()
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    /**
     * The subjects that belong to this class.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject');
    }
}
