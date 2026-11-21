<?php

namespace App\Http\Controllers\Timetable;

use App\Http\Controllers\Concerns\ScopesCampusForUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\SaveTimetablePeriodRequest;
use App\Models\Campus;
use App\Models\Session;
use App\Models\Timetable\TimetablePeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The periods of the school day, per campus and session.
 */
class TimetablePeriodController extends Controller
{
    use ScopesCampusForUser;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TimetablePeriod::class);

        $user = $request->user();
        $campuses = Campus::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $campusId = $this->resolveCampusId($request) ?? $campuses->first()?->id;
        $sessions = Session::query()->orderByDesc('is_active')->orderByDesc('start_year')->get(['id', 'name', 'is_active']);
        $sessionId = $request->integer('session_id') ?: $sessions->first()?->id;

        $periods = TimetablePeriod::query()
            ->visibleTo($user)
            ->where('campus_id', $campusId)
            ->where('session_id', $sessionId)
            ->withCount('entries')
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get()
            ->map(fn (TimetablePeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'start_time' => substr((string) $period->start_time, 0, 5),
                'end_time' => substr((string) $period->end_time, 0, 5),
                'sort_order' => $period->sort_order,
                'is_break' => $period->is_break,
                'is_active' => $period->is_active,
                'entries_count' => $period->entries_count,
            ]);

        return Inertia::render('Timetable/Periods', [
            'periods' => $periods,
            'campuses' => $user->isSuperAdmin() ? $campuses : $campuses->where('id', $campusId)->values(),
            'sessions' => $sessions,
            'filters' => ['campus_id' => $campusId, 'session_id' => $sessionId],
            'canManage' => $user->can('create', TimetablePeriod::class),
        ]);
    }

    public function store(SaveTimetablePeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['campus_id'] = $this->resolveCampusId($request) ?? ($data['campus_id'] ?? null);

        if ($data['campus_id'] === null) {
            throw ValidationException::withMessages(['campus_id' => 'Choose a campus.']);
        }

        Gate::authorize('update', new TimetablePeriod(['campus_id' => $data['campus_id']]));

        TimetablePeriod::create($data + ['is_break' => false, 'is_active' => true]);

        return back()->with('success', 'Period added.');
    }

    public function update(SaveTimetablePeriodRequest $request, TimetablePeriod $period): RedirectResponse
    {
        Gate::authorize('update', $period);

        $data = $request->safe()->except(['campus_id', 'session_id']);

        if (($data['is_break'] ?? false) && $period->entries()->exists()) {
            throw ValidationException::withMessages(['is_break' => 'Lessons are scheduled in this period, so it cannot become a break.']);
        }

        $period->update($data);

        return back()->with('success', 'Period updated.');
    }

    public function destroy(TimetablePeriod $period): RedirectResponse
    {
        Gate::authorize('delete', $period);

        if ($period->entries()->exists()) {
            throw ValidationException::withMessages(['period' => 'Lessons are scheduled in this period. Remove them first.']);
        }

        $period->delete();

        return back()->with('success', 'Period removed.');
    }
}
