<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'code',
        'description',
        'is_active',
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

    protected static function booted(): void
    {
        static::creating(function (Subject $subject): void {
            if (blank($subject->code)) {
                $subject->code = self::generateCode($subject->name);
            }

            if (blank($subject->short_name)) {
                $subject->short_name = self::generateShortName($subject->name);
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
        $base = $base->isEmpty() ? 'SUB' : (string) $base;

        $code = $base;
        $suffix = 1;

        while (self::withTrashed()->where('code', $code)->exists()) {
            $code = $base.'_'.(++$suffix);
        }

        return $code;
    }

    /**
     * A short display abbreviation derived from the name — initials for a
     * multi-word name (e.g. "Computer Science" -> "CS"), otherwise the first
     * few letters (e.g. "Mathematics" -> "MATH").
     */
    public static function generateShortName(string $name): string
    {
        $words = Str::of($name)->squish()->explode(' ')->filter();

        $base = $words->count() > 1
            ? $words->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))->implode('')
            : Str::upper(Str::substr($words->first() ?? $name, 0, 4));

        $base = $base === '' ? 'SUB' : $base;

        $shortName = $base;
        $suffix = 1;

        while (self::withTrashed()->where('short_name', $shortName)->exists()) {
            $shortName = $base.(++$suffix);
        }

        return $shortName;
    }

    /**
     * The classes that belong to this subject.
     */
    public function schoolClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'subject_id', 'class_id');
    }
}
