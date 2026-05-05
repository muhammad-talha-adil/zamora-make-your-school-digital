<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Ledger\Ledger;
use App\Models\Ledger\LedgerCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Ledger::with(['category', 'campus', 'student', 'supplier', 'creator']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ledger_number', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50);

        return Inertia::render('Finance/Transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['campus_id', 'type', 'category_id', 'date_from', 'date_to', 'search']),
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'categories' => LedgerCategory::active()->orderBy('name')->get(),
        ]);
    }

    public function show(Ledger $ledger)
    {
        $ledger->load(['category', 'campus', 'student', 'supplier', 'creator']);

        return Inertia::render('Finance/Transactions/Show', [
            'ledger' => $ledger,
        ]);
    }
}
