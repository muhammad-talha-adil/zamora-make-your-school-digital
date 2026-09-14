<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStaffDocumentTypeRequest;
use App\Http\Requests\Staff\UpdateStaffDocumentTypeRequest;
use App\Models\Staff\StaffDocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * The kinds of paper a school files against a member of staff.
 *
 * Gated the same way `StaffController::storeDepartment()`/`storeDesignation()`
 * are — `staff.department.manage` — rather than a new permission: this is the
 * same lookup-table job as departments and designations, one screen over.
 */
class StaffDocumentTypeController extends Controller
{
    /**
     * The list, for the settings screen and for the document-kind dropdown.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => StaffDocumentType::orderBy('name')->get(),
        ]);
    }

    public function store(StoreStaffDocumentTypeRequest $request): JsonResponse
    {
        $documentType = StaffDocumentType::create($request->validated() + ['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Document type created successfully.',
            'documentType' => $documentType,
        ]);
    }

    public function update(UpdateStaffDocumentTypeRequest $request, StaffDocumentType $documentType): JsonResponse
    {
        $documentType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Document type updated successfully.',
            'documentType' => $documentType->fresh(),
        ]);
    }

    public function destroy(StaffDocumentType $documentType): JsonResponse|RedirectResponse
    {
        $documentType->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Document type deactivated.']);
        }

        return back()->with('success', 'Document type deactivated.');
    }
}
