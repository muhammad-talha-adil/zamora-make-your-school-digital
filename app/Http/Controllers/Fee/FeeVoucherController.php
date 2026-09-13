<?php

namespace App\Http\Controllers\Fee;

use App\Enums\Fee\AdjustmentType;
use App\Enums\Fee\FineType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\AddVoucherAdjustmentRequest;
use App\Http\Requests\Fee\AddVoucherItemRequest;
use App\Http\Requests\Fee\GenerateVouchersBulkRequest;
use App\Http\Requests\Fee\GenerateVouchersRequest;
use App\Http\Requests\Fee\GetApplicableFeeStructureRequest;
use App\Http\Requests\Fee\UpdateFeeVoucherRequest;
use App\Http\Requests\Fee\UpdateVoucherItemRequest;
use App\Models\Campus;
use App\Models\Fee\FeeFineRule;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Fee\FeeVoucherPrintLog;
use App\Models\Month;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Session;
use App\Models\Student;
use App\Services\Fee\VoucherGenerationService;
use App\Services\Finance\StudentBillingService;
use App\Services\Finance\UnifiedAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class FeeVoucherController extends Controller
{
    public function __construct(
        protected StudentBillingService $studentBillingService,
        protected UnifiedAccountingService $accountingService
    ) {}

    /**
     * API: Get vouchers list as JSON (for AJAX requests)
     */
    public function getVouchersList(Request $request)
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        $query = FeeVoucher::query()->visibleTo($request->user())->with(['student', 'voucherMonth', 'campus']);

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

        $perPage = $request->input('per_page', 50);
        $vouchers = $query->latest()->paginate($perPage);

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
        Gate::authorize('viewAny', FeeVoucher::class);

        $query = FeeVoucher::query()->visibleTo($request->user())->with(['student', 'voucherMonth', 'campus']);

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

        $perPage = $request->input('per_page', 50);
        $vouchers = $query->latest()->paginate($perPage);

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
     * `fee.vouchers.create` — the route names it that; the screen is the same
     * one `generate.form` renders, since a voucher is never hand-typed here.
     */
    public function create()
    {
        return $this->generateForm();
    }

    /**
     * Show the form for generating vouchers.
     */
    public function generateForm()
    {
        Gate::authorize('generate', FeeVoucher::class);

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
    public function getApplicableFeeStructure(GetApplicableFeeStructureRequest $request)
    {
        Gate::authorize('generate', FeeVoucher::class);

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
    public function generate(GenerateVouchersRequest $request)
    {
        Gate::authorize('generate', FeeVoucher::class);

        $validated = $request->validated();

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
            'include_inventory_dues' => $validated['include_inventory_dues'] ?? false,
            'include_transport_dues' => $validated['include_transport_dues'] ?? false,
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
     * The same run as `generate()`, for every class in the campus at once —
     * the whole school's monthly billing in one call, rather than one class at
     * a time.
     */
    public function generateBulk(GenerateVouchersBulkRequest $request)
    {
        Gate::authorize('generate', FeeVoucher::class);

        $validated = $request->validated();

        $voucherService = app(VoucherGenerationService::class);
        $months = Month::whereIn('id', $validated['month_ids'])->get();
        $monthNumbers = $months->pluck('month_number')->sort()->values()->all();

        $classIds = SchoolClass::where('is_active', true)->pluck('id');

        $totalGenerated = 0;
        $totalSkipped = 0;
        $allErrors = [];

        foreach ($classIds as $classId) {
            $filters = array_filter([
                'session_id' => $validated['session_id'],
                'campus_id' => $validated['campus_id'],
                'class_id' => $classId,
                'include_previous_unpaid' => $validated['include_previous_unpaid'] ?? false,
                'include_inventory_dues' => $validated['include_inventory_dues'] ?? false,
                'include_transport_dues' => $validated['include_transport_dues'] ?? false,
            ], fn ($value) => $value !== null);

            foreach ($monthNumbers as $monthNumber) {
                $result = $voucherService->generateMonthlyVouchers($monthNumber, $validated['year'], $filters);

                $totalGenerated += $result['generated'];
                $totalSkipped += $result['skipped'];

                if (! empty($result['errors'])) {
                    $allErrors = array_merge($allErrors, $result['errors']);
                }
            }
        }

        return redirect()->route('fee.vouchers.index')->with(
            empty($allErrors) ? 'success' : 'warning',
            "Generated {$totalGenerated} vouchers across {$classIds->count()} classes. {$totalSkipped} skipped."
        );
    }

    /**
     * Display the specified voucher.
     */
    public function show(FeeVoucher $voucher)
    {
        Gate::authorize('view', $voucher);

        $voucher->load([
            'student',
            'voucherMonth',
            'campus',
            'schoolClass',
            'section',
            'items.feeHead',
            'paymentAllocations.payment',
        ]);

        [$cohortSummary, $cohortVouchers] = $this->buildCohortAnalytics($voucher);

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
            'cohortSummary' => $cohortSummary,
            'cohortVouchers' => $cohortVouchers,
        ]);
    }

    /**
     * The three-part bank challan a parent takes to the counter.
     *
     * Fee here is collected at a bank branch, not at the school, so the slip
     * has to carry everything the teller needs: the amount, the due date and a
     * reference number they can key in. It prints in three parts — the bank
     * keeps one, returns one to the school with the day's scroll, and the
     * parent keeps the third as proof of payment.
     */
    public function challan(FeeVoucher $voucher)
    {
        $this->authorizePrint($voucher);

        $voucher->load([
            'student',
            'voucherMonth',
            'items.feeHead',
            'campus',
            'schoolClass',
            'section',
        ]);

        $school = School::where('is_active', true)->first();

        $fineRule = FeeFineRule::query()
            ->active()
            ->where('campus_id', $voucher->campus_id)
            ->where('session_id', $voucher->session_id)
            ->first();

        return response()->view('fee.vouchers.challan', [
            'voucher' => $voucher,
            'school' => $school,
            'bankAccount' => $this->bankAccountLine($school),
            'amountInWords' => $this->amountInWords((float) $voucher->balance_amount),
            'lateFineNote' => $fineRule ? $this->lateFineNote($fineRule) : null,
        ])->header('Content-Type', 'text/html');
    }

    /**
     * The account the teller credits, as one line.
     */
    private function bankAccountLine(?School $school): ?string
    {
        if (! $school || ! $school->bank_account_no) {
            return null;
        }

        return collect([
            $school->bank_name,
            $school->bank_branch,
            $school->bank_account_title,
            'A/C '.$school->bank_account_no,
        ])->filter()->implode(' · ');
    }

    /**
     * "One thousand two hundred" — a challan is a financial instrument and the
     * figure has to appear in words as well as digits.
     *
     * `intl` is present here, but a challan that cannot be printed stops fee
     * collection, so a server without the extension falls back to the figure
     * rather than failing.
     */
    private function amountInWords(float $amount): string
    {
        $rupees = (int) round($amount);

        if (! class_exists(\NumberFormatter::class)) {
            return number_format($rupees);
        }

        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);

        return ucfirst((string) $formatter->format($rupees));
    }

    /**
     * What the parent is told they will pay if they miss the due date.
     */
    private function lateFineNote(FeeFineRule $rule): string
    {
        $after = $rule->grace_days > 0
            ? ' after '.$rule->grace_days.' days'
            : '';

        return match ($rule->fine_type) {
            FineType::SLAB => 'After due date: Rs. '.number_format((float) $rule->initial_amount, 0)
                .' + Rs. '.number_format((float) $rule->daily_amount, 0).' per day'.$after,
            FineType::FIXED_PER_DAY => 'After due date: Rs. '
                .number_format((float) $rule->fine_value, 0).' per day'.$after,
            FineType::FIXED_ONCE => 'After due date: Rs. '.number_format((float) $rule->fine_value, 0).$after,
            FineType::PERCENT => 'After due date: '.rtrim(rtrim((string) $rule->fine_value, '0'), '.').'% fine'.$after,
        };
    }

    /**
     * Display printable voucher.
     */
    public function print(FeeVoucher $voucher)
    {
        $this->authorizePrint($voucher);

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

        $vouchers = FeeVoucher::query()
            ->when(auth()->check(), fn ($query) => $query->visibleTo($request->user()))
            ->with([
                'student',
                'voucherMonth',
                'items.feeHead',
                'campus',
                'schoolClass',
                'section',
            ])->whereIn('id', $voucherIds)->get();

        foreach ($vouchers as $voucher) {
            $this->authorizePrint($voucher);
        }

        $school = School::where('is_active', true)->first();

        return response()->view('fee.vouchers.print-batch', [
            'vouchers' => $vouchers,
            'school' => $school,
        ])->header('Content-Type', 'text/html');
    }

    /**
     * The two ways into a printable voucher: an authenticated user who may
     * print it, or an anonymous request the `signed` route middleware has
     * already verified — a link the app itself generated, meant to be shared
     * (a parent opening a challan from WhatsApp has no portal login).
     */
    protected function authorizePrint(FeeVoucher $voucher): void
    {
        if (auth()->check()) {
            Gate::authorize('print', $voucher);

            return;
        }

        abort_unless(request()->hasValidSignature(), 403);
    }

    /**
     * Cancel a voucher.
     */
    public function cancel(FeeVoucher $voucher)
    {
        Gate::authorize('delete', $voucher);

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
     * Permanently removes a voucher that was never billed against.
     */
    public function destroy(FeeVoucher $voucher)
    {
        Gate::authorize('delete', $voucher);

        if ($voucher->status === 'paid' || (float) $voucher->paid_amount > 0) {
            return back()->withErrors(['error' => 'Cannot delete a voucher with payments. Cancel or refund first.']);
        }

        $voucher->delete();

        return redirect()->route('fee.vouchers.index')->with('success', 'Voucher deleted successfully.');
    }

    /**
     * Publishes a voucher — the point it stops being a draft a family cannot
     * yet see.
     */
    public function publish(FeeVoucher $voucher)
    {
        Gate::authorize('update', $voucher);

        if ($voucher->published_at) {
            return back()->withErrors(['error' => 'This voucher is already published.']);
        }

        $voucher->update(['published_at' => now()]);

        return back()->with('success', 'Voucher published successfully.');
    }

    /**
     * A voucher-level adjustment — arrears, an advance, a waiver, a fine
     * reversed — recorded against the voucher as a whole rather than one item.
     */
    public function addAdjustment(AddVoucherAdjustmentRequest $request, FeeVoucher $voucher)
    {
        Gate::authorize('update', $voucher);

        if ($voucher->status === 'paid') {
            return back()->withErrors(['error' => 'Cannot adjust a paid voucher.']);
        }

        $validated = $request->validated();

        $voucher->adjustments()->create([
            'adjustment_type' => $validated['adjustment_type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'related_voucher_id' => $validated['related_voucher_id'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Arrears and a manual charge add to what is owed; an advance, a
        // waiver and a reversed fine take from it.
        $increases = in_array($validated['adjustment_type'], [
            AdjustmentType::ARREARS->value,
            AdjustmentType::MANUAL_CHARGE->value,
        ], true);

        $delta = $increases ? (float) $validated['amount'] : -(float) $validated['amount'];

        $voucher->update([
            'net_amount' => (float) $voucher->net_amount + $delta,
            'balance_amount' => max(0, (float) $voucher->balance_amount + $delta),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Adjustment recorded successfully.']);
        }

        return back()->with('success', 'Adjustment recorded successfully.');
    }

    /**
     * Logs a print — an audit trail of who printed a challan and when, not a
     * gate on printing it.
     */
    public function logPrint(Request $request, FeeVoucher $voucher)
    {
        $this->authorizePrint($voucher);

        $log = FeeVoucherPrintLog::firstOrNew(['fee_voucher_id' => $voucher->id, 'printed_by' => auth()->id()]);
        $log->printed_at = now();
        $log->print_count = ($log->print_count ?? 0) + 1;
        $log->save();

        return response()->json(['message' => 'Print logged.']);
    }

    /**
     * Get vouchers by student.
     */
    /**
     * `{student}` is bound straight off the URL — the route always was
     * `/vouchers/student/{student}`, but this used to read a `student_id`
     * from the query string instead and ignore it, so the endpoint 422'd on
     * every call unless the caller redundantly repeated the id as a query
     * param too.
     */
    public function getByStudent(Request $request, Student $student)
    {
        Gate::authorize('viewByStudent', [FeeVoucher::class, $student->id]);

        $vouchers = FeeVoucher::query()
            ->visibleTo($request->user())
            ->where('student_id', $student->id)
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
        Gate::authorize('viewAny', FeeVoucher::class);

        $query = FeeVoucher::unpaid()->visibleTo($request->user())->with(['student', 'voucherMonth', 'items.feeHead', 'items.studentAccountCharge']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $vouchers = $query->get()->map(function (FeeVoucher $voucher) {
            $items = $voucher->items->map(function (FeeVoucherItem $item) {
                $charge = $item->studentAccountCharge;
                $balance = $this->studentBillingService->getVoucherItemBalance($item);

                return [
                    'id' => $item->id,
                    'student_account_charge_id' => $charge?->id,
                    'fee_head_id' => $item->fee_head_id,
                    'fee_head_name' => $item->feeHead?->name,
                    'description' => $item->description,
                    'source_module' => $item->source_module ?? 'fee',
                    'source_type' => $item->source_type instanceof \BackedEnum ? $item->source_type->value : (string) $item->source_type,
                    'amount' => (float) $item->amount,
                    'net_amount' => (float) $item->net_amount,
                    'balance_amount' => $balance,
                    'status' => $charge?->status ?? 'open',
                ];
            })->filter(fn (array $item) => $item['balance_amount'] > 0)->values()->all();

            return [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'voucher_month' => $voucher->voucherMonth ? [
                    'id' => $voucher->voucherMonth->id,
                    'name' => $voucher->voucherMonth->name,
                ] : null,
                'voucher_year' => $voucher->voucher_year,
                'net_amount' => (float) $voucher->net_amount,
                'balance_amount' => (float) $voucher->balance_amount,
                'items' => $items,
            ];
        })->filter(fn (array $voucher) => ! empty($voucher['items']))->values();

        return response()->json($vouchers);
    }

    /**
     * Get overdue vouchers.
     */
    public function getOverdue(Request $request)
    {
        Gate::authorize('viewAny', FeeVoucher::class);

        // Grouped, not a bare `orWhere`: an ungrouped `OR` here let every
        // campus's overdue vouchers through a `campus_id` filter, because `AND`
        // binds tighter than `OR` in the SQL this built.
        $query = FeeVoucher::query()
            ->visibleTo($request->user())
            ->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($inner) {
                        $inner->where('due_date', '<', now()->toDateString())
                            ->whereIn('status', ['unpaid', 'partial']);
                    });
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
        Gate::authorize('update', $voucher);

        $voucher->load([
            'student',
            'voucherMonth',
            'campus',
            'schoolClass',
            'section',
            'items.feeHead',
            'adjustments',
        ]);

        [$cohortSummary, $cohortVouchers] = $this->buildCohortAnalytics($voucher);

        return Inertia::render('Fee/Vouchers/Edit', [
            'voucher' => [
                'id' => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
                'student_id' => $voucher->student_id,
                'class_id' => $voucher->class_id,
                'section_id' => $voucher->section_id,
                'voucher_month_id' => $voucher->voucher_month_id,
                'voucher_year' => $voucher->voucher_year,
                'issue_date' => $voucher->issue_date?->format('Y-m-d'),
                'due_date' => $voucher->due_date?->format('Y-m-d'),
                'status' => $voucher->status instanceof \BackedEnum ? $voucher->status->value : (string) $voucher->status,
                'gross_amount' => (float) $voucher->gross_amount,
                'discount_amount' => (float) $voucher->discount_amount,
                'fine_amount' => (float) $voucher->fine_amount,
                'paid_amount' => (float) $voucher->paid_amount,
                'net_amount' => (float) $voucher->net_amount,
                'balance_amount' => (float) $voucher->balance_amount,
                'advance_adjusted_amount' => (float) $voucher->advance_adjusted_amount,
                'notes' => $voucher->notes,
                'student' => $voucher->student ? [
                    'id' => $voucher->student->id,
                    'name' => $voucher->student->name,
                    'registration_number' => $voucher->student->registration_number,
                ] : null,
                'voucherMonth' => $voucher->voucherMonth ? [
                    'id' => $voucher->voucherMonth->id,
                    'name' => $voucher->voucherMonth->name,
                ] : null,
                'campus' => $voucher->campus ? [
                    'id' => $voucher->campus->id,
                    'name' => $voucher->campus->name,
                ] : null,
                'schoolClass' => $voucher->schoolClass ? [
                    'id' => $voucher->schoolClass->id,
                    'name' => $voucher->schoolClass->name,
                ] : null,
                'section' => $voucher->section ? [
                    'id' => $voucher->section->id,
                    'name' => $voucher->section->name,
                ] : null,
                'items' => $voucher->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'fee_head_id' => $item->fee_head_id,
                        'fee_head' => $item->feeHead ? [
                            'id' => $item->feeHead->id,
                            'name' => $item->feeHead->name,
                            'code' => $item->feeHead->code,
                        ] : null,
                        'description' => $item->description,
                        'amount' => (float) $item->amount,
                        'discount_amount' => (float) $item->discount_amount,
                        'net_amount' => (float) $item->net_amount,
                    ];
                })->values()->toArray(),
                'adjustments' => $voucher->adjustments->map(function ($adjustment) {
                    return [
                        'id' => $adjustment->id,
                        'type' => $adjustment->adjustment_type instanceof \BackedEnum ? $adjustment->adjustment_type->value : (string) $adjustment->adjustment_type,
                        'amount' => (float) $adjustment->amount,
                        'description' => $adjustment->description,
                        'created_at' => $adjustment->created_at?->toDateTimeString(),
                    ];
                })->values()->toArray(),
            ],
            'feeHeads' => FeeHead::active()->select('id', 'name', 'code', 'category')->ordered()->get(),
            'cohortSummary' => $cohortSummary,
            'cohortVouchers' => $cohortVouchers,
        ]);
    }

    /**
     * Update the specified voucher.
     */
    public function update(UpdateFeeVoucherRequest $request, FeeVoucher $voucher)
    {
        Gate::authorize('update', $voucher);

        $validated = $request->validated();

        $voucher->update($validated);

        return redirect()->route('fee.vouchers.show', $voucher->id)
            ->with('success', 'Voucher updated successfully.');
    }

    /**
     * Add a new fee head item to the voucher.
     */
    public function addItem(AddVoucherItemRequest $request, FeeVoucher $voucher)
    {
        Gate::authorize('update', $voucher);

        // Prevent changes to paid vouchers
        if ($voucher->status === 'paid') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Cannot modify a paid voucher.'], 422)
                : back()->withErrors(['error' => 'Cannot modify a paid voucher.']);
        }

        $validated = $request->validated();

        // Check if this fee head already exists on the voucher
        $existingItem = $voucher->items()->where('fee_head_id', $validated['fee_head_id'])->first();
        if ($existingItem) {
            return $request->expectsJson()
                ? response()->json(['message' => 'This fee head already exists on the voucher.'], 422)
                : back()->withErrors(['error' => 'This fee head already exists on the voucher.']);
        }

        $feeHead = FeeHead::find($validated['fee_head_id']);

        $item = $voucher->items()->create([
            'fee_head_id' => $validated['fee_head_id'],
            'description' => $validated['description'] ?? $feeHead->name,
            'amount' => $validated['amount'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'net_amount' => $validated['amount'] - ($validated['discount_amount'] ?? 0),
            'source_type' => 'manual',
            'source_module' => 'fee',
            'reference_id' => null,
        ]);

        if ($item) {
            $charge = $this->studentBillingService->createVoucherCharge($voucher, $voucher->enrollmentRecord, [
                'source_module' => 'fee',
                'source_type' => 'manual_fee_head',
                'source_id' => $voucher->id,
                'charge_category' => $feeHead->category ?? 'fee',
                'title' => $feeHead->name,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'discount_amount' => (float) $item->discount_amount,
                'net_amount' => (float) $item->net_amount,
                'is_recurring' => false,
                'meta' => [
                    'manual_edit' => true,
                ],
                'created_by' => auth()->id(),
                'approved_by' => auth()->id(),
            ]);

            $item->update(['student_account_charge_id' => $charge->id]);
            $this->studentBillingService->linkChargeToVoucherItem($charge, $item);
        }

        // Recalculate voucher totals
        $this->recalculateVoucherTotals($voucher);
        $this->studentBillingService->syncVoucherChargeStatuses($voucher->fresh('items.studentAccountCharge'));
        $this->accountingService->postVoucherJournal($voucher->fresh('items.studentAccountCharge'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fee item added successfully.']);
        }

        return back()->with('success', 'Fee item added successfully.');
    }

    /**
     * Update an existing voucher item.
     */
    public function updateItem(UpdateVoucherItemRequest $request, FeeVoucher $voucher, FeeVoucherItem $item)
    {
        Gate::authorize('update', $voucher);

        // Prevent changes to paid vouchers
        if ($voucher->status === 'paid') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Cannot modify a paid voucher.'], 422)
                : back()->withErrors(['error' => 'Cannot modify a paid voucher.']);
        }

        // Ensure the item belongs to this voucher
        if ($item->fee_voucher_id !== $voucher->id) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Item does not belong to this voucher.'], 422)
                : back()->withErrors(['error' => 'Item does not belong to this voucher.']);
        }

        $validated = $request->validated();

        $item->update([
            'description' => $validated['description'] ?? $item->description,
            'amount' => $validated['amount'],
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'net_amount' => $validated['amount'] - ($validated['discount_amount'] ?? 0),
        ]);

        if (! $item->studentAccountCharge && $voucher->enrollmentRecord) {
            $charge = $this->studentBillingService->createVoucherCharge($voucher, $voucher->enrollmentRecord, [
                'source_module' => 'fee',
                'source_type' => 'manual_fee_head',
                'source_id' => $voucher->id,
                'charge_category' => $item->feeHead?->category ?? 'fee',
                'title' => $item->feeHead?->name ?? $item->description,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'discount_amount' => (float) $item->discount_amount,
                'net_amount' => (float) $item->net_amount,
                'is_recurring' => false,
                'meta' => [
                    'manual_edit' => true,
                ],
                'created_by' => auth()->id(),
                'approved_by' => auth()->id(),
            ]);
            $item->update(['student_account_charge_id' => $charge->id]);
            $this->studentBillingService->linkChargeToVoucherItem($charge, $item);
        }

        $this->studentBillingService->updateChargeFromVoucherItem($voucher, $item->fresh('feeHead', 'studentAccountCharge'));

        // Recalculate voucher totals
        $this->recalculateVoucherTotals($voucher);
        $this->studentBillingService->syncVoucherChargeStatuses($voucher->fresh('items.studentAccountCharge'));
        $this->accountingService->postVoucherJournal($voucher->fresh('items.studentAccountCharge'));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fee item updated successfully.']);
        }

        return back()->with('success', 'Fee item updated successfully.');
    }

    /**
     * Remove a fee head item from the voucher.
     */
    public function removeItem(FeeVoucher $voucher, FeeVoucherItem $item)
    {
        Gate::authorize('update', $voucher);

        // Prevent changes to paid vouchers
        if ($voucher->status === 'paid') {
            return request()->expectsJson()
                ? response()->json(['message' => 'Cannot modify a paid voucher.'], 422)
                : back()->withErrors(['error' => 'Cannot modify a paid voucher.']);
        }

        // Ensure the item belongs to this voucher
        if ($item->fee_voucher_id !== $voucher->id) {
            return request()->expectsJson()
                ? response()->json(['message' => 'Item does not belong to this voucher.'], 422)
                : back()->withErrors(['error' => 'Item does not belong to this voucher.']);
        }

        if ($item->studentAccountCharge) {
            $item->studentAccountCharge->delete();
        }

        $item->delete();

        // Recalculate voucher totals
        $this->recalculateVoucherTotals($voucher);
        $this->studentBillingService->syncVoucherChargeStatuses($voucher->fresh('items.studentAccountCharge'));
        $this->accountingService->postVoucherJournal($voucher->fresh('items.studentAccountCharge'));

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Fee item removed successfully.']);
        }

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

    /**
     * Build class/section voucher cycle analytics for the same period.
     */
    protected function buildCohortAnalytics(FeeVoucher $voucher): array
    {
        $cohortQuery = FeeVoucher::with(['student'])
            ->where('session_id', $voucher->session_id)
            ->where('campus_id', $voucher->campus_id)
            ->where('class_id', $voucher->class_id)
            ->where('voucher_month_id', $voucher->voucher_month_id)
            ->where('voucher_year', $voucher->voucher_year)
            ->when($voucher->section_id, fn ($query) => $query->where('section_id', $voucher->section_id))
            ->when(! $voucher->section_id, fn ($query) => $query->whereNull('section_id'))
            ->orderBy('student_id');

        $cohortVouchersCollection = $cohortQuery->get();

        $summary = [
            'total' => $cohortVouchersCollection->count(),
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'overdue' => 0,
            'cancelled' => 0,
        ];

        $today = now()->toDateString();

        foreach ($cohortVouchersCollection as $cohortVoucher) {
            $status = $cohortVoucher->status instanceof \BackedEnum
                ? $cohortVoucher->status->value
                : (string) $cohortVoucher->status;

            if (
                in_array($status, ['unpaid', 'partial'], true)
                && $cohortVoucher->due_date
                && $cohortVoucher->due_date->toDateString() < $today
                && (float) $cohortVoucher->balance_amount > 0
            ) {
                $status = 'overdue';
            }

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        }

        $cohortVouchers = $cohortVouchersCollection->map(function (FeeVoucher $cohortVoucher) use ($today) {
            $status = $cohortVoucher->status instanceof \BackedEnum
                ? $cohortVoucher->status->value
                : (string) $cohortVoucher->status;

            if (
                in_array($status, ['unpaid', 'partial'], true)
                && $cohortVoucher->due_date
                && $cohortVoucher->due_date->toDateString() < $today
                && (float) $cohortVoucher->balance_amount > 0
            ) {
                $status = 'overdue';
            }

            return [
                'id' => $cohortVoucher->id,
                'voucher_no' => $cohortVoucher->voucher_no,
                'student_name' => $cohortVoucher->student?->name,
                'registration_number' => $cohortVoucher->student?->registration_number,
                'status' => $status,
                'net_amount' => (float) $cohortVoucher->net_amount,
                'paid_amount' => (float) $cohortVoucher->paid_amount,
                'balance_amount' => (float) $cohortVoucher->balance_amount,
                'due_date' => $cohortVoucher->due_date?->format('Y-m-d'),
            ];
        })->values()->toArray();

        return [$summary, $cohortVouchers];
    }
}
