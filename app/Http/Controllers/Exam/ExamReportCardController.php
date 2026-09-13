<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Exam;
use App\Models\Exam\ExamResultHeader;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Services\Exam\AnnualResultService;
use App\Services\Exam\DatesheetService;
use App\Services\Exam\ExamPositionService;
use App\Services\Exam\ReportCardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The things a school prints and hands over.
 *
 * The module could compute a result and could not produce the one artefact a
 * family ever sees. The card, the datesheet and the year's summary live here;
 * the arithmetic behind them lives in the services, so nothing is worked out
 * for the first time in a view.
 */
class ExamReportCardController extends Controller
{
    public function __construct(
        private ReportCardService $cards,
        private ExamPositionService $positions,
        private AnnualResultService $annual,
        private DatesheetService $datesheets
    ) {}

    /**
     * One child's result card (print).
     */
    public function card(Request $request, int|string $resultHeaderId)
    {
        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $this->authorize('view', $header);

        return response()->view('exam.result-card', [
            'cards' => [$this->cards->forResult($header, $this->span($request))],
        ]);
    }

    /**
     * A whole section's cards, one to a page (print).
     */
    public function sectionCards(Request $request, int|string $examId)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        $exam = Exam::findOrFail($examId);

        // Checked against a result in the section being printed, so a teacher
        // cannot run off another class's cards.
        $first = ExamResultHeader::where('exam_id', $exam->id)
            ->where('class_id', $validated['class_id'])
            ->when($validated['section_id'] ?? null, fn ($q, $id) => $q->where('section_id', $id))
            ->first();

        if (! $first) {
            return response()->view('exam.result-card', ['cards' => []]);
        }

        $this->authorize('view', $first);

        return response()->view('exam.result-card', [
            'cards' => $this->cards->forSection(
                $exam,
                (int) $validated['class_id'],
                $validated['section_id'] ?? null,
                $this->span($request)
            ),
        ]);
    }

    /**
     * The class teacher's line at the bottom of the card.
     */
    public function saveRemarks(Request $request, int|string $resultHeaderId)
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $header = ExamResultHeader::findOrFail($resultHeaderId);
        $this->authorize('enterMarks', $header);

        $header->update(['remarks' => $validated['remarks'] ?? null]);

        return response()->json(['message' => 'Remarks saved', 'data' => $header->fresh()]);
    }

    /**
     * Work out positions again.
     *
     * They are computed when an exam is published, which is the moment that
     * matters. This exists for the case a school corrects a mark afterwards and
     * wants the ranking to catch up without republishing.
     */
    public function recomputePositions(Request $request, int|string $examId)
    {
        $exam = Exam::findOrFail($examId);
        $this->authorize('publish', $exam);

        $ranked = $this->positions->recomputeFor(
            $exam,
            $request->integer('class_id') ?: null
        );

        return response()->json([
            'message' => "Positions worked out for {$ranked} results",
            'data' => ['ranked' => $ranked],
        ]);
    }

    /**
     * The annual result screen.
     *
     * Its own page rather than a tab: every other exam screen answers a
     * question about one exam, and this one answers a question about the
     * session.
     */
    public function annualPage(Request $request)
    {
        $this->authorize('viewAny', ExamResultHeader::class);

        return Inertia::render('Exam/Results/Annual', [
            'sessions' => Session::where('is_active', true)->get(['id', 'name']),
            'classes' => SchoolClass::where('is_active', true)
                ->orderBy('level')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * One child's year, assembled from the terms.
     */
    public function annual(Request $request, int|string $studentId)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
        ]);

        // The child's own results decide who may read this, as they do for a
        // single exam.
        $header = ExamResultHeader::where('student_id', $studentId)
            ->whereHas('exam', fn ($q) => $q->where('session_id', $validated['session_id']))
            ->first();

        if ($header) {
            $this->authorize('view', $header);
        } else {
            $this->authorize('viewAny', ExamResultHeader::class);
        }

        return response()->json([
            'data' => $this->annual->forStudent(
                (int) $studentId,
                (int) $validated['session_id'],
                $header?->campus_id
            ),
        ]);
    }

    /**
     * A section's year, for the sheet the office works from.
     */
    public function annualForSection(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ]);

        $this->authorize('viewAny', ExamResultHeader::class);

        return response()->json([
            'data' => $this->annual->forSection(
                (int) $validated['session_id'],
                (int) $validated['class_id'],
                $validated['section_id'] ?? null,
                $request->user()?->campusId()
            ),
        ]);
    }

    /**
     * The datesheet (print).
     */
    public function datesheet(Request $request, int|string $examId)
    {
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ]);

        $exam = Exam::findOrFail($examId);
        $this->authorize('view', $exam);

        $sheet = $this->datesheets->forExam(
            $exam,
            $validated['class_id'] ?? null,
            $validated['section_id'] ?? null,
            $validated['campus_id'] ?? null,
            $request->user()
        );

        return response()->view('exam.datesheet', [
            'sheet' => $sheet,
            'school' => School::where('is_active', true)->first(),
            'heading' => $this->datesheetHeading($sheet),
        ]);
    }

    /**
     * The months a card's attendance line covers.
     *
     * Defaults to the child's session up to the month the exam ended, which is
     * the span a term's card covers; a school printing a half-yearly card says
     * otherwise.
     *
     * @return array{from_month?: int, to_month?: int, year?: int}
     */
    private function span(Request $request): array
    {
        return array_filter([
            'from_month' => $request->integer('from_month') ?: null,
            'to_month' => $request->integer('to_month') ?: null,
            'year' => $request->integer('year') ?: null,
        ]);
    }

    /**
     * "Class 9 — Section A", where the sheet is for one.
     *
     * @param  array<string, mixed>  $sheet
     */
    private function datesheetHeading(array $sheet): ?string
    {
        $first = $sheet['days'][0]['papers'][0] ?? null;

        if (! $first || ! $first['class']) {
            return null;
        }

        $classes = collect($sheet['days'])
            ->flatMap(fn ($day) => collect($day['papers'])->pluck('class'))
            ->filter()
            ->unique();

        if ($classes->count() !== 1) {
            return null;
        }

        return $first['section']
            ? $classes->first().' — Section '.$first['section']
            : $classes->first();
    }
}
