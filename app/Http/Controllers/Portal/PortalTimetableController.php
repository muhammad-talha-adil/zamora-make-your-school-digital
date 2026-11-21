<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesOwnStudent;
use App\Http\Controllers\Controller;
use App\Models\Timetable\TimetableEntry;
use App\Services\Timetable\TimetableGridService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A family's own weekly timetable: the section their child is enrolled in.
 */
class PortalTimetableController extends Controller
{
    use ResolvesOwnStudent;

    public function __construct(private TimetableGridService $grid) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $students = $this->ownStudents($user);
        $student = $this->resolveOwnStudent($user, $request->integer('student_id') ?: null);
        $enrollment = $student->currentEnrollment;

        $entries = collect();
        $periods = collect();

        if ($enrollment) {
            $entries = TimetableEntry::query()
                ->where('session_id', $enrollment->session_id)
                ->where('class_id', $enrollment->class_id)
                ->when(
                    $enrollment->section_id,
                    fn ($q, $id) => $q->where('section_id', $id),
                    fn ($q) => $q->whereNull('section_id')
                )
                ->get();

            $periods = $this->grid->periodsFor($enrollment->campus_id, $enrollment->session_id);
        }

        return Inertia::render('Portal/Timetable/Index', [
            'student' => ['id' => $student->id, 'name' => $student->user?->name],
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name])->values(),
            'periods' => $this->grid->periodPayload($periods),
            'entries' => $this->grid->entryPayload($entries),
        ]);
    }
}
