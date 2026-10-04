<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentDocumentTypeRequest;
use App\Http\Requests\Student\UpdateStudentDocumentTypeRequest;
use App\Models\StudentDocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * The kinds of paper a school files against a student.
 *
 * Mirrors `App\Http\Controllers\Staff\StaffDocumentTypeController` — a plain
 * lookup table, gated by route `permission:` middleware alone rather than a
 * policy, the same way departments and designations are.
 */
class StudentDocumentTypeController extends Controller
{
    /**
     * The list, for the settings screen and for the document-type dropdown.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => StudentDocumentType::orderBy('name')->get(),
        ]);
    }

    public function store(StoreStudentDocumentTypeRequest $request): JsonResponse
    {
        $documentType = StudentDocumentType::create($request->validated() + ['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Document type created successfully.',
            'documentType' => $documentType,
        ]);
    }

    public function update(UpdateStudentDocumentTypeRequest $request, StudentDocumentType $documentType): JsonResponse
    {
        $documentType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Document type updated successfully.',
            'documentType' => $documentType->fresh(),
        ]);
    }

    public function destroy(StudentDocumentType $documentType): JsonResponse|RedirectResponse
    {
        $documentType->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Document type deactivated.']);
        }

        return back()->with('success', 'Document type deactivated.');
    }
}
