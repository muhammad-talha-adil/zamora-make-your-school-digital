<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentDocumentRequest;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Uploading and removing the papers a school holds a copy of for a student.
 *
 * Mirrors the document endpoints on `App\Http\Controllers\Staff\
 * StaffProfileController` — one child's documents, scoped through
 * `StudentDocumentPolicy`.
 */
class StudentDocumentController extends Controller
{
    public function index(Request $request, Student $student): JsonResponse
    {
        Gate::authorize('viewAny', [StudentDocument::class, $student]);

        return response()->json([
            'data' => $student->documents()->with('documentType')->latest()->get(),
        ]);
    }

    public function store(StoreStudentDocumentRequest $request, Student $student): JsonResponse
    {
        Gate::authorize('create', [StudentDocument::class, $student]);

        $validated = $request->validated();

        $path = $request->hasFile('file')
            ? $request->file('file')->store('students/'.$student->id.'/documents', 'public')
            : null;

        $document = $student->documents()->create(
            collect($validated)->except('file')->all() + [
                'path' => $path,
                'uploaded_by' => $request->user()?->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Document filed successfully.',
            'data' => $document->load('documentType'),
        ], 201);
    }

    public function destroy(StudentDocument $document): JsonResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document removed.',
        ]);
    }
}
