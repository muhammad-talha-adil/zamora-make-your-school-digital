<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Finance\JournalEntry;
use App\Models\Ledger\Ledger;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['campus_id', 'system', 'module', 'kind', 'date_from', 'date_to', 'search']);

        $transactions = collect()
            ->concat($this->getLedgerTransactions($request))
            ->concat($this->getJournalTransactions($request))
            ->sortByDesc(fn (array $transaction) => sprintf(
                '%s-%010d',
                $transaction['transaction_date'],
                $transaction['sort_id']
            ))
            ->values();

        $paginatedTransactions = $this->paginateCollection($transactions, (int) $request->integer('page', 1), 25, $request);

        return Inertia::render('Finance/Transactions', [
            'transactions' => $paginatedTransactions,
            'filters' => $filters,
            'campuses' => Campus::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Ledger $ledger)
    {
        $ledger->load(['category', 'campus', 'student', 'supplier', 'creator']);

        return Inertia::render('Finance/Transactions/Show', [
            'ledger' => $ledger,
        ]);
    }

    protected function getLedgerTransactions(Request $request): Collection
    {
        if ($request->input('system') === 'journal' || ($request->filled('module') && $request->module !== 'finance')) {
            return collect();
        }

        $query = Ledger::with(['category', 'campus', 'student.user', 'supplier', 'creator']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('kind')) {
            $kind = strtolower((string) $request->kind);

            if ($kind === 'income') {
                $query->where('transaction_type', 'INCOME');
            } elseif ($kind === 'expense') {
                $query->where('transaction_type', 'EXPENSE');
            } elseif (in_array($kind, ['receivable', 'collection', 'adjustment'], true)) {
                return collect();
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($builder) use ($search) {
                $builder->where('ledger_number', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('reference_number', 'like', '%'.$search.'%')
                    ->orWhereHas('student.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Ledger $ledger) {
                $kind = strtoupper((string) $ledger->transaction_type) === 'INCOME' ? 'income' : 'expense';

                return [
                    'id' => 'ledger-'.$ledger->id,
                    'sort_id' => $ledger->id,
                    'system' => 'ledger',
                    'module' => 'finance',
                    'kind' => $kind,
                    'reference_no' => $ledger->ledger_number,
                    'transaction_date' => optional($ledger->transaction_date)->format('Y-m-d') ?? now()->toDateString(),
                    'description' => $ledger->description ?: 'Finance transaction',
                    'campus_name' => $ledger->campus?->name,
                    'student_name' => $ledger->student?->name,
                    'counterparty_name' => $ledger->supplier?->name,
                    'category_name' => $ledger->category?->name,
                    'status' => 'posted',
                    'amount' => (float) $ledger->amount,
                    'detail_url' => route('finance.transactions.show', $ledger),
                ];
            });
    }

    protected function getJournalTransactions(Request $request): Collection
    {
        if ($request->input('system') === 'ledger') {
            return collect();
        }

        $query = JournalEntry::with(['campus', 'student.user', 'lines.account', 'creator']);

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('module')) {
            $query->where('source_module', $request->module);
        }

        if ($request->filled('kind')) {
            $sourceTypes = match (strtolower((string) $request->kind)) {
                'receivable' => ['fee_voucher', 'student_inventory_assignment', 'transport_charge'],
                'collection' => ['fee_payment'],
                'adjustment' => ['student_inventory_return'],
                'expense' => ['payroll_run', 'payroll_payment', 'vehicle_expense'],
                'income' => [],
                default => null,
            };

            if ($sourceTypes === []) {
                return collect();
            }

            if (is_array($sourceTypes)) {
                $query->whereIn('source_type', $sourceTypes);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($builder) use ($search) {
                $builder->where('entry_no', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('source_module', 'like', '%'.$search.'%')
                    ->orWhere('source_type', 'like', '%'.$search.'%')
                    ->orWhereHas('student.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (JournalEntry $entry) {
                $kind = match ($entry->source_type) {
                    'fee_payment' => 'collection',
                    'student_inventory_return' => 'adjustment',
                    'payroll_run', 'payroll_payment', 'vehicle_expense' => 'expense',
                    default => 'receivable',
                };

                return [
                    'id' => 'journal-'.$entry->id,
                    'sort_id' => $entry->id,
                    'system' => 'journal',
                    'module' => $entry->source_module ?: 'finance',
                    'kind' => $kind,
                    'reference_no' => $entry->entry_no,
                    'transaction_date' => optional($entry->entry_date)->format('Y-m-d') ?? now()->toDateString(),
                    'description' => $entry->description ?: 'Journal entry',
                    'campus_name' => $entry->campus?->name,
                    'student_name' => $entry->student?->name,
                    'counterparty_name' => null,
                    'category_name' => $this->formatJournalSourceType($entry->source_type),
                    'status' => $entry->status,
                    'amount' => $this->resolveJournalAmount($entry),
                    'detail_url' => null,
                ];
            });
    }

    protected function resolveJournalAmount(JournalEntry $entry): float
    {
        $meta = $entry->meta ?? [];

        foreach (['received_amount', 'receivable_amount', 'credit_amount'] as $key) {
            if (isset($meta[$key])) {
                return (float) $meta[$key];
            }
        }

        return (float) $entry->lines->sum(fn ($line) => max((float) $line->debit, (float) $line->credit));
    }

    protected function formatJournalSourceType(?string $sourceType): string
    {
        return str_replace('_', ' ', ucfirst((string) $sourceType));
    }

    protected function paginateCollection(Collection $items, int $page, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        return new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
