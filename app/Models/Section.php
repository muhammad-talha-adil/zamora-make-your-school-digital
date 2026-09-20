<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Section extends Model
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
        'description',
        'is_active',
        'class_id',
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
        static::creating(function (Section $section): void {
            if (blank($section->code)) {
                $section->code = self::generateCode($section->name, $section->class_id);
            }
        });
    }

    /**
     * A short reference code derived from the name — unique per class only,
     * since "Section A" legitimately exists in Class 1 and Class 2 at once.
     */
    public static function generateCode(string $name, ?int $classId): string
    {
        $base = Str::of($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->limit(20, '');
        $base = $base->isEmpty() ? 'SEC' : (string) $base;

        $code = $base;
        $suffix = 1;

        while (self::withTrashed()->where('class_id', $classId)->where('code', $code)->exists()) {
            $code = $base.'_'.(++$suffix);
        }

        return $code;
    }

    /**
     * Get the school class that owns the section.
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
