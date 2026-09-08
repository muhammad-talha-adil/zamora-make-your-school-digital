<?php

namespace App\Models\Exam;

use App\Models\Campus;
use App\Models\Session;
use App\Services\Exam\GradeResolver;
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
     * The active grade scale, ignoring campus and session.
     *
     * @deprecated Use `App\Services\Exam\GradeResolver::systemFor()`.
     *
     * This table has always carried `campus_id` and `session_id`, and this
     * method read neither: a multi-campus school got whichever row the database
     * returned first, and last year's scale was applied to this year's results.
     * It is kept only so an older screen does not fatal; nothing in the exam
     * module calls it any more.
     */
    public static function getActiveGradeSystem()
    {
        return app(GradeResolver::class)->systemFor();
    }
}
