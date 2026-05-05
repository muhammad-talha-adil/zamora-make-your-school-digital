<?php

namespace App\Models\Exam;

use App\Models\Campus;
use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeSystem extends Model
{
    protected $fillable = [
        'name',
        'campus_id',
        'session_id',
        'rounding_mode',
        'precision',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'precision' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get all grade items for this grade system.
     */
    public function gradeSystemItems(): HasMany
    {
        return $this->hasMany(GradeSystemItem::class)->orderBy('min_percentage', 'desc');
    }

    /**
     * Get the campus that owns this grade system.
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    /**
     * Get the session that owns this grade system.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Get the active grade scale (only one active at a time).
     */
    public static function getActiveGradeSystem()
    {
        return static::where('is_active', true)->first();
    }
}
