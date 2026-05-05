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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:inventory_purchases,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
            'category_id' => 'required|exists:ledger_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $purchase = Purchase::findOrFail($validated['purchase_id']);

        // Update purchase (existing business logic)
        $purchase->paid_amount += $validated['amount'];
        $purchase->payment_status = $purchase->paid_amount >= $purchase->total_amount
            ? 'paid'
            : 'partial';
        $purchase->save();

        // Create ledger entry (NEW unified finance)
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
}
