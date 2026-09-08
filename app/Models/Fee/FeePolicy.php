<?php

namespace App\Models\Fee;

use App\Enums\Fee\ProrationMethod;
use App\Models\Campus;
use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The billing rules a campus sets for itself.
 *
 * Everything here is a school's own decision rather than a rule of the system:
 * whether a child admitted on the 25th pays a full month, whether an admission
 * fee comes back if they leave in a fortnight, whether the sibling concession
 * applies itself.
 */
class FeePolicy extends Model
{
    protected $fillable = [
        'campus_id',
        'session_id',
        'proration_method',
        'proration_cutoff_day',
        'one_time_refund_days',
        'sibling_discount_enabled',
        'notes',
        'is_active',
    ];

    /**
     * Matched to the column defaults, so the unsaved policy `resolve()` hands
     * back for a campus that has set nothing bills exactly as before.
     */
    protected $attributes = [
        'proration_method' => 'none',
        'proration_cutoff_day' => 16,
        'one_time_refund_days' => 0,
        'sibling_discount_enabled' => false,
        'is_active' => true,
    ];

    protected $casts = [
        'proration_method' => ProrationMethod::class,
        'proration_cutoff_day' => 'integer',
        'one_time_refund_days' => 'integer',
        'sibling_discount_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The policy in force for a campus, in a session.
     *
     * A row naming the session wins over the campus default, so a change made
     * this year does not rewrite how last year was billed. Returns an unsaved
     * model when the campus has set nothing, whose defaults leave billing
     * exactly as it was before policies existed.
     */
    public static function resolve(?int $campusId, ?int $sessionId = null): self
    {
        if (! $campusId) {
            return new self;
        }

        $policy = self::query()
            ->where('campus_id', $campusId)
            ->where('is_active', true)
            ->where(function ($query) use ($sessionId) {
                $query->where('session_id', $sessionId)
                    ->orWhereNull('session_id');
            })
            // A session-specific row sorts before the campus default.
            ->orderByRaw('session_id is null')
            ->first();

        return $policy ?? new self;
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * The share of a month's charge owed by a child admitted on this date.
     */
    public function shareOfMonthFor(\DateTimeInterface $admittedOn): float
    {
        $method = $this->proration_method ?? ProrationMethod::NONE;

        return $method->shareOfMonth($admittedOn, $this->proration_cutoff_day ?? 16);
    }

    public function prorates(): bool
    {
        return ($this->proration_method ?? ProrationMethod::NONE) !== ProrationMethod::NONE;
    }
}
