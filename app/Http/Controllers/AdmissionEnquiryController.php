<?php

namespace App\Http\Controllers;

use App\Models\AdmissionEnquiry;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The parents who have asked about a place.
 *
 * A family visits in March, asks about Class 5 and leaves; today that is a name
 * in a diary, and when they come back in June the school types everything
 * again — if it remembers them at all.
 *
 * The one screen that matters here is the follow-up list: who is due to be rung
 * today. Everything else is a form.
 */
class AdmissionEnquiryController extends Controller
{
    public function page(Request $request)
    {
        Gate::authorize('viewAny', Student::class);

        return Inertia::render('Students/Enquiries/Index', [
            'campuses' => Campus::all(),
            'classes' => SchoolClass::where('is_active', true)->orderBy('level')->orderBy('name')->get(),
            'sessions' => Session::where('is_active', true)->get(),
            'filters' => $request->only(['status', 'due']),
        ]);
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Student::class);

        $enquiries = AdmissionEnquiry::query()
            ->visibleTo($request->user())
            ->with(['campus', 'class', 'session', 'handledBy:id,name', 'student:id,admission_no'])
            // The list a school works from in admission season.
            ->when($request->boolean('due'), fn ($q) => $q->due())
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('phone'), fn ($q, $phone) => $q->where('phone', 'like', '%'.$phone.'%'))
            ->orderByRaw('CASE WHEN follow_up_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('follow_up_on')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 25));

        return response()->json(['data' => $enquiries]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate($this->rules());

        $enquiry = AdmissionEnquiry::create($validated + [
            'status' => AdmissionEnquiry::STATUS_OPEN,
            'handled_by' => $request->user()?->id,
        ]);

        return response()->json(['message' => 'Enquiry recorded', 'data' => $enquiry], 201);
    }

    public function update(Request $request, int|string $id)
    {
        Gate::authorize('create', Student::class);

        $enquiry = AdmissionEnquiry::findOrFail($id);

        $validated = $request->validate($this->rules() + [
            'status' => ['nullable', 'string', 'in:open,contacted,visited,admitted,closed'],
        ]);

        $enquiry->update($validated);

        return response()->json(['message' => 'Enquiry updated', 'data' => $enquiry->fresh()]);
    }

    /**
     * What the admission form should be filled in with.
     *
     * The enquiry is not turned into a student here. A school checks the
     * details with the family at the counter, adds the fee and the guardian's
     * CNIC, and admits them through the ordinary form — which is the form that
     * knows how to do it. This just means nobody types the name twice.
     */
    public function prefill(Request $request, int|string $id)
    {
        Gate::authorize('create', Student::class);

        $enquiry = AdmissionEnquiry::with(['campus', 'class', 'session'])->findOrFail($id);

        return response()->json([
            'data' => [
                'enquiry_id' => $enquiry->id,
                'name' => $enquiry->student_name,
                'dob' => $enquiry->dob?->toDateString(),
                'gender_id' => $enquiry->gender_id,
                'campus_id' => $enquiry->campus_id,
                'class_id' => $enquiry->class_id,
                'session_id' => $enquiry->session_id,
                'father_name' => $enquiry->guardian_name,
                'father_phone' => $enquiry->phone,
                'father_address' => $enquiry->address,
            ],
        ]);
    }

    /**
     * Marks an enquiry as having become a child.
     *
     * Kept separate from the admission itself so that an admission which fails
     * halfway does not leave an enquiry claiming a student that was never
     * created.
     */
    public function markAdmitted(Request $request, int|string $id)
    {
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $enquiry = AdmissionEnquiry::findOrFail($id);

        $enquiry->update([
            'student_id' => $validated['student_id'],
            'status' => AdmissionEnquiry::STATUS_ADMITTED,
            'converted_at' => now(),
        ]);

        return response()->json(['message' => 'Enquiry closed as admitted', 'data' => $enquiry->fresh()]);
    }

    public function destroy(int|string $id)
    {
        Gate::authorize('create', Student::class);

        AdmissionEnquiry::findOrFail($id)->delete();

        return response()->json(['message' => 'Enquiry removed']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:255'],
            // The one thing a walk-in conversation reliably produces.
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_on' => ['nullable', 'date'],
        ];
    }
}
