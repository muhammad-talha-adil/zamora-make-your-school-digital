<?php

namespace App\Http\Controllers\Timetable;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\SaveTimetableEntryRequest;
use App\Models\SchoolClass;
use App\Models\Timetable\TimetableEntry;
use App\Services\Timetable\TimetableClashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Places, moves and removes lessons on a section's timetable.
 */
class TimetableEntryController extends Controller
{
    public function __construct(private TimetableClashService $clashes) {}

    public function store(SaveTimetableEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->authorizeSection($data);

        $this->clashes->place($data);

        return back()->with('success', 'Lesson added.');
    }

    public function update(SaveTimetableEntryRequest $request, TimetableEntry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);

        $data = $request->validated();

        // Moving a lesson into another section needs reach over that one too.
        $this->authorizeSection($data);

        $this->clashes->place($data, $entry);

        return back()->with('success', 'Lesson updated.');
    }

    public function destroy(TimetableEntry $entry): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $entry->delete();

        return back()->with('success', 'Lesson removed.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function authorizeSection(array $data): void
    {
        Gate::authorize('manageSection', [
            TimetableEntry::class,
            SchoolClass::whereKey($data['class_id'])->value('campus_id'),
            (int) $data['class_id'],
            isset($data['section_id']) ? (int) $data['section_id'] : null,
        ]);
    }
}
