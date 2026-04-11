<?php

namespace App\Services;

use App\Models\Ledger\Ledger;
use App\Models\Ledger\LedgerCategory;
use App\Models\Ledger\PaymentMethod;

class FinanceService
{
    /**
     * Create an income transaction (money received)
     */
    public function createIncomeTransaction(array $data): Ledger
    {
        $ledgerNumber = $this->generateLedgerNumber('INCOME');
        
        return Ledger::create([
            'ledger_number' => $ledgerNumber,
            'transaction_type' => 'INCOME',
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'campus_id' => $data['campus_id'],
            'student_id' => $data['student_id'] ?? null,
            'supplier_id' => null, // Always null for income
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Create an expense transaction (money paid)
     */
    public function createExpenseTransaction(array $data): Ledger
    {
        $ledgerNumber = $this->generateLedgerNumber('EXPENSE');
        
        return Ledger::create([
            'ledger_number' => $ledgerNumber,
            'transaction_type' => 'EXPENSE',
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'campus_id' => $data['campus_id'],
            'student_id' => null, // Always null for expense
            'supplier_id' => $data['supplier_id'] ?? null,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Generate unique ledger number
     */
    protected function generateLedgerNumber(string $type): string
    {
        $prefix = $type === 'INCOME' ? 'LI' : 'LE';
        $year = date('Y');
        
        $lastLedger = Ledger::whereYear('created_at', $year)
            ->where('ledger_number', 'like', $prefix . '-' . $year . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastLedger && preg_match('/' . $prefix . '-' . $year . '-(\d+)/', $lastLedger->ledger_number, $matches)) {
            $counter = intval($matches[1]) + 1;
        } else {
            $counter = 1;
        }
        
        return $prefix . '-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get total income for a period
     */
    public function getTotalIncome($fromDate, $toDate, $campusId = null): float
    {
        $query = Ledger::income()
            ->whereBetween('transaction_date', [$fromDate, $toDate]);
        
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        
        return (float) $query->sum('amount');
    }

    /**
     * Get total expense for a period
     */
    public function getTotalExpense($fromDate, $toDate, $campusId = null): float
    {
        $query = Ledger::expense()
            ->whereBetween('transaction_date', [$fromDate, $toDate]);
        
        if ($campusId) {
            $query->where('campus_id', $campusId);
        }
        
        return (float) $query->sum('amount');
    }

    /**
     * Get balance (income - expense)
     */
    public function getBalance($fromDate, $toDate, $campusId = null): float
    {
        $income = $this->getTotalIncome($fromDate, $toDate, $campusId);
        $expense = $this->getTotalExpense($fromDate, $toDate, $campusId);
        
        return $income - $expense;
    }

    /**
     * Get today's summary
     */
    public function getTodaySummary($campusId = null): array
    {
        $today = now()->toDateString();
        
        return [
            'income' => $this->getTotalIncome($today, $today, $campusId),
            'expense' => $this->getTotalExpense($today, $today, $campusId),
            'balance' => $this->getBalance($today, $today, $campusId),
        ];
    }

    /**
     * Get current month summary
     */
    public function getMonthSummary($campusId = null): array
    {
        $fromDate = now()->startOfMonth()->toDateString();
        $toDate = now()->endOfMonth()->toDateString();
        
        return [
            'income' => $this->getTotalIncome($fromDate, $toDate, $campusId),
            'expense' => $this->getTotalExpense($fromDate, $toDate, $campusId),
            'balance' => $this->getBalance($fromDate, $toDate, $campusId),
        ];
    }

    /**
     * Get category by type (for dropdowns)
     */
    public function getCategoriesByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return LedgerCategory::where('type', $type)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all active payment methods
     */
    public function getPaymentMethods(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentMethod::active()->orderBy('name')->get();
    }

    /**
     * Get income categories
     */
    public function getIncomeCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getCategoriesByType('INCOME');
    }

    /**
     * Get expense categories
     */
    public function getExpenseCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getCategoriesByType('EXPENSE');
    }
}
