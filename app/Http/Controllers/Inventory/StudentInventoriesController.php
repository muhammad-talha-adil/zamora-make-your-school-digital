<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreReturnRequest;
use App\Http\Requests\Inventory\StoreStudentInventoryRequest;
use App\Models\Campus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\InventoryType;
use App\Models\PurchaseItem;
use App\Models\ReturnModel;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\StudentInventory;
use App\Models\StudentInventoryItem;
use App\Models\StudentInventoryReturn;
use App\Models\StudentInventoryReturnItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentInventoriesController extends Controller
{
    /**
     * Display student inventory listing.
     *
     * IMPROVEMENTS:
     * - Added pagination for large datasets (1000+ students)
     * - Added campus_id filtering for multi-campus safety
     * - Added status filter
     * - Updated to use new parent-child structure
     */
    public function index(Request $request)
    {
        $campusId = $request->get('campus_id');
        $studentId = $request->get('student_id');

        return inertia('inventory/StudentInventories/Index', [
            'studentInventories' => StudentInventory::with([
                'campus:id,name',
                'student' => function ($q) { $q->select('id', 'registration_no'); },
                'student.user' => function ($q) { $q->select('id', 'name'); },
                'items.inventoryItem:id,name'
            ])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->when($studentId, fn($q) => $q->where('student_id', $studentId))
                ->when($request->get('status'), fn($q) => $q->where('status', $request->get('status')))
                ->orderBy('assigned_date', 'desc')
                ->paginate(20),
            'campuses' => Campus::orderBy('name')->get(),
            'students' => Student::whereHas('enrollmentRecords', function ($query) use ($campusId) {
                    $query->where('campus_id', $campusId);
                })
                ->orderBy('registration_no')
                ->get(),
            'filters' => [
                'campus_id' => $campusId,
                'student_id' => $studentId,
                'status' => $request->get('status'),
            ],
        ]);
    }

    /**
     * Get all student inventories with optional filters.
     *
     * IMPROVEMENTS:
     * - campus_id is now required for multi-campus safety
     * - Added pagination support
     * - Updated to use parent-child structure
     * - Returns grouped by student inventory record with items
     */
    public function getAll(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $studentId = $request->get('student_id');

        if (!$campusId) {
            return response()->json([
                'error' => 'campus_id is required',
                'message' => 'Please provide a campus_id filter for multi-campus safety.',
            ], 422);
        }

        if (!Campus::where('id', $campusId)->exists()) {
            return response()->json([
                'error' => 'Invalid campus_id',
                'message' => 'The specified campus does not exist.',
            ], 422);
        }

        $inventories = StudentInventory::with([
            'campus:id,name',
            'student' => function ($q) { $q->select('id', 'registration_no'); },
            'student.user' => function ($q) { $q->select('id', 'name'); },
            'items.inventoryItem:id,name',
            'class:id,name',
            'section:id,name'
        ])
            ->where('campus_id', $campusId)
            ->when($studentId, fn($q) => $q->where('student_id', $studentId))
            ->when($request->get('status'), fn($q) => $q->where('status', $request->get('status')))
            ->when($request->get('from_date'), fn($q) => $q->whereDate('assigned_date', '>=', $request->get('from_date')))
            ->when($request->get('to_date'), fn($q) => $q->whereDate('assigned_date', '<=', $request->get('to_date')))
            ->orderBy('assigned_date', 'desc')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'student_inventory_id' => $record->student_inventory_id,
                    'campus_id' => $record->campus_id,
                    'campus_name' => $record->campus?->name,
                    'student_id' => $record->student_id,
                    'student_name' => $record->student?->name,
                    'registration_number' => $record->student?->registration_no,
                    'class_name' => $record->class?->name,
                    'section_name' => $record->section?->name,
                    'total_amount' => $record->total_amount,
                    'total_discount' => $record->total_discount,
                    'final_amount' => $record->final_amount,
                    'status' => $record->status,
                    'assigned_date' => $record->assigned_date,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                    // Include items as nested array
                    'items' => $record->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'item_name_snapshot' => $item->item_name_snapshot,
                            'description_snapshot' => $item->description_snapshot,
                            'unit_price_snapshot' => $item->unit_price_snapshot,
                            'discount_amount' => $item->discount_amount,
                            'discount_percentage' => $item->discount_percentage,
                            'quantity' => $item->quantity,
                            'returned_quantity' => $item->returned_quantity,
                            'remaining_quantity' => $item->remainingQuantity(),
                            'inventory_item' => $item->inventoryItem ? [
                                'id' => $item->inventoryItem->id,
                                'name' => $item->inventoryItem->name
                            ] : null,
                        ];
                    }),
                ];
            });

        return response()->json($inventories);
    }

    /**
     * Get students for dropdown with search functionality.
     * Returns students with their current class and section.
     */
    public function getStudents(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $search = $request->get('q', '');

        if (!$campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        // Get students through their active enrollment records for the campus
        $students = Student::whereHas('enrollmentRecords', function ($query) use ($campusId) {
                $query->where('campus_id', $campusId)->active();
            })
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('registration_no', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                }
            })
            ->with(['enrollmentRecords' => function ($q) use ($campusId) {
                $q->where('campus_id', $campusId)->active()
                    ->with(['class:id,name', 'section:id,name'])
                    ->limit(1);
            }])
            ->orderBy('registration_no')
            ->limit(50)
            ->get()
            ->map(function ($student) {
                $enrollment = $student->enrollmentRecords->first();
                $className = $enrollment?->class?->name ?? '';
                $sectionName = $enrollment?->section?->name ?? '';
                
                // Format: "Ali - Five - A"
                $displayName = $student->name;
                if ($className) {
                    $displayName .= ' - ' . $className;
                }
                if ($sectionName) {
                    $displayName .= ' - ' . $sectionName;
                }
                
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_number' => $student->registration_no,
                    'display_text' => $displayName,
                    'class_name' => $className,
                    'section_name' => $sectionName,
                    'class_id' => $enrollment?->class_id,
                    'section_id' => $enrollment?->section_id,
                ];
            });

        return response()->json($students);
    }

    /**
     * Get inventory items filtered by campus and type.
     */
    public function getInventoryItems(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');
        $typeId = $request->get('type_id');
        $search = $request->get('q', '');

        if (!$campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        $items = InventoryItem::where('campus_id', $campusId)
            ->where('is_active', true)
            ->when($typeId, fn($q) => $q->where('inventory_type_id', $typeId))
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function ($item) use ($campusId) {
                // Get stock for this specific campus
                $stock = InventoryStock::where('inventory_item_id', $item->id)
                    ->where('campus_id', $campusId)
                    ->first();
                
                // Get the latest sale_rate from purchase items
                $purchaseItem = \App\Models\PurchaseItem::where('inventory_item_id', $item->id)
                    ->orderBy('id', 'desc')
                    ->first();
                
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'sale_rate' => $purchaseItem?->sale_rate ?? 0,
                    'purchase_rate' => $purchaseItem?->purchase_rate ?? 0,
                    'available_stock' => $stock ? ($stock->quantity - $stock->reserved_quantity) : 0,
                    'inventory_type_id' => $item->inventory_type_id,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get inventory types for a campus.
     */
    public function getInventoryTypes(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        if (!$campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        $types = InventoryType::forCampus($campusId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($types);
    }

    /**
     * Get dashboard summary for student inventories.
     *
     * IMPROVEMENT: Updated to use parent-child structure.
     */
    public function getDashboardSummary(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        if (!$campusId) {
            return response()->json([
                'error' => 'campus_id is required',
            ], 422);
        }

        $query = StudentInventory::where('campus_id', $campusId);

        $totalAssigned = $query->count();
        $totalQuantityAssigned = $query->withSum('items as total_quantity', 'quantity') ? $query->get()->sum('total_quantity') : 0;
        $totalQuantityReturned = $query->withSum('items as total_returned', 'returned_quantity') ? $query->get()->sum('total_returned') : 0;
        $pendingReturns = $query->pendingReturn()->count();
        $fullyReturned = $query->returned()->count();

        // Calculate values from the parent record
        $totalOriginalValue = $query->sum('total_amount');
        $totalDiscountAmount = $query->sum('total_discount');
        $totalFinalValue = $query->sum('final_amount');

        return response()->json([
            'totals' => [
                'assigned_items' => $totalAssigned,
                'total_quantity_assigned' => $totalQuantityAssigned,
                'total_quantity_returned' => $totalQuantityReturned,
                'total_quantity_remaining' => $totalQuantityAssigned - $totalQuantityReturned,
                'pending_returns' => $pendingReturns,
                'fully_returned' => $fullyReturned,
            ],
            'values' => [
                'total_original_value' => $totalOriginalValue,
                'total_discount_amount' => $totalDiscountAmount,
                'total_final_value' => $totalFinalValue,
                'remaining_inventory_value' => $totalFinalValue - ($query->returned()->sum('final_amount') ?? 0),
            ],
            'percentages' => [
                'return_rate' => $totalQuantityAssigned > 0
                    ? round(($totalQuantityReturned / $totalQuantityAssigned) * 100, 2)
                    : 0,
                'pending_rate' => $totalAssigned > 0
                    ? round(($pendingReturns / $totalAssigned) * 100, 2)
                    : 0,
            ],
        ]);
    }

    /**
     * Show the form for assigning inventory to a student.
     *
     * Renders the create page.
     */
    public function create(Request $request)
    {
        return inertia('inventory/StudentInventories/Create', [
            'campuses' => Campus::orderBy('name')->get(),
        ]);
    }

    /**
     * Show return form for a student inventory.
     *
     * Renders the return create page.
     */
    public function createReturn(Request $request, StudentInventory $studentInventory)
    {
        $studentInventory = StudentInventory::where('id', $studentInventory->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $studentInventory->load([
            'campus:id,name', 
            'student' => function ($q) { $q->select('id', 'registration_no', 'user_id'); },
            'student.user' => function ($q) { $q->select('id', 'name'); },
            'student.enrollmentRecords' => function ($q) { $q->active()->with(['class:id,name', 'section:id,name']); },
            'items.inventoryItem:id,name',
            'class',
            'section'
        ]);

        return inertia('inventory/StudentInventories/ReturnCreate', [
            'studentInventory' => [
                'id' => $studentInventory->id,
                'student_inventory_id' => $studentInventory->student_inventory_id,
                'campus_id' => $studentInventory->campus_id,
                'campus_name' => $studentInventory->campus?->name,
                'student_id' => $studentInventory->student_id,
                'student_name' => $studentInventory->student?->name,
                'registration_number' => $studentInventory->student?->registration_no,
                'class_name' => $studentInventory->class?->name ?? $studentInventory->student?->enrollmentRecords->first()?->class?->name,
                'section_name' => $studentInventory->section?->name ?? $studentInventory->student?->enrollmentRecords->first()?->section?->name,
                'total_amount' => $studentInventory->total_amount,
                'total_discount' => $studentInventory->total_discount,
                'final_amount' => $studentInventory->final_amount,
                'assigned_date' => $studentInventory->assigned_date,
                'status' => $studentInventory->status,
                'items' => $studentInventory->items->map(function ($item) {
                    // If unit_price_snapshot is 0 or null, try to get from PurchaseItem
                    $unitPrice = $item->unit_price_snapshot;
                    if (!$unitPrice || $unitPrice == 0) {
                        $purchaseItem = PurchaseItem::where('inventory_item_id', $item->inventory_item_id)
                            ->orderBy('id', 'desc')
                            ->first();
                        $unitPrice = $purchaseItem?->sale_rate ?? 0;
                    }
                    
                    return [
                        'id' => $item->id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'item_name_snapshot' => $item->item_name_snapshot,
                        'description_snapshot' => $item->description_snapshot,
                        'unit_price_snapshot' => $unitPrice,
                        'discount_amount' => $item->discount_amount,
                        'discount_percentage' => $item->discount_percentage,
                        'quantity' => $item->quantity,
                        'returned_quantity' => $item->returned_quantity,
                        'remaining_quantity' => $item->remainingQuantity(),
                        'total_value' => $item->totalValue(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Assign inventory item to a student.
     *
     * IMPROVEMENTS:
     * - Creates a single parent record with multiple items
     * - Added DB transaction for atomicity
     * - Added discount handling
     * - Enhanced snapshot
     * - Supports multiple items in a single assignment
     */
    public function assign(StoreStudentInventoryRequest $request)
    {
        $validated = $request->validated();

        try {
            \DB::transaction(function () use ($validated, &$studentInventoryRecord) {
                // Get student's current class and section from enrollment
                $enrollment = StudentEnrollmentRecord::where('student_id', $validated['student_id'])
                    ->active()
                    ->first();
                
                $studentClassId = $enrollment?->class_id;
                $studentSectionId = $enrollment?->section_id;

                // Handle multiple items or single item
                $items = [];
                if (isset($validated['items']) && is_array($validated['items'])) {
                    $items = $validated['items'];
                } else {
                    // Single item (backward compatibility)
                    $items = [[
                        'inventory_item_id' => $validated['inventory_item_id'],
                        'quantity' => $validated['quantity'],
                        'discount_amount' => $validated['discount_amount'] ?? 0,
                        'discount_percentage' => $validated['discount_percentage'] ?? 0,
                    ]];
                }

                // Calculate totals
                $totalAmount = 0;
                $totalDiscount = 0;

                // First pass: check stock availability
                foreach ($items as $itemData) {
                    $inventoryItemId = $itemData['inventory_item_id'];
                    $quantity = $itemData['quantity'];
                    $discountAmount = $itemData['discount_amount'] ?? 0;
                    $discountPercentage = $itemData['discount_percentage'] ?? 0;

                    // Check stock availability with lock
                    $stock = InventoryStock::where('campus_id', $validated['campus_id'])
                        ->where('inventory_item_id', $inventoryItemId)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || !$stock->isAvailable($quantity)) {
                        throw new \Exception('Insufficient stock available for item.');
                    }

                    $item = InventoryItem::findOrFail($inventoryItemId);
                    
                    // Get the latest sale_rate from purchase items
                    $purchaseItem = PurchaseItem::where('inventory_item_id', $inventoryItemId)
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    $unitPrice = $purchaseItem?->sale_rate ?? 0;
                    $purchaseRate = $purchaseItem?->purchase_rate ?? 0;
                    
                    $lineTotal = $quantity * $unitPrice;
                    $lineDiscount = $discountAmount + ($lineTotal * $discountPercentage / 100);

                    $totalAmount += $lineTotal;
                    $totalDiscount += $lineDiscount;
                }

                $finalAmount = $totalAmount - $totalDiscount;

                // Validate final amount is not negative
                if ($finalAmount < 0) {
                    throw new \Exception('Discount cannot exceed total amount. Please review your discount values.');
                }

                // Create parent student inventory record
                $studentInventoryRecord = StudentInventory::create([
                    'campus_id' => $validated['campus_id'],
                    'student_id' => $validated['student_id'],
                    'total_amount' => $totalAmount,
                    'total_discount' => $totalDiscount,
                    'final_amount' => $finalAmount,
                    'assigned_date' => $validated['assigned_date'] ?? now()->toDateString(),
                    'status' => 'assigned',
                    'student_class_id' => $studentClassId,
                    'student_section_id' => $studentSectionId,
                    'note' => $validated['note'] ?? null,
                ]);

                // Second pass: create items and deduct stock
                foreach ($items as $itemData) {
                    $inventoryItemId = $itemData['inventory_item_id'];
                    $quantity = $itemData['quantity'];
                    $discountAmount = $itemData['discount_amount'] ?? 0;
                    $discountPercentage = $itemData['discount_percentage'] ?? 0;

                    $item = InventoryItem::findOrFail($inventoryItemId);
                    
                    // Get the latest rates from purchase items
                    $purchaseItem = PurchaseItem::where('inventory_item_id', $inventoryItemId)
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    $unitPrice = $purchaseItem?->sale_rate ?? 0;
                    $purchaseRate = $purchaseItem?->purchase_rate ?? 0;

                    // Create student inventory item
                    StudentInventoryItem::create([
                        'student_inventory_record_id' => $studentInventoryRecord->id,
                        'campus_id' => $validated['campus_id'],
                        'student_id' => $validated['student_id'],
                        'inventory_item_id' => $inventoryItemId,
                        'quantity' => $quantity,
                        'returned_quantity' => 0,
                        'unit_price_snapshot' => $unitPrice,
                        'purchase_rate_snapshot' => $purchaseRate,
                        'item_name_snapshot' => $item->name,
                        'description_snapshot' => $item->description,
                        'discount_amount' => $discountAmount,
                        'discount_percentage' => $discountPercentage,
                        'assigned_date' => $validated['assigned_date'] ?? now()->toDateString(),
                        'status' => 'assigned',
                    ]);

                    // Update stock using helper method
                    $stock = InventoryStock::where('campus_id', $validated['campus_id'])
                        ->where('inventory_item_id', $inventoryItemId)
                        ->first();
                    $stock->deductForAssignment($quantity);
                }
            });

            return redirect()->route('inventory.student-manage')
                ->with('success', 'Inventory assigned to student successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign inventory: ' . $e->getMessage());
        }
    }

    /**
     * Get enhanced item snapshot for audit purposes.
     */
    protected function getItemSnapshot(InventoryItem $item): array
    {
        return [
            'item_name' => $item->name,
            'description' => $item->description,
            'purchase_rate' => 0,
            'sale_rate' => 0,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Process return of inventory item.
     *
     * IMPROVEMENTS:
     * - Uses new parent-child table structure (student_inventory_returns + student_inventory_return_items)
     * - Supports multiple items in a single return
     */
    public function processReturn(Request $request)
    {
        $validated = $request->validate([
            'student_inventory_record_id' => 'required|exists:student_inventory_records,id',
            'campus_id' => 'required|exists:campuses,id',
            'items' => 'required|array|min:1',
            'items.*.student_inventory_item_id' => 'required|exists:student_inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.return_price' => 'nullable|numeric|min:0',
            'items.*.reason_id' => 'nullable|exists:reasons,id',
            'items.*.custom_reason' => 'nullable|string|max:500',
            'return_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        try {
            \DB::transaction(function () use ($validated) {
                // Get the student inventory record
                $record = StudentInventory::findOrFail($validated['student_inventory_record_id']);
                
                // Calculate totals
                $totalQuantity = 0;
                $totalAmount = 0;
                $itemsData = [];

                foreach ($validated['items'] as $itemData) {
                    $studentInventoryItem = StudentInventoryItem::findOrFail($itemData['student_inventory_item_id']);
                    
                    // Validate return quantity
                    $maxReturnable = $studentInventoryItem->remainingQuantity();
                    if ($itemData['quantity'] > $maxReturnable) {
                        throw new \Exception('Return quantity for item "' . $studentInventoryItem->item_name_snapshot . '" exceeds remaining quantity (' . $maxReturnable . ').');
                    }

                    $quantity = $itemData['quantity'];
                    // Use return_price if provided, otherwise use the original unit price
                    $unitPrice = isset($itemData['return_price']) && $itemData['return_price'] > 0 
                        ? $itemData['return_price'] 
                        : ($studentInventoryItem->unit_price_snapshot ?? 0);
                    $lineTotal = $quantity * $unitPrice;

                    $totalQuantity += $quantity;
                    $totalAmount += $lineTotal;

                    // Restore stock
                    $stock = InventoryStock::firstOrCreate(
                        [
                            'campus_id' => $validated['campus_id'],
                            'inventory_item_id' => $studentInventoryItem->inventory_item_id,
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                        ]
                    );
                    $stock->restoreFromReturn($quantity);

                    // Update returned quantity on the item
                    $studentInventoryItem->returned_quantity += $quantity;
                    $studentInventoryItem->save();

                    $itemsData[] = [
                        'item_id' => $studentInventoryItem->id,
                        'inventory_item_id' => $studentInventoryItem->inventory_item_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_amount' => $lineTotal,
                        'return_price' => $itemData['return_price'] ?? null,
                        'reason_id' => $itemData['reason_id'] ?? null,
                        'custom_reason' => $itemData['custom_reason'] ?? null,
                        'item_snapshot' => [
                            'item_name' => $studentInventoryItem->item_name_snapshot,
                            'description' => $studentInventoryItem->description_snapshot,
                            'unit_price' => $unitPrice,
                        ],
                    ];
                }

                // Determine status
                $allItems = $record->items;
                $hasRemaining = $allItems->contains(fn($item) => $item->remainingQuantity() > 0);
                $status = $hasRemaining ? 'partial_return' : 'returned';

                // Create parent return record
                $returnRecord = StudentInventoryReturn::create([
                    'return_id' => StudentInventoryReturn::generateReturnId(),
                    'campus_id' => $validated['campus_id'],
                    'student_id' => $record->student_id,
                    'record_id' => $record->id,
                    'total_quantity' => $totalQuantity,
                    'total_amount' => $totalAmount,
                    'status' => $status,
                    'return_date' => $validated['return_date'],
                    'note' => $validated['note'] ?? null,
                ]);

                // Create return items
                foreach ($itemsData as $itemData) {
                    StudentInventoryReturnItem::create([
                        'return_id' => $returnRecord->id,
                        'item_id' => $itemData['item_id'],
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total_amount' => $itemData['total_amount'],
                        'return_price' => $itemData['return_price'] ?? null,
                        'reason_id' => $itemData['reason_id'] ?? null,
                        'custom_reason' => $itemData['custom_reason'] ?? null,
                        'item_snapshot' => $itemData['item_snapshot'],
                    ]);
                }

                // Update parent record status
                $record->status = $status;
                $record->save();
            });

            return response()->json([
                'message' => 'Return processed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process return.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process return of inventory item (legacy single item).
     *
     * IMPROVEMENTS:
     * - Added DB transaction
     * - Uses helper methods for stock
     * - Enhanced snapshot
     */
    public function return(StoreReturnRequest $request, StudentInventory $studentInventory)
    {
        /** @var StudentInventory $studentInventory */
        $studentInventory = StudentInventory::where('id', $studentInventory->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $validated = $request->validated();
        $returnQuantity = (int) $validated['quantity'];

        // Validate return quantity
        $maxReturnable = $studentInventory->remainingQuantity();
        if ($returnQuantity > $maxReturnable) {
            return back()->withErrors([
                'quantity' => 'Return quantity (' . $returnQuantity . ') cannot exceed remaining quantity (' . $maxReturnable . ').',
            ]);
        }

        try {
            \DB::transaction(function () use ($studentInventory, $returnQuantity, $validated) {
                // Get or create stock record (available_quantity is auto-calculated)
                $stock = InventoryStock::firstOrCreate(
                    [
                        'campus_id' => $studentInventory->campus_id,
                        'inventory_item_id' => $studentInventory->inventory_item_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                    ]
                );

                $stock->restoreFromReturn($returnQuantity);

                // Create return record with enhanced snapshot
                $returnSnapshot = ReturnModel::createSnapshot($studentInventory);
                ReturnModel::create([
                    'campus_id' => $studentInventory->campus_id,
                    'student_inventory_id' => $studentInventory->id,
                    'quantity' => $returnQuantity,
                    'return_date' => $validated['return_date'] ?? now()->toDateString(),
                    'note' => $validated['note'] ?? null,
                    'item_snapshot' => $returnSnapshot,
                    'item_name_snapshot' => $studentInventory->item_name_snapshot,
                    'description_snapshot' => $studentInventory->description_snapshot,
                    'unit_price_snapshot' => $studentInventory->unit_price_snapshot,
                    'discount_snapshot' => $studentInventory->getDiscountInfo(),
                ]);

                // Update student inventory
                $studentInventory->returned_quantity += $returnQuantity;
                $studentInventory->status = $studentInventory->remainingQuantity() <= 0 ? 'returned' : 'partial_return';
                $studentInventory->save();
            });

            return redirect()->route('inventory.student-manage')
                ->with('success', 'Return processed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process return: ' . $e->getMessage());
        }
    }

    /**
     * Get students for dropdown with their current assignments.
     *
     * IMPROVEMENT: Added campus filtering.
     */
    public function getStudentsWithInventory(Request $request): JsonResponse
    {
        $campusId = $request->get('campus_id');

        if (!$campusId) {
            return response()->json(['error' => 'campus_id is required'], 422);
        }

        // Get students through their student inventories for the campus
        $students = Student::whereHas('studentInventories', function ($q) use ($campusId) {
                $q->where('campus_id', $campusId);
            })
            ->with(['studentInventories' => function ($q) use ($campusId) {
                $q->where('campus_id', $campusId)
                    ->with(['inventoryItem:id,name'])
                    ->select(['id', 'student_id', 'inventory_item_id', 'quantity', 'returned_quantity', 'status', 'item_name_snapshot', 'discount_amount', 'discount_percentage']);
            }])
            ->orderBy('registration_no')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_number' => $student->registration_no,
                    'current_assignments' => $student->studentInventories->map(function ($inv) {
                        return [
                            'id' => $inv->id,
                            'item_name' => $inv->item_name_snapshot,
                            'quantity' => $inv->quantity,
                            'returned_quantity' => $inv->returned_quantity,
                            'remaining' => $inv->quantity - $inv->returned_quantity,
                            'status' => $inv->status,
                            'discount_amount' => $inv->discount_amount,
                            'discount_percentage' => $inv->discount_percentage,
                        ];
                    }),
                ];
            });

        return response()->json($students);
    }

    /**
     * Check if student can return specific quantity.
     *
     * IMPROVEMENT: Enhanced response.
     */
    public function checkReturnAvailability(Request $request, StudentInventory $studentInventory): JsonResponse
    {
        /** @var StudentInventory $studentInventory */
        $studentInventory = StudentInventory::where('id', $studentInventory->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $quantity = (int) $request->get('quantity', 0);
        $maxReturnable = $studentInventory->remainingQuantity();
        $isAvailable = $quantity <= $maxReturnable && $quantity > 0;

        return response()->json([
            'available' => $isAvailable,
            'max_returnable' => $maxReturnable,
            'requested_quantity' => $quantity,
            'remaining_quantity' => $maxReturnable,
            'status' => $studentInventory->status,
            'discount_info' => $studentInventory->getDiscountInfo(),
            'message' => $isAvailable
                ? 'Return is allowed.'
                : 'Cannot return more than remaining quantity (' . $maxReturnable . ').',
        ]);
    }

    /**
     * Show a student inventory record.
     */
    public function show(Request $request, StudentInventory $studentInventory)
    {
        /** @var StudentInventory $studentInventory */
        $studentInventory = StudentInventory::where('id', $studentInventory->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $studentInventory->load([
            'campus:id,name', 
            'student' => function ($q) { $q->select('id', 'registration_no', 'user_id'); },
            'student.user' => function ($q) { $q->select('id', 'name'); },
            'student.enrollmentRecords' => function ($q) { $q->active()->with(['class:id,name', 'section:id,name']); },
            'items.inventoryItem:id,name',
            'class',
            'section'
        ]);

        return inertia('inventory/StudentInventories/Show', [
            'studentInventory' => [
                'id' => $studentInventory->id,
                'student_inventory_id' => $studentInventory->student_inventory_id,
                'campus_id' => $studentInventory->campus_id,
                'campus_name' => $studentInventory->campus?->name,
                'student_id' => $studentInventory->student_id,
                'student_name' => $studentInventory->student?->name,
                'registration_number' => $studentInventory->student?->registration_no,
                'class_name' => $studentInventory->class?->name ?? $studentInventory->student?->enrollmentRecords->first()?->class?->name,
                'section_name' => $studentInventory->section?->name ?? $studentInventory->student?->enrollmentRecords->first()?->section?->name,
                'total_amount' => $studentInventory->total_amount,
                'total_discount' => $studentInventory->total_discount,
                'final_amount' => $studentInventory->final_amount,
                'assigned_date' => $studentInventory->assigned_date,
                'status' => $studentInventory->status,
                'created_at' => $studentInventory->created_at,
                'items' => $studentInventory->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'item_name_snapshot' => $item->item_name_snapshot,
                        'description_snapshot' => $item->description_snapshot,
                        'unit_price_snapshot' => $item->unit_price_snapshot,
                        'discount_amount' => $item->discount_amount,
                        'discount_percentage' => $item->discount_percentage,
                        'quantity' => $item->quantity,
                        'returned_quantity' => $item->returned_quantity,
                        'remaining_quantity' => $item->remainingQuantity(),
                        'total_value' => $item->totalValue(),
                        'inventory_item' => $item->inventoryItem ? [
                            'id' => $item->inventoryItem->id,
                            'name' => $item->inventoryItem->name
                        ] : null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Show a student inventory return record details.
     */
    public function showReturn(Request $request, StudentInventoryReturn $studentInventoryReturn)
    {
        $studentInventoryReturn = StudentInventoryReturn::where('id', $studentInventoryReturn->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $studentInventoryReturn->load([
            'campus:id,name', 
            'studentInventoryRecord' => function ($q) {
                $q->with(['student' => function ($sq) { 
                    $sq->select('id', 'registration_no', 'user_id'); 
                }]);
            },
            'studentInventoryRecord.student.user:id,name',
            'studentInventoryRecord.class:id,name',
            'studentInventoryRecord.section:id,name',
            'items'
        ]);

        // Get student from the inventory record (most reliable source)
        $student = $studentInventoryReturn->studentInventoryRecord?->student;
        
        // Fallback: try direct student relationship
        if (!$student) {
            $studentInventoryReturn->load('student.user:id,name');
            $student = $studentInventoryReturn->student;
        }

        return inertia('inventory/StudentInventories/ReturnShow', [
            'returnRecord' => [
                'id' => $studentInventoryReturn->id,
                'return_id' => $studentInventoryReturn->return_id,
                'campus_id' => $studentInventoryReturn->campus_id,
                'campus_name' => $studentInventoryReturn->campus?->name,
                'student_id' => $studentInventoryReturn->student_id,
                'student_name' => $student?->name ?? 'Student #' . $studentInventoryReturn->student?->registration_no,
                'registration_number' => $student?->registration_no ?? $studentInventoryReturn->student?->registration_no,
                'total_quantity' => $studentInventoryReturn->total_quantity,
                'total_amount' => $studentInventoryReturn->total_amount,
                'status' => $studentInventoryReturn->status,
                'return_date' => $studentInventoryReturn->return_date,
                'note' => $studentInventoryReturn->note,
                'created_at' => $studentInventoryReturn->created_at,
                'items' => $studentInventoryReturn->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_name_snapshot' => $item->item_snapshot['item_name'] ?? 'N/A',
                        'description_snapshot' => $item->item_snapshot['description'] ?? null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'return_price' => $item->return_price,
                        'total_amount' => $item->total_amount,
                        'reason_id' => $item->reason_id,
                        'custom_reason' => $item->custom_reason,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Soft delete a student inventory assignment.
     */
    public function destroy(Request $request, StudentInventory $studentInventory)
    {
        /** @var StudentInventory $studentInventory */
        $studentInventory = StudentInventory::where('id', $studentInventory->id)
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->firstOrFail();

        $studentInventory->delete();

        return back()->with('success', 'Inventory assignment deleted successfully.');
    }

    /**
     * Restore a soft-deleted student inventory.
     */
    public function restore(Request $request, $id)
    {
        $studentInventory = StudentInventory::withTrashed()
            ->when($request->get('campus_id'), fn($q) => $q->where('campus_id', $request->get('campus_id')))
            ->findOrFail($id);

        $studentInventory->restore();

        return back()->with('success', 'Inventory assignment restored successfully.');
    }
}
