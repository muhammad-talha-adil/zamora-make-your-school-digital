<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Month;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Services\Fee\VoucherGenerationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeVoucherController extends Controller
{
    /**
     * API: Get vouchers list as JSON (for AJAX requests)
     */
    public function getVouchersList(Request $request)
    {
        $query = FeeVoucher::with(['student', 'voucherMonth', 'campus']);

        // Apply campus filter
        if ($request->filled('campus_id')) {
            $query->where('campus_id', (int) $request->campus_id);
        }

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', (int) $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', (int) $request->section_id);
        }

        if ($request->filled('month_id')) {
            $query->where('voucher_month_id', (int) $request->month_id);
        }

        if ($request->filled('year')) {
            $query->where('voucher_year', (int) $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('voucher_no', 'like', '%'.$request->search.'%')
                    ->orWhereHas('student', function ($sq) use ($request) {
                        $sq->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('registration_number', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $vouchers = $query->latest()->paginate(50);

        // Transform the data
        $vouchers->getCollection()->transform(function ($voucher) {
            return [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'student_id' => $voucher->student_id,
                'student' => $voucher->student ? [
                    'id' => $voucher->student->id,
                    'name' => $voucher->student->name,
                    'registration_number' => $voucher->student->registration_number,
                ] : null,
                'voucher_month_id' => $voucher->voucher_month_id,
                'voucher_month' => $voucher->voucherMonth ? [
                    'id' => $voucher->voucherMonth->id,
                    'name' => $voucher->voucherMonth->name,
                ] : null,
                'voucher_year' => $voucher->voucher_year,
                'issue_date' => $voucher->issue_date,
                'due_date' => $voucher->due_date,
                'status' => $voucher->status,
                'gross_amount' => (float) $voucher->gross_amount,
                'discount_amount' => (float) $voucher->discount_amount,
                'fine_amount' => (float) $voucher->fine_amount,
                'net_amount' => (float) $voucher->net_amount,
                'paid_amount' => (float) $voucher->paid_amount,
                'balance_amount' => (float) $voucher->balance_amount,
            ];
        });

        return response()->json($vouchers);
    }

    /**
     * Display a listing of fee vouchers.
     */
    public function index(Request $request)
    {
        $query = FeeVoucher::with(['student', 'voucherMonth', 'campus']);

        // Apply campus filter (required for multi-campus security)
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        // Apply filters
        if ($request->filled('class_id')) {
            $query->where('class_id', (int) $request->class_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', (int) $request->section_id);
        }

        if ($request->filled('month_id')) {
            $query->where('voucher_month_id', (int) $request->month_id);
        }

        if ($request->filled('year')) {
            $query->where('voucher_year', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('voucher_no', 'like', '%'.$request->search.'%')
                    ->orWhereHas('student', function ($sq) use ($request) {
                        $sq->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('registration_number', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $vouchers = $query->latest()->paginate(50);

        // Transform the data to ensure consistent structure
        $vouchers->getCollection()->transform(function ($voucher) {
            return [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'student_id' => $voucher->student_id,
                'student' => $voucher->student ? [
                    'id' => $voucher->student->id,
                    'name' => $voucher->student->name,
                    'registration_number' => $voucher->student->registration_number,
                ] : null,
                'voucher_month_id' => $voucher->voucher_month_id,
                'voucher_month' => $voucher->voucherMonth ? [
                    'id' => $voucher->voucherMonth->id,
                    'name' => $voucher->voucherMonth->name,
                ] : null,
                'voucher_year' => $voucher->voucher_year,
                'issue_date' => $voucher->issue_date,
                'due_date' => $voucher->due_date,
                'status' => $voucher->status,
                'gross_amount' => (float) $voucher->gross_amount,
                'discount_amount' => (float) $voucher->discount_amount,
                'fine_amount' => (float) $voucher->fine_amount,
                'net_amount' => (float) $voucher->net_amount,
                'paid_amount' => (float) $voucher->paid_amount,
                'balance_amount' => (float) $voucher->balance_amount,
            ];
        });

        return Inertia::render('Fee/Vouchers/Index', [
            'vouchers' => $vouchers,
            'months' => Month::select('id', 'name')->orderBy('month_number')->get(),
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::select('id', 'name')->orderBy('name')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->orderBy('name')->get(),
            'filters' => $request->only(['campus_id', 'class_id', 'section_id', 'month_id', 'year', 'status', 'search']),
        ]);
    }

    /**
     * Show the form for generating vouchers.
     */
    public function generateForm()
    {
        return Inertia::render('Fee/Vouchers/Generate', [
            'sessions' => Session::select('id', 'name', 'start_date', 'end_date')->orderBy('start_date', 'desc')->get(),
            'months' => Month::select('id', 'name', 'month_number')->orderBy('month_number')->get(),
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::select('id', 'name')->orderBy('id')->get(),
            'sections' => Section::select('id', 'name', 'class_id')->orderBy('name')->get(),
            'feeHeads' => FeeHead::active()->select('id', 'name', 'code', 'category')->ordered()->get(),
        ]);
    }

    /**
     * Get applicable fee structure for class/session/campus.
     * Returns the fee structure that would be used for voucher generation.
     */
    public function getApplicableFeeStructure(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'campus_id' => 'required|exists:campuses,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
        ]);

        $sessionId = $request->session_id;
        $campusId = $request->campus_id;
        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $effectiveDate = now()->toDateString();

        // Priority: Section -> Class -> Campus
        // First check: Section-specific fee structure
        if ($sectionId) {
            $structure = FeeStructure::active()
                ->forSession($sessionId)
                ->forCampus($campusId)
                ->forClass($classId)
                ->forSection($sectionId)
                ->effectiveOn($effectiveDate)
                ->with(['items.feeHead'])
                ->first();

            if ($structure) {
                return response()->json([
                    'found' => true,
                    'structure' => $this->formatFeeStructure($structure),
                    'source' => 'section',
                ]);
            }
        }

        // Second check: Class-specific fee structure
        $structure = FeeStructure::active()
            ->forSession($sessionId)
            ->forCampus($campusId)
            ->forClass($classId)
            ->whereNull('section_id')
            ->effectiveOn($effectiveDate)
            ->with(['items.feeHead'])
            ->first();

        if ($structure) {
            return response()->json([
                'found' => true,
                'structure' => $this->formatFeeStructure($structure),
                'source' => 'class',
            ]);
        }

        // Third check: Campus-specific fee structure
        $structure = FeeStructure::active()
            ->forSession($sessionId)
            ->forCampus($campusId)
            ->whereNull('class_id')
            ->effectiveOn($effectiveDate)
            ->with(['items.feeHead'])
            ->first();

        if ($structure) {
            return response()->json([
                'found' => true,
                'structure' => $this->formatFeeStructure($structure),
                'source' => 'campus',
            ]);
        }

        // No fee structure found
        return response()->json([
            'found' => false,
            'structure' => null,
            'source' => null,
            'message' => 'No fee structure found for this class. You can still generate vouchers using manual fees or create a fee structure first.',
        ]);
    }

    /**
     * Format fee structure for response.
     */
    protected function formatFeeStructure(FeeStructure $structure): array
    {
        $items = $structure->items->map(function ($item) {
            return [
                'id' => $item->id,
                'fee_head_id' => $item->fee_head_id,
                'fee_head' => $item->feeHead ? [
                    'id' => $item->feeHead->id,
                    'name' => $item->feeHead->name,
                    'code' => $item->feeHead->code,
                    'category' => $item->feeHead->category,
                ] : null,
                'amount' => (float) $item->amount,
                'frequency' => $item->frequency,
            ];
        });

        return [
            'id' => $structure->id,
            'title' => $structure->title,
            'effective_from' => $structure->effective_from?->format('Y-m-d'),
            'effective_to' => $structure->effective_to?->format('Y-m-d'),
            'is_default' => $structure->is_default,
            'items' => $items,
            'total_monthly' => $items->where('frequency', 'monthly')->sum('amount'),
            'total_annual' => $items->where('frequency', 'annual')->sum('amount'),
        ];
    }

    /**
     * Generate vouchers for single or multiple months.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:academic_sessions,id',
            'campus_id' => 'required|exists:campuses,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'month_ids' => 'required|array|min:1',
            'month_ids.*' => 'required|exists:months,id',
            'year' => 'required|integer|min:2020|max:2100',
            'include_previous_unpaid' => 'nullable|boolean',
            'custom_fee_heads' => 'nullable|array',
            'custom_fee_heads.*.fee_head_id' => 'required|exists:fee_heads,id',
            'custom_fee_heads.*.amount' => 'required|numeric|min:0',
        ]);

        $voucherService = app(VoucherGenerationService::class);

        // Get month numbers from month IDs
        $months = Month::whereIn('id', $validated['month_ids'])->get();
        $monthNumbers = $months->pluck('month_number')->toArray();

        $filters = [
            'session_id' => $validated['session_id'],
            'campus_id' => $validated['campus_id'],
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'] ?? null,
            'include_previous_unpaid' => $validated['include_previous_unpaid'] ?? false,
            'custom_fee_heads' => $validated['custom_fee_heads'] ?? [],
        ];

        // Remove null values
        $filters = array_filter($filters, fn ($value) => $value !== null);

        $totalGenerated = 0;
        $totalSkipped = 0;
        $allErrors = [];

        // Sort month numbers to process in order
        sort($monthNumbers);

        foreach ($monthNumbers as $monthNumber) {
            $result = $voucherService->generateMonthlyVouchers(
                $monthNumber,
                $validated['year'],
                $filters
            );

            $totalGenerated += $result['generated'];
            $totalSkipped += $result['skipped'];

            if (! empty($result['errors'])) {
                $allErrors = array_merge($allErrors, $result['errors']);
            }
        }

        if (empty($allErrors)) {
            return redirect()->route('fee.vouchers.index')
                ->with('success', "Generated {$totalGenerated} vouchers. Skipped {$totalSkipped} existing vouchers.");
        }

        return back()->with('warning',
            "Generated {$totalGenerated} vouchers. {$totalSkipped} skipped. ".
            count($allErrors).' errors occurred.'
        );
    }

    /**
     * Display the specified voucher.
     */
    public function show(FeeVoucher $voucher)
    {
        $voucher->load([
            'student',
            'voucherMonth',
            'campus',
            'schoolClass',
            'section',
            'items.feeHead',
            'paymentAllocations.payment',
        ]);

        // Transform payment allocations to flat payments array for the UI
        $payments = $voucher->paymentAllocations->map(function ($allocation) {
            return [
                'id' => $allocation->id,
                'payment_date' => $allocation->payment?->payment_date?->format('Y-m-d'),
                'receipt_no' => $allocation->payment?->receipt_no,
                'allocated_amount' => (float) $allocation->allocated_amount,
                'payment_method' => $allocation->payment?->payment_method,
            ];
        })->toArray();

        return Inertia::render('Fee/Vouchers/Show', [
            'voucher' => [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'student' => $voucher->student,
                'voucher_month' => $voucher->voucherMonth,
                'voucher_year' => $voucher->voucher_year,
                'issue_date' => $voucher->issue_date?->format('Y-m-d'),
                'due_date' => $voucher->due_date?->format('Y-m-d'),
                'status' => $voucher->status,
                'gross_amount' => (float) $voucher->gross_amount,
                'discount_amount' => (float) $voucher->discount_amount,
                'fine_amount' => (float) $voucher->fine_amount,
                'net_amount' => (float) $voucher->net_amount,
                'paid_amount' => (float) $voucher->paid_amount,
                'balance_amount' => (float) $voucher->balance_amount,
                'campus' => $voucher->campus,
                'class' => $voucher->schoolClass,
                'section' => $voucher->section,
                'items' => $voucher->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'fee_head_id' => $item->fee_head_id,
                        'fee_head' => $item->feeHead,
                        'description' => $item->description,
                        'amount' => (float) $item->amount,
                        'discount_amount' => (float) $item->discount_amount,
                        'fine_amount' => (float) $item->fine_amount,
                        'net_amount' => (float) $item->net_amount,
                    ];
                })->toArray(),
                'payments' => $payments,
            ],
        ]);
    }

    /**
     * Display printable voucher.
     */
    public function print(FeeVoucher $voucher)
    {
        $voucher->load([
            'student',
            'voucherMonth',
            'items.feeHead',
            'campus',
            'schoolClass',
            'section',
        ]);

        $school = School::where('is_active', true)->first();

        return response()->view('fee.vouchers.print', [
            'voucher' => $voucher,
            'school' => $school,
        ])->header('Content-Type', 'text/html');
    }

    /**
     * Print multiple vouchers in batch.
     */
    public function printBatch(Request $request)
    {
        $voucherIds = $request->voucher_ids
            ? explode(',', $request->voucher_ids)
            : [];

        if (empty($voucherIds)) {
            return redirect()->route('fee.vouchers.index')
                ->with('error', 'No vouchers selected for printing');
        }

        $vouchers = FeeVoucher::with([
            'student',
            'voucherMonth',
            'items.feeHead',
            'campus',
            'schoolClass',
            'section',
        ])->whereIn('id', $voucherIds)->get();

        $school = School::where('is_active', true)->first();

        return response()->view('fee.vouchers.print-batch', [
            'vouchers' => $vouchers,
            'school' => $school,
        ])->header('Content-Type', 'text/html');
    }

    /**
     * Cancel a voucher.
     */
    public function cancel(FeeVoucher $voucher)
    {
        if ($voucher->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot cancel a paid voucher.']);
        }

        if ($voucher->paid_amount > 0) {
            return back()->withErrors(['error' => 'Cannot cancel a voucher with payments. Please refund first.']);
        }

        $voucher->update(['status' => 'cancelled']);

        return back()->with('success', 'Voucher cancelled successfully.');
    }

    /**
     * Get vouchers by student.
     */
    public function getByStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $vouchers = FeeVoucher::where('student_id', $request->student_id)
            ->with(['voucherMonth'])
            ->orderBy('voucher_year', 'desc')
            ->orderBy('voucher_month_id', 'desc')
            ->get();

        return response()->json($vouchers);
    }

    /**
     * Get unpaid vouchers.
     */
    public function getUnpaid(Request $request)
    {
        $query = FeeVoucher::unpaid()->with(['student', 'voucherMonth']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        return response()->json($query->get());
    }

    /**
     * Get overdue vouchers.
     */
    public function getOverdue(Request $request)
    {
        $query = FeeVoucher::where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('due_date', '<', now()->toDateString())
                    ->whereIn('status', ['unpaid', 'partial']);
            })
            ->with(['student', 'voucherMonth', 'campus']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        return response()->json($query->get());
    }

    /**
     * Show the form for editing the specified voucher.
     */
    public function edit(FeeVoucher $voucher)
    {
        $voucher->load(['student', 'voucherMonth', 'items.feeHead', 'adjustments']);

        return Inertia::render('Fee/Vouchers/Edit', [
            'voucher' => $voucher,
        ]);
    }

    /**
     * Update the specified voucher.
     */
    public function update(Request $request, FeeVoucher $voucher)
    {
        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $voucher->update($validated);

        return redirect()->route('fee.vouchers.show', $voucher->id)
            ->with('success', 'Voucher updated successfully.');
    }

    /**
     * Add a new fee head item to the voucher.
     */
    public function addItem(Request $request, FeeVoucher $voucher)
    {
        // Prevent changes to paid vouchers
        if ($voucher->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot modify a paid voucher.']);
        }

        $validated = $request->validate([
            'fee_head_id' => 'required|exists:fee_heads,id',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        // Check if this fee head already exists on the voucher
        $existingItem = $voucher->items()->where('fee_head_id', $validated['fee_head_id'])->first();
        if ($existingItem) {
            return back()->withErrors(['error' => 'This fee head already exists on the voucher.']);
        }

        $feeHead = FeeHead::find($validated['fee_head_id']);

        $voucher->items()->create([
            'fee_head_id' => $validated['fee_head_id'],
            'description' => $validated['description'] ?? $feeHead->name,
            'quantity' => 1,
            'unit_price' => $validated['amount'],
            'amount' => $validated['amount'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'net_amount' => $validated['amount'] - ($validated['discount_amount'] ?? 0),
            'source_type' => 'manual',
            'reference_id' => null,
        ]);

        // Recalculate voucher totals
        $this->recalculateVoucherTotals($voucher);

        return back()->with('success', 'Fee item added successfully.');
    }

    /**
     * Update an existing voucher item.
     */
    public function updateItem(Request $request, FeeVoucher $voucher, FeeVoucherItem $item)
    {
        // Prevent changes to paid vouchers
        if ($voucher->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot modify a paid voucher.']);
        }

        // Ensure the item belongs to this voucher
        if ($item->fee_voucher_id !== $voucher->id) {
            return back()->withErrors(['error' => 'Item does not belong to this voucher.']);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $item->update([
            'description' => $validated['description'] ?? $item->description,
            'unit_price' => $validated['amount'],
            'amount' => $validated['amount'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'net_amount' => $validated['amount'] - ($validated['discount_amount'] ?? 0),
        ]);

        // Recalculate voucher totals
        $this->recalculateVoucherTotals($voucher);

        return back()->with('success', 'Fee item updated successfully.');
    }

    /**
     * Remove a fee head item from the voucher.
     */
    public function removeItem(FeeVoucher $voucher, FeeVoucherItem $item)
    {
        // Prevent changes to paid vouchers
        if ($voucher->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot modify a paid voucher.']);
        }

        // Ensure the item belongs to this voucher
        if ($item->fee_voucher_id !== $voucher->id) {
            return back()->withErrors(['error' => 'Item does not belong to this voucher.']);
        }

        $item->delete();

        // Recalculate voucher totals
        $this->recalculateVoucherTotals($voucher);

        return back()->with('success', 'Fee item removed successfully.');
    }

    /**
     * Recalculate voucher totals after item changes.
     */
    protected function recalculateVoucherTotals(FeeVoucher $voucher): void
    {
        $items = $voucher->items;

        $grossAmount = $items->sum('amount');
        $discountAmount = $items->sum('discount_amount');
        $netAmount = $items->sum('net_amount');

        $voucher->update([
            'gross_amount' => $grossAmount,
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'balance_amount' => $netAmount - $voucher->paid_amount,
        ]);
    }
}
