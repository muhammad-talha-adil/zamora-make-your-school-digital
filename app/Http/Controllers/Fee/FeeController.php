<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\FeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    protected FeeService $feeService;

    public function __construct(FeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    // =====================================================
    // FEE TYPES METHODS
    // =====================================================

    /**
     * List fee types per campus.
     */
    public function index(Request $request): JsonResponse
    {
        $campusId = $request->input('campus_id');

        if (!$campusId) {
            return response()->json([
                'success' => false,
                'message' => 'Campus ID is required',
            ], 400);
        }

        $feeTypes = FeeType::with('campus')
            ->where('campus_id', $campusId)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $feeTypes,
            'message' => 'Fee types retrieved successfully',
        ]);
    }

    /**
     * Create fee type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $feeType = FeeType::create([
                'campus_id' => $validated['campus_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeType->load('campus'),
                'message' => 'Fee type created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fee type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show fee type.
     */
    public function show(string $id): JsonResponse
    {
        $feeType = FeeType::with('campus')->find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feeType,
            'message' => 'Fee type retrieved successfully',
        ]);
    }

    /**
     * Update fee type.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $feeType = FeeType::find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
            ], 404);
        }

        $validated = $request->validate([
            'campus_id' => 'sometimes|required|exists:campuses,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $feeType->update([
                'campus_id' => $validated['campus_id'] ?? $feeType->campus_id,
                'name' => $validated['name'] ?? $feeType->name,
                'description' => $validated['description'] ?? $feeType->description,
                'is_active' => $validated['is_active'] ?? $feeType->is_active,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeType->fresh()->load('campus'),
                'message' => 'Fee type updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete fee type.
     */
    public function destroy(string $id): JsonResponse
    {
        $feeType = FeeType::find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Check for related records
            if ($feeType->feeStructures()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete fee type with associated fee structures',
                ], 400);
            }

            if ($feeType->studentFees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete fee type with associated student fees',
                ], 400);
            }

            $feeType->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee type deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fee type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore soft deleted fee type.
     */
    public function restore(string $id): JsonResponse
    {
        $feeType = FeeType::withTrashed()->find($id);

        if (!$feeType || !$feeType->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found or not deleted',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $feeType->restore();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeType->load('campus'),
                'message' => 'Fee type restored successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore fee type: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get trashed fee types.
     */
    public function trashed(Request $request): JsonResponse
    {
        $campusId = $request->input('campus_id');

        if (!$campusId) {
            return response()->json([
                'success' => false,
                'message' => 'Campus ID is required',
            ], 400);
        }

        $feeTypes = FeeType::with('campus')
            ->where('campus_id', $campusId)
            ->onlyTrashed()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $feeTypes,
            'message' => 'Trashed fee types retrieved successfully',
        ]);
    }

    /**
     * Toggle fee type status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $feeType = FeeType::find($id);

        if (!$feeType) {
            return response()->json([
                'success' => false,
                'message' => 'Fee type not found',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $feeType->update(['is_active' => !$feeType->is_active]);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeType->fresh()->load('campus'),
                'message' => 'Fee type status toggled successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // FEE STRUCTURE METHODS
    // =====================================================

    /**
     * List fee structures.
     */
    public function feeStructureIndex(Request $request): JsonResponse
    {
        $campusId = $request->input('campus_id');
        $sessionId = $request->input('session_id');
        $classId = $request->input('class_id');

        if (!$campusId) {
            return response()->json(['success' => false, 'message' => 'Campus ID is required'], 400);
        }

        $query = FeeStructure::with(['campus', 'session', 'schoolClass', 'feeType'])
            ->where('campus_id', $campusId);

        if ($sessionId) $query->where('session_id', $sessionId);
        if ($classId) $query->where('class_id', $classId);

        $feeStructures = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $feeStructures,
            'grouped_by_class' => $feeStructures->groupBy('class_id'),
            'message' => 'Fee structures retrieved successfully',
        ]);
    }

    /**
     * Create fee structure.
     */
    public function feeStructureStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'session_id' => 'required|exists:sessions,id',
            'class_id' => 'required|exists:school_classes,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'due_day_of_month' => 'nullable|integer|min:1|max:31',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // Check duplicate
            $existing = FeeStructure::where([
                'campus_id' => $validated['campus_id'],
                'session_id' => $validated['session_id'],
                'class_id' => $validated['class_id'],
                'fee_type_id' => $validated['fee_type_id'],
            ])->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee structure already exists for this combination',
                ], 422);
            }

            $feeStructure = FeeStructure::create([
                'campus_id' => $validated['campus_id'],
                'session_id' => $validated['session_id'],
                'class_id' => $validated['class_id'],
                'fee_type_id' => $validated['fee_type_id'],
                'amount' => $validated['amount'],
                'due_day_of_month' => $validated['due_day_of_month'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeStructure->load(['campus', 'session', 'schoolClass', 'feeType']),
                'message' => 'Fee structure created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show fee structure.
     */
    public function feeStructureShow(string $id): JsonResponse
    {
        $feeStructure = FeeStructure::with(['campus', 'session', 'schoolClass', 'feeType'])->find($id);

        if (!$feeStructure) {
            return response()->json(['success' => false, 'message' => 'Fee structure not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feeStructure]);
    }

    /**
     * Update fee structure.
     */
    public function feeStructureUpdate(Request $request, string $id): JsonResponse
    {
        $feeStructure = FeeStructure::find($id);

        if (!$feeStructure) {
            return response()->json(['success' => false, 'message' => 'Fee structure not found'], 404);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|required|numeric|min:0',
            'due_day_of_month' => 'nullable|integer|min:1|max:31',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $feeStructure->update($validated);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeStructure->fresh()->load(['campus', 'session', 'schoolClass', 'feeType']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle fee structure status.
     */
    public function feeStructureToggleStatus(string $id): JsonResponse
    {
        $feeStructure = FeeStructure::find($id);

        if (!$feeStructure) {
            return response()->json(['success' => false, 'message' => 'Fee structure not found'], 404);
        }

        DB::beginTransaction();

        try {
            $feeStructure->update(['is_active' => !$feeStructure->is_active]);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $feeStructure->fresh()->load(['campus', 'session', 'schoolClass', 'feeType']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to toggle: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete fee structure.
     */
    public function feeStructureDestroy(string $id): JsonResponse
    {
        $feeStructure = FeeStructure::find($id);

        if (!$feeStructure) {
            return response()->json(['success' => false, 'message' => 'Fee structure not found'], 404);
        }

        DB::beginTransaction();

        try {
            $feeStructure->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Fee structure deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Bulk create fee structures.
     */
    public function feeStructureBulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'session_id' => 'required|exists:sessions,id',
            'class_id' => 'required|exists:school_classes,id',
            'fee_structures' => 'required|array',
            'fee_structures.*.fee_type_id' => 'required|exists:fee_types,id',
            'fee_structures.*.amount' => 'required|numeric|min:0',
            'fee_structures.*.due_day_of_month' => 'nullable|integer|min:1|max:31',
        ]);

        DB::beginTransaction();

        try {
            $created = [];
            $updated = [];

            foreach ($validated['fee_structures'] as $fs) {
                $existing = FeeStructure::where([
                    'campus_id' => $validated['campus_id'],
                    'session_id' => $validated['session_id'],
                    'class_id' => $validated['class_id'],
                    'fee_type_id' => $fs['fee_type_id'],
                ])->first();

                if ($existing) {
                    $existing->update([
                        'amount' => $fs['amount'],
                        'due_day_of_month' => $fs['due_day_of_month'] ?? null,
                    ]);
                    $updated[] = $existing;
                } else {
                    $newFs = FeeStructure::create([
                        'campus_id' => $validated['campus_id'],
                        'session_id' => $validated['session_id'],
                        'class_id' => $validated['class_id'],
                        'fee_type_id' => $fs['fee_type_id'],
                        'amount' => $fs['amount'],
                        'due_day_of_month' => $fs['due_day_of_month'] ?? null,
                        'is_active' => true,
                    ]);
                    $created[] = $newFs;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['created' => $created, 'updated' => $updated],
                'message' => 'Fee structures processed',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fee structure summary.
     */
    public function feeStructureSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $feeStructures = FeeStructure::with(['schoolClass', 'feeType'])
            ->where('campus_id', $validated['campus_id'])
            ->where('session_id', $validated['session_id'])
            ->where('is_active', true)
            ->get();

        $summary = [];
        foreach ($feeStructures->groupBy('class_id') as $classId => $structures) {
            $class = $structures->first()->schoolClass;
            $summary[] = [
                'class_id' => $classId,
                'class_name' => $class->name ?? 'Unknown',
                'total_fee' => $structures->sum('amount'),
                'fee_types_count' => $structures->count(),
                'fee_types' => $structures->map(fn($fs) => [
                    'id' => $fs->feeType->id,
                    'name' => $fs->feeType->name,
                    'amount' => $fs->amount,
                ]),
            ];
        }

        return response()->json(['success' => true, 'data' => $summary]);
    }

    // =====================================================
    // STUDENT FEE ASSIGNMENT METHODS
    // =====================================================

    /**
     * List student fees.
     */
    public function studentFeeIndex(Request $request): JsonResponse
    {
        $campusId = $request->input('campus_id');

        if (!$campusId) {
            return response()->json(['success' => false, 'message' => 'Campus ID is required'], 400);
        }

        $query = StudentFee::with(['campus', 'session', 'student', 'feeType', 'invoice'])
            ->where('campus_id', $campusId);

        if ($sessionId = $request->input('session_id')) {
            $query->where('session_id', $sessionId);
        }
        if ($studentId = $request->input('student_id')) {
            $query->where('student_id', $studentId);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $studentFees = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $studentFees]);
    }

    /**
     * Assign fee to student.
     */
    public function studentFeeAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'student_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:sessions,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            // PRODUCTION FIX: Validate student belongs to campus
            $student = User::where('id', $validated['student_id'])
                ->where('campus_id', $validated['campus_id'])
                ->firstOrFail();

            // Calculate total amount after discount
            $amount = $validated['amount'];
            $discountAmount = $validated['discount_amount'] ?? 0;
            $discountPercentage = $validated['discount_percentage'] ?? 0;

            if ($discountPercentage > 0) {
                $discountAmount += ($amount * $discountPercentage / 100);
            }

            $totalAmount = max(0, $amount - $discountAmount);

            // Check duplicate
            $existingFee = StudentFee::where([
                'student_id' => $validated['student_id'],
                'session_id' => $validated['session_id'],
                'fee_type_id' => $validated['fee_type_id'],
                'status' => ['pending', 'partial', 'paid'],
            ])->first();

            if ($existingFee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee already assigned for this combination',
                ], 422);
            }

            $studentFee = StudentFee::create([
                'campus_id' => $validated['campus_id'],
                'student_id' => $validated['student_id'],
                'session_id' => $validated['session_id'],
                'fee_type_id' => $validated['fee_type_id'],
                'amount' => $amount,
                'discount_amount' => $discountAmount,
                'discount_percentage' => $discountPercentage,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'assigned_date' => now()->toDateString(),
                'due_date' => $validated['due_date'],
                'invoice_id' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $studentFee->load(['campus', 'session', 'student', 'feeType']),
                'message' => 'Fee assigned successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign all fees from fee structure to student.
     */
    public function studentFeeAssignAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'student_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:sessions,id',
            'class_id' => 'required|exists:school_classes,id',
            'due_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            // Validate student belongs to campus
            User::where('id', $validated['student_id'])
                ->where('campus_id', $validated['campus_id'])
                ->firstOrFail();

            $feeStructures = FeeStructure::with('feeType')
                ->where('campus_id', $validated['campus_id'])
                ->where('session_id', $validated['session_id'])
                ->where('class_id', $validated['class_id'])
                ->where('is_active', true)
                ->get();

            if ($feeStructures->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No fee structures found'], 422);
            }

            $created = [];
            $skipped = [];

            foreach ($feeStructures as $fs) {
                $existingFee = StudentFee::where([
                    'student_id' => $validated['student_id'],
                    'session_id' => $validated['session_id'],
                    'fee_type_id' => $fs->fee_type_id,
                    'status' => ['pending', 'partial', 'paid'],
                ])->first();

                if ($existingFee) {
                    $skipped[] = ['fee_type' => $fs->feeType->name, 'reason' => 'Already assigned'];
                    continue;
                }

                $studentFee = StudentFee::create([
                    'campus_id' => $validated['campus_id'],
                    'student_id' => $validated['student_id'],
                    'session_id' => $validated['session_id'],
                    'fee_type_id' => $fs->fee_type_id,
                    'amount' => $fs->amount,
                    'discount_amount' => 0,
                    'discount_percentage' => 0,
                    'total_amount' => $fs->amount,
                    'status' => 'pending',
                    'assigned_date' => now()->toDateString(),
                    'due_date' => $validated['due_date'],
                    'invoice_id' => null,
                ]);

                $created[] = $studentFee;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => ['created_count' => count($created), 'skipped' => $skipped],
                'message' => count($created) . ' fees assigned',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get student fees.
     */
    public function studentFeeByStudent(string $studentId): JsonResponse
    {
        $studentFees = StudentFee::with(['campus', 'session', 'feeType', 'invoice'])
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAmount = $studentFees->sum('total_amount');
        $paidAmount = $studentFees->where('status', 'paid')->sum('total_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'fees' => $studentFees,
                'summary' => [
                    'total_assigned' => $totalAmount,
                    'total_paid' => $paidAmount,
                    'total_pending' => $totalAmount - $paidAmount,
                ],
            ],
        ]);
    }

    /**
     * Get student fee summary.
     */
    public function studentFeeSummary(string $studentId): JsonResponse
    {
        $studentFees = StudentFee::with(['feeType'])
            ->where('student_id', $studentId)
            ->get();

        $totalAssigned = $studentFees->sum('total_amount');
        $totalPaid = $studentFees->where('status', 'paid')->sum('total_amount');

        $byFeeType = $studentFees->groupBy('fee_type_id')->map(function ($items, $feeTypeId) {
            $feeType = $items->first()->feeType;
            return [
                'fee_type_id' => $feeTypeId,
                'fee_type_name' => $feeType->name ?? 'Unknown',
                'total_amount' => $items->sum('total_amount'),
                'paid_amount' => $items->where('status', 'paid')->sum('total_amount'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'student_id' => $studentId,
                'total_assigned' => $totalAssigned,
                'total_paid' => $totalPaid,
                'total_due' => $totalAssigned - $totalPaid,
                'by_fee_type' => $byFeeType->values(),
            ],
        ]);
    }

    /**
     * Update student fee status.
     */
    public function studentFeeUpdateStatus(Request $request, string $id): JsonResponse
    {
        $studentFee = StudentFee::find($id);

        if (!$studentFee) {
            return response()->json(['success' => false, 'message' => 'Student fee not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,partial,paid,overdue',
        ]);

        DB::beginTransaction();

        try {
            $studentFee->update(['status' => $validated['status']]);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $studentFee->fresh()->load(['campus', 'session', 'student', 'feeType']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete student fee.
     */
    public function studentFeeDestroy(string $id): JsonResponse
    {
        $studentFee = StudentFee::find($id);

        if (!$studentFee) {
            return response()->json(['success' => false, 'message' => 'Student fee not found'], 404);
        }

        // PRODUCTION FIX: Prevent deletion of paid fees
        if ($studentFee->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot delete a paid fee'], 422);
        }

        DB::beginTransaction();

        try {
            $studentFee->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Student fee deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =====================================================
    // INVOICE METHODS (Using FeeService)
    // =====================================================

    /**
     * List invoices.
     */
    public function invoiceIndex(Request $request): JsonResponse
    {
        $campusId = $request->input('campus_id');

        if (!$campusId) {
            return response()->json(['success' => false, 'message' => 'Campus ID is required'], 400);
        }

        $query = Invoice::with(['campus', 'session', 'student', 'invoiceItems', 'payments'])
            ->where('campus_id', $campusId);

        if ($sessionId = $request->input('session_id')) {
            $query->where('session_id', $sessionId);
        }
        if ($studentId = $request->input('student_id')) {
            $query->where('student_id', $studentId);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $invoices]);
    }

    /**
     * Create invoice (using FeeService).
     */
    public function invoiceStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'student_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:sessions,id',
            'invoice_date' => 'required|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'fee_ids' => 'nullable|array',
            'fee_ids.*' => 'exists:student_fees,id',
            'inventory_ids' => 'nullable|array',
            'inventory_ids.*' => 'exists:student_inventory,id',
        ]);

        try {
            $invoice = $this->feeService->createInvoice($validated);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get invoice preview.
     */
    public function invoicePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'student_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        try {
            $preview = $this->feeService->getInvoicePreview(
                $validated['campus_id'],
                $validated['student_id'],
                $validated['session_id']
            );

            return response()->json(['success' => true, 'data' => $preview]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show invoice.
     */
    public function invoiceShow(string $id): JsonResponse
    {
        $invoice = Invoice::with([
            'campus',
            'session',
            'student',
            'invoiceItems.feeType',
            'invoiceItems.studentInventory',
            'payments'
        ])->find($id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $invoice]);
    }

    /**
     * Cancel invoice (using FeeService).
     */
    public function invoiceCancel(string $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        try {
            $invoice = $this->feeService->cancelInvoice($invoice);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel: ' . $e->getMessage()], 500);
        }
    }

    // =====================================================
    // PAYMENT METHODS (Using FeeService)
    // =====================================================

    /**
     * List payments.
     */
    public function paymentIndex(Request $request): JsonResponse
    {
        $campusId = $request->input('campus_id');

        if (!$campusId) {
            return response()->json(['success' => false, 'message' => 'Campus ID is required'], 400);
        }

        $query = Payment::with(['invoice.student', 'invoice'])
            ->whereHas('invoice', fn($q) => $q->where('campus_id', $campusId));

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        $byMode = Payment::whereHas('invoice', fn($q) => $q->where('campus_id', $campusId))
            ->get()
            ->groupBy('payment_mode')
            ->map(fn($items) => ['count' => $items->count(), 'total' => $items->sum('amount')]);

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'summary' => ['total_collected' => $payments->sum('amount'), 'by_mode' => $byMode],
            ],
        ]);
    }

    /**
     * Process payment (using FeeService).
     */
    public function paymentStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:cash,bank,card,cheque,online',
            'amount' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        try {
            $result = $this->feeService->processPayment($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Payment recorded successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get payments by invoice.
     */
    public function paymentByInvoice(string $invoiceId): JsonResponse
    {
        $payments = Payment::with('invoice')
            ->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'summary' => ['total_paid' => $payments->sum('amount'), 'count' => $payments->count()],
            ],
        ]);
    }

    /**
     * Get payment receipt.
     */
    public function paymentReceipt(string $id): JsonResponse
    {
        $payment = Payment::with([
            'invoice.campus',
            'invoice.student',
            'invoice.session',
            'invoice.payments'
        ])->find($id);

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $remainingBalance = $payment->invoice->total_amount - $payment->invoice->paid_amount;

        return response()->json([
            'success' => true,
            'data' => [
                'receipt' => [
                    'receipt_number' => 'RCP' . str_pad($payment->id, 6, '0', STR_PAD_LEFT),
                    'payment_date' => $payment->payment_date,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'student' => $payment->invoice->student,
                    'campus' => $payment->invoice->campus,
                    'session' => $payment->invoice->session,
                    'amount_paid' => $payment->amount,
                    'payment_mode' => $payment->payment_mode,
                    'reference_number' => $payment->reference_number,
                    'invoice_total' => $payment->invoice->total_amount,
                    'total_paid' => $payment->invoice->paid_amount,
                    'remaining_balance' => max(0, $remainingBalance),
                ],
            ],
        ]);
    }
}
