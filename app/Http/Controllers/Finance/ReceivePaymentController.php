<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceService;
use App\Models\Fee\FeeVoucher;
use App\Models\Fee\FeeVoucherItem;
use App\Models\Campus;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollmentRecord;
use App\Models\Ledger\LedgerCategory;
use App\Models\Ledger\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceivePaymentController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request)
    {
        // Get all campuses
        $campuses = Campus::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get all classes
        $classes = SchoolClass::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get all sections
        $sections = Section::select('id', 'name', 'class_id')
            ->orderBy('name')
            ->get();

        $categories = LedgerCategory::income()->active()->orderBy('name')->get();
        $paymentMethods = PaymentMethod::active()->orderBy('name')->get();

        return Inertia::render('Finance/ReceivePayment', [
            'campuses' => $campuses,
            'classes' => $classes,
            'sections' => $sections,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Get classes by campus (API).
     */
    public function getClasses(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
        ]);

        // Get classes that have active enrollments in the selected campus
        $classes = SchoolClass::select('school_classes.id', 'school_classes.name')
            ->join('student_enrollment_records', function ($join) {
                $join->on('student_enrollment_records.class_id', '=', 'school_classes.id')
                    ->whereNull('student_enrollment_records.leave_date');
            })
            ->where('student_enrollment_records.campus_id', $request->campus_id)
            ->distinct()
            ->orderBy('school_classes.name')
            ->get();

        return response()->json($classes);
    }

    /**
     * Get sections by class (API).
     */
    public function getSections(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        // Get sections that have active enrollments for the selected class and campus
        $query = Section::select('sections.id', 'sections.name', 'sections.class_id')
            ->join('student_enrollment_records', function ($join) {
                $join->on('student_enrollment_records.section_id', '=', 'sections.id')
                    ->whereNull('student_enrollment_records.leave_date');
            })
            ->where('sections.class_id', $request->class_id);

        if ($request->filled('campus_id')) {
            $query->where('student_enrollment_records.campus_id', $request->campus_id);
        }

        $sections = $query->distinct()
            ->orderBy('sections.name')
            ->get();

        return response()->json($sections);
    }

    /**
     * Get students by filters (API).
     */
    public function getStudents(Request $request)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'class_id' => 'required|exists:school_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'q' => 'nullable|string',
        ]);

        // Get students via enrollment records for active enrollments
        $enrollmentQuery = StudentEnrollmentRecord::select('student_id')
            ->where('class_id', $request->class_id)
            ->where('campus_id', $request->campus_id)
            ->whereNull('leave_date');

        if ($request->filled('section_id')) {
            $enrollmentQuery->where('section_id', $request->section_id);
        }

        $studentIds = $enrollmentQuery->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return response()->json([]);
        }

        // Build student query
        $studentQuery = Student::select('id', 'registration_no', 'student_code')
            ->whereIn('id', $studentIds)
            ->with(['user:id,name']);

        // Filter by search query if provided
        if ($request->filled('q') && strlen($request->q) > 0) {
            $searchTerm = $request->q;
            $studentQuery->where(function ($query) use ($searchTerm) {
                $query->where('registration_no', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $students = $studentQuery->orderBy('registration_no')
            ->limit(20)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'registration_number' => $student->registration_number,
                    'display_text' => $student->name . ' (' . $student->registration_number . ')',
                ];
            });

        return response()->json($students);
    }

    /**
     * Get student details with vouchers and previous balances (API).
     */
    public function getStudentDetails(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $studentId = $request->student_id;
        
        // Get student with user relationship
        $student = Student::with(['user:id,name', 'currentEnrollment'])
            ->find($studentId);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Format student data for response
        $studentData = [
            'id' => $student->id,
            'name' => $student->name,
            'registration_number' => $student->registration_number,
            'currentEnrollment' => $student->currentEnrollment,
        ];

        // Get unpaid/partial vouchers with items breakdown
        $vouchers = FeeVoucher::where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->with(['voucherMonth', 'items.feeHead'])
            ->orderBy('voucher_year', 'desc')
            ->orderBy('voucher_month_id', 'desc')
            ->get()
            ->map(function ($voucher) {
                // Calculate balance by fee head
                $balanceByHead = [];
                foreach ($voucher->items as $item) {
                    $headName = $item->feeHead?->name ?? 'Other';
                    $itemBalance = $item->net_amount; // Each item's net amount is the balance
                    $balanceByHead[$headName] = ($balanceByHead[$headName] ?? 0) + $itemBalance;
                }

                return [
                    'id' => $voucher->id,
                    'voucher_no' => $voucher->voucher_no,
                    'voucher_month' => $voucher->voucherMonth?->name,
                    'voucher_year' => $voucher->voucher_year,
                    'net_amount' => (float) $voucher->net_amount,
                    'paid_amount' => (float) $voucher->paid_amount,
                    'balance_amount' => (float) $voucher->balance_amount,
                    'status' => $voucher->status,
                    'balance_by_head' => $balanceByHead,
                ];
            });

        // Calculate previous balances by fee head across all unpaid vouchers
        $previousBalances = [];
        foreach ($vouchers as $voucher) {
            foreach ($voucher->balance_by_head as $headName => $amount) {
                $previousBalances[$headName] = ($previousBalances[$headName] ?? 0) + $amount;
            }
        }

        return response()->json([
            'student' => $studentData,
            'vouchers' => $vouchers,
            'previousBalances' => $previousBalances,
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        // Validation differs based on payment type
        if ($request->payment_type === 'other') {
            $validated = $request->validate([
                'payment_type' => 'required|in:student,other',
                'campus_id' => 'required|exists:campuses,id',
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required',
                'category_id' => 'required|exists:ledger_categories,id',
                'transaction_date' => 'required|date',
                'description' => 'nullable|string',
                'payer_name' => 'nullable|string|max:255',
            ]);

            // For "Other" payments, create income transaction directly without voucher
            $this->financeService->createIncomeTransaction([
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'category_id' => $validated['category_id'],
                'student_id' => null,
                'reference_type' => 'App\\Models\\Ledger\\ManualPayment',
                'reference_id' => null,
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? 'Manual payment received: ' . ($validated['payer_name'] ?? 'Other'),
                'campus_id' => $validated['campus_id'],
            ]);

            return redirect()->route('finance.transactions.index')
                ->with('success', 'Payment received successfully!');
        }

        // Student payment (existing logic)
        $validated = $request->validate([
            'payment_type' => 'required|in:student,other',
            'student_id' => 'required|exists:students,id',
            'voucher_id' => 'required|exists:fee_vouchers,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $voucher = FeeVoucher::findOrFail($validated['voucher_id']);
        
        // Update voucher (existing business logic)
        $voucher->paid_amount += $validated['amount'];
        $voucher->balance_amount = max(0, $voucher->net_amount - $voucher->paid_amount);
        $voucher->status = $voucher->balance_amount <= 0 ? 'paid' : 'partial';
        $voucher->save();

        // Get default category for student payments (Tuition Fee)
        $defaultCategory = LedgerCategory::where('code', 'TUITION_FEE')->first();

        // Create ledger entry (NEW unified finance)
        $this->financeService->createIncomeTransaction([
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'category_id' => $defaultCategory?->id ?? 1,
            'student_id' => $validated['student_id'],
            'reference_type' => 'App\\Models\\Fee\\FeeVoucher',
            'reference_id' => $validated['voucher_id'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'] ?? 'Fee payment for ' . $voucher->voucher_no,
            'campus_id' => $voucher->campus_id,
        ]);

        return redirect()->route('finance.transactions.index')
            ->with('success', 'Payment received successfully!');
    }
}
