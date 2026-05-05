<?php

namespace App\Http\Controllers;

use App\Models\AdmissionFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionFeeController extends Controller
{
    /**
     * Store a new admission fee record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'admission_date' => 'required|date',
            'admission_fee' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,pending,waived',
            'notes' => 'nullable|string',
        ]);

        $admissionFee = AdmissionFee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admission fee saved successfully.',
            'admission_fee' => $admissionFee,
        ]);
    }

    /**
     * Update an admission fee record.
     */
    public function update(Request $request, AdmissionFee $admissionFee): JsonResponse
    {
        $validated = $request->validate([
            'admission_date' => 'required|date',
            'admission_fee' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,pending,waived',
            'notes' => 'nullable|string',
        ]);

        $admissionFee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Admission fee updated successfully.',
            'admission_fee' => $admissionFee,
        ]);
    }

    /**
     * Delete an admission fee record.
     */
    public function destroy(AdmissionFee $admissionFee): JsonResponse
    {
        $admissionFee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admission fee deleted successfully.',
        ]);
    }
}
