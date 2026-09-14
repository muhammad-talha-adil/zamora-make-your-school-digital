<?php

namespace App\Models;

use App\Http\Controllers\Settings\SubscriptionController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Subscription extends Model
{
    use LogsActivity;

    /**
     * Every status/expiry change to the installation's own access lock.
     *
     * {@see SubscriptionController::update()}
     * used to write this to the file log by hand; now that every change is
     * also a database row, the manual `Log::info()` call there has been
     * removed to avoid saying the same thing twice.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'block_reason', 'block_reason_note', 'demo_expires_at', 'subscription_expires_at', 'notes'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => "subscription {$eventName}");
    }

    protected $fillable = [
        'status',
        'block_reason',
        'block_reason_note',
        'demo_expires_at',
        'subscription_expires_at',
        'notes',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'demo_expires_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The single subscription row for this installation, created with a safe
     * default (active, no expiry) the first time anything asks for it.
     *
     * Never deletes or otherwise touches existing data — it only ever reads
     * or, via {@see updateFrom()}, updates this one row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'status' => 'active',
        ]);
    }

    /**
     * Whether access should be blocked for anyone but a developer right now.
     */
    public function isBlocking(): bool
    {
        return match ($this->status) {
            'suspended' => true,
            'expired' => true,
            'demo' => $this->demo_expires_at !== null && $this->demo_expires_at->isPast(),
            'active' => $this->subscription_expires_at !== null && $this->subscription_expires_at->isPast(),
            default => false,
        };
    }

    /**
     * The expiry date that currently governs access, if any.
     */
    public function activeExpiry(): ?Carbon
    {
        return match ($this->status) {
            'demo' => $this->demo_expires_at,
            'active' => $this->subscription_expires_at,
            default => null,
        };
    }

    /**
     * Days remaining until {@see activeExpiry()}, or null when there is no
     * governing expiry (lifetime, or already suspended/expired).
     */
    public function daysUntilExpiry(): ?int
    {
        $expiry = $this->activeExpiry();

        if ($expiry === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false);
    }
}
