<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * A read-only view over every {@see Activity} row the traited models write.
 *
 * A school-wide audit trail is sensitive - it can show exactly how much a
 * family paid, who changed a salary, or who unlocked an exam result - so
 * this is deliberately gated to the owner/developer tier the same way
 * {@see SubscriptionController} is, rather than reused for a campus admin.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeUser($request);

        $query = Activity::query()->with(['causer', 'subject'])->latest();

        if ($subjectType = $request->string('subject_type')->toString()) {
            $query->where('subject_type', $subjectType);
        }

        if ($causerId = $request->integer('causer_id')) {
            $query->where('causer_id', $causerId)->where('causer_type', User::class);
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $activities = $query->paginate(25)->withQueryString();

        $activities->through(fn (Activity $activity): array => [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type ? Str::afterLast($activity->subject_type, '\\') : null,
            'subject_id' => $activity->subject_id,
            'causer_name' => $activity->causer?->name,
            'changes' => $activity->attribute_changes?->toArray(),
            'created_at' => $activity->created_at?->toIso8601String(),
        ]);

        return Inertia::render('settings/ActivityLog', [
            'activities' => $activities,
            'filters' => $request->only(['subject_type', 'causer_id', 'from', 'to']),
            'subjectTypes' => $this->availableSubjectTypes(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function authorizeUser(Request $request): void
    {
        if (! $request->user()?->hasAnyRole(['developer', 'owner'])) {
            abort(403, 'Unauthorized action. You do not have permission to access this resource.');
        }
    }

    /**
     * The distinct subject classes actually present in the log, offered as
     * `[value => label]` for the filter dropdown - cheap because the log
     * table is small and the column is indexed.
     *
     * @return array<string, string>
     */
    private function availableSubjectTypes(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $type): array => [$type => Str::afterLast($type, '\\')])
            ->all();
    }
}
