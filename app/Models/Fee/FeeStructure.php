<?php

namespace App\Models\Fee;

use App\Enums\Fee\FeeStructureStatus;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fee Structure Model
 * 
 * Header/master fee plan for a campus/session/class/section scope.
 */
class FeeStructure extends Model
{
    use SoftDeletes;

    protected $table = 'fee_structures';

    protected $fillable = [
        'title',
        'session_id',
        'campus_id',
        'class_id',
        'section_id',
        'is_default',
        'effective_from',
        'effective_to',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'status' => FeeStructureStatus::class,
    ];

    /**
     * Get the session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the campus
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the class (nullable)
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the class (alias for schoolClass)
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the section (nullable)
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get structure items
     */
    public function items(): HasMany
    {
        return $this->hasMany(FeeStructureItem::class);
    }

    /**
     * Scope: Active structures
     */
    public function scopeActive($query)
    {
        return $query->where('status', FeeStructureStatus::ACTIVE);
    }

    /**
     * Scope: By session
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: By campus
     */
    public function scopeForCampus($query, int $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    /**
     * Scope: By class
     */
    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope: By section
     */
    public function scopeForSection($query, int $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Scope: Default structures
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Effective on date
     */
    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * Check if the fee structure is effective on a given date
     */
    public function isEffectiveOn($date): bool
    {
        $date = $date instanceof \Carbon\Carbon ? $date->toDateString() : $date;
        
        if ($this->effective_from && $this->effective_from->toDateString() > $date) {
            return false;
        }
        
        if ($this->effective_to && $this->effective_to->toDateString() < $date) {
            return false;
        }
        
        return true;
    }
}
