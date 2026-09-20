<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Concerns\ResolvesOwnStudent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Exam\ExamReportCardController;
use App\Models\Exam\ExamPaper;
use App\Models\Exam\ExamResultHeader;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * A family's own exam results.
 *
 * `ExamResultHeaderPolicy::viewAny()` now accepts `exam.result.view.own`,
 * which only opens the door to this list — the query itself is scoped to the
 * caller's own resolved student. Only published (or subsequently locked)
 * results are shown here; a result still being marked or verified is not
 * something a family should see yet.
 */
class PortalExamController extends Controller
{
    use ResolvesOwnStudent;

    /**
     * @var array<int, string>
     */
    private const VISIBLE_STATUSES = [
        ExamResultHeader::STATUS_PUBLISHED,
        ExamResultHeader::STATUS_LOCKED,
    ];

    public function __construct(private ExamReportCardController $reportCardController) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ExamResultHeader::class);

        $user = $request->user();
        $students = $this->ownStudents($user);
        $student = $this->resolveOwnStudent($user, $request->integer('student_id') ?: null);

        $results = ExamResultHeader::where('student_id', $student->id)
            ->whereIn('status', self::VISIBLE_STATUSES)
            ->with(['exam.examType', 'campus', 'class', 'section', 'overallGradeItem'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Exams/Index', [
            'student' => ['id' => $student->id, 'name' => $student->user?->name],
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name])->values(),
            'results' => $results,
            'upcomingPapers' => $this->upcomingPapers($student),
        ]);
    }

    /**
     * The papers still ahead on the timetable, for this child's class.
     *
     * A family sets its expectations from this list well before any result
     * exists to show — the timing set on the papers screen is exactly what
     * belongs here.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function upcomingPapers(Student $student): Collection
    {
        $enrollment = $student->currentEnrollment;

        if (! $enrollment) {
            return collect();
        }

        return ExamPaper::with(['exam:id,name', 'subject:id,name'])
            ->where('status', '!=', 'cancelled')
            ->where('paper_date', '>=', now()->toDateString())
            ->where('class_id', $enrollment->class_id)
            ->where(function ($query) use ($enrollment) {
                $query->whereNull('section_id')->orWhere('section_id', $enrollment->section_id);
            })
            ->orderBy('paper_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ExamPaper $paper) => [
                'id' => $paper->id,
                'exam' => $paper->exam?->name,
                'subject' => $paper->subject?->name,
                'paper_date' => $paper->paper_date?->toDateString(),
                'start_time' => $paper->start_time,
                'end_time' => $paper->end_time,
            ]);
    }

    /**
     * Reuses `ExamReportCardController::card()` as-is — the ownership check
     * already lives on `ExamResultHeaderPolicy::view()` and now recognises
     * both the child themselves and a linked guardian.
     */
    public function show(Request $request, ExamResultHeader $resultHeader): HttpResponse
    {
        return $this->reportCardController->card($request, $resultHeader->id);
    }
}
