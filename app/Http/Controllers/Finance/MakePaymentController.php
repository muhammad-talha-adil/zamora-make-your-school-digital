<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Ledger\LedgerCategory;
use App\Models\Ledger\PaymentMethod;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MakePaymentController extends Controller
{
    protected $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    public function create(Request $request)
    {
        $purchases = [];
        $suppliers = [];

        if ($request->filled('search')) {
            // Search purchases
            $purchases = Purchase::with(['supplier', 'campus'])
                ->where('payment_status', '!=', 'paid')
                ->where('purchase_id', 'like', '%'.$request->search.'%')
                ->limit(10)
                ->get();

            // Or search suppliers
            $suppliers = Supplier::where('name', 'like', '%'.$request->search.'%')
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone']);
        }

        return Inertia::render('Finance/MakePayment', [
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'categories' => LedgerCategory::expense()->active()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * `purchase_id` used to be required unconditionally, so eight of the ten
     * seeded expense categories (Salary, Rent, Electricity, Internet,
     * Transport, Maintenance, Other — everything except the three
     * supplier-payment ones) had no purchase to attach to and could never
     * actually be used through this screen, despite being offered in the
     * category dropdown. A purchase payment and a general expense are now two
     * branches, the same shape `ReceivePaymentController` already uses for
     * "student" vs "other".
     */
    public function store(Request $request)
    {
        if ($request->filled('purchase_id')) {
            $validated = $request->validate([
                'purchase_id' => 'required|exists:inventory_purchases,id',
                'amount' => 'required|numeric|min:1',
                'payment_method' => 'required',
                'category_id' => 'required|exists:ledger_categories,id',
                'transaction_date' => 'required|date',
                'description' => 'nullable|string',
            ]);

            $purchase = Purchase::findOrFail($validated['purchase_id']);

            $purchase->paid_amount += $validated['amount'];
            $purchase->payment_status = $purchase->paid_amount >= $purchase->total_amount
                ? 'paid'
                : 'partial';
            $purchase->save();

            $this->financeService->createExpenseTransaction([
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'category_id' => $validated['category_id'],
                'supplier_id' => $purchase->supplier_id,
                'reference_type' => 'App\\Models\\Purchase',
                'reference_id' => $validated['purchase_id'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? 'Payment for '.$purchase->purchase_id,
                'campus_id' => $purchase->campus_id,
            ]);

            return redirect()->route('finance.transactions.index')
                ->with('success', 'Payment made successfully!');
        }

        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
            'category_id' => 'required|exists:ledger_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'payee_name' => 'nullable|string|max:255',
        ]);

        $this->financeService->createExpenseTransaction([
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'category_id' => $validated['category_id'],
            'supplier_id' => null,
            'reference_type' => 'App\\Models\\Ledger\\ManualPayment',
            'reference_id' => null,
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'] ?? 'Manual expense paid: '.($validated['payee_name'] ?? 'Other'),
            'campus_id' => $validated['campus_id'],
        ]);

        return redirect()->route('finance.transactions.index')
            ->with('success', 'Payment made successfully!');
    }
}
