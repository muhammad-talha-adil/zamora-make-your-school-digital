<?php

namespace App\Http\Controllers\Timetable;

use App\Http\Controllers\Concerns\ResolvesOwnStaffProfile;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\StaffProfile;
use App\Models\Subject;
use App\Models\Timetable\TimetableEntry;
use App\Models\User;
use App\Services\Timetable\TimetableGridService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The timetable screens for staff: a section's grid (editable for managers,
 * read-only otherwise) and a teacher's own week.
 */
class TimetableController extends Controller
{
    use ResolvesOwnStaffProfile;

    public function __construct(private TimetableGridService $grid) {}

    /**
     * A section's weekly grid.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        Gate::authorize('viewAny', TimetableEntry::class);

        $user = $request->user();

        // A family has their own screen; this one is the staff view of any section.
        if ($user->student || $user->guardian) {
            return redirect()->route('portal.timetable.index');
        }

        $sessions = Session::query()->orderByDesc('is_active')->orderByDesc('start_year')->get(['id', 'name', 'is_active']);
        $sessionId = $request->integer('session_id') ?: $sessions->first()?->id;

        $options = $this->sectionOptions($user, $sessionId);
        $selected = $options->first(fn (array $option) => $option['class_id'] === $request->integer('class_id')
            && $option['section_id'] === ($request->integer('section_id') ?: null))
            ?? $options->first();

        $canManage = $user->can('create', TimetableEntry::class);

        $entries = collect();
        $periods = collect();

        if ($selected) {
            $class = SchoolClass::find($selected['class_id']);

            $entries = TimetableEntry::query()
                ->visibleTo($user)
                ->where('session_id', $sessionId)
                ->where('class_id', $selected['class_id'])
                ->when(
                    $selected['section_id'],
                    fn ($q, $id) => $q->where('section_id', $id),
                    fn ($q) => $q->whereNull('section_id')
                )
                ->get();

            $periods = $this->grid->periodsFor($class?->campus_id, $sessionId);
        }

        return Inertia::render('Timetable/Index', [
            'sessions' => $sessions,
            'sectionOptions' => $options->values(),
            'filters' => [
                'session_id' => $sessionId,
                'class_id' => $selected['class_id'] ?? null,
                'section_id' => $selected['section_id'] ?? null,
            ],
            'periods' => $this->grid->periodPayload($periods),
            'entries' => $this->grid->entryPayload($entries),
            'canManage' => $canManage,
            'subjects' => $canManage
                ? Subject::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : [],
            'teachers' => $canManage ? $this->teachers($user) : [],
        ]);
    }

    /**
     * The signed-in teacher's own week, across every section they teach.
     */
    public function mine(Request $request): Response
    {
        Gate::authorize('viewAny', TimetableEntry::class);

        $staff = $this->resolveOwnStaffProfile($request->user());

        $sessions = Session::query()->orderByDesc('is_active')->orderByDesc('start_year')->get(['id', 'name', 'is_active']);
        $sessionId = $request->integer('session_id') ?: $sessions->first()?->id;

        $entries = TimetableEntry::query()
            ->where('staff_profile_id', $staff->id)
            ->where('session_id', $sessionId)
            ->get();

        $campusId = $staff->campus_id ?? $entries->first()?->campus_id;

        return Inertia::render('Timetable/Mine', [
            'sessions' => $sessions,
            'filters' => ['session_id' => $sessionId],
            'periods' => $this->grid->periodPayload($this->grid->periodsFor($campusId, $sessionId)),
            'entries' => $this->grid->entryPayload($entries),
        ]);
    }

    /**
     * Every class/section the viewer may open, one option per section (or per
     * class that has no sections).
     *
     * @return Collection<int, array{key: string, class_id: int, section_id: int|null, label: string}>
     */
    private function sectionOptions(User $user, ?int $sessionId): Collection
    {
        $options = collect();

        $classes = SchoolClass::query()
            ->where('is_active', true)
            ->visibleTo($user)
            ->with(['sections' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        foreach ($classes as $class) {
            $sections = $class->sections->isEmpty() ? collect([null]) : $class->sections;

            foreach ($sections as $section) {
                if ($user->isClassRestricted() && ! $user->teachesSection($class->id, $section?->id, $sessionId)) {
                    continue;
                }

                $options->push([
                    'key' => $class->id.':'.($section?->id ?? ''),
                    'class_id' => $class->id,
                    'section_id' => $section?->id,
                    'label' => $section ? $class->name.' - '.$section->name : $class->name,
                ]);
            }
        }

        return $options;
    }

    /**
     * @return Collection<int, array{id: int, name: string|null}>
     */
    private function teachers(User $user): Collection
    {
        return StaffProfile::query()
            ->active()
            ->visibleTo($user)
            ->whereHas('user', fn ($q) => $q->role('teacher'))
            ->with('user:id,name')
            ->get()
            ->map(fn (StaffProfile $profile) => ['id' => $profile->id, 'name' => $profile->user?->name])
            ->sortBy('name')
            ->values();
    }
}
