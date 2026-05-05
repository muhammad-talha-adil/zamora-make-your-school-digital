<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Ledger\Ledger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Daily Cash Book Report
     */
    public function cashBook(Request $request)
    {
        $date = $request->filled('date') ? $request->date : now()->toDateString();
        $campusId = $request->filled('campus_id') ? $request->campus_id : null;

        $query = Ledger::with(['category', 'student', 'supplier'])
            ->where('transaction_date', $date);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        $transactions = $query->orderBy('id')->get();

        $totalIncome = $transactions->where('transaction_type', 'INCOME')->sum('amount');
        $totalExpense = $transactions->where('transaction_type', 'EXPENSE')->sum('amount');

        return Inertia::render('Finance/Reports/CashBook', [
            'date' => $date,
            'transactions' => $transactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'selectedCampus' => $campusId,
        ]);
    }

    /**
     * Income Statement Report
     */
    public function income(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? $request->to_date : now()->endOfMonth()->toDateString();
        $campusId = $request->filled('campus_id') ? $request->campus_id : null;

        $query = Ledger::income()
            ->whereBetween('transaction_date', [$fromDate, $toDate]);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        // Group by category
        $byCategory = $query->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $totalIncome = $byCategory->sum('total');

        return Inertia::render('Finance/Reports/Income', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'byCategory' => $byCategory,
            'totalIncome' => $totalIncome,
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'selectedCampus' => $campusId,
        ]);
    }

    /**
     * Expense Statement Report
     */
    public function expense(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? $request->to_date : now()->endOfMonth()->toDateString();
        $campusId = $request->filled('campus_id') ? $request->campus_id : null;

        $query = Ledger::expense()
            ->whereBetween('transaction_date', [$fromDate, $toDate]);

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        // Group by category
        $byCategory = $query->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $totalExpense = $byCategory->sum('total');

        return Inertia::render('Finance/Reports/Expense', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'byCategory' => $byCategory,
            'totalExpense' => $totalExpense,
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'selectedCampus' => $campusId,
        ]);
    }
}
