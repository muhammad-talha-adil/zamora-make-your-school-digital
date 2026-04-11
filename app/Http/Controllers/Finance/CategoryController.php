<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Ledger\LedgerCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = LedgerCategory::with(['parent', 'children']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('type')->orderBy('name')->get();

        return Inertia::render('Finance/Categories', [
            'categories' => $categories,
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:INCOME,EXPENSE',
            'parent_id' => 'nullable|exists:ledger_categories,id',
            'is_active' => 'boolean',
        ]);

        LedgerCategory::create($validated);

        return back()->with('success', 'Category created successfully!');
    }

    public function update(Request $request, LedgerCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:ledger_categories,id',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroy(LedgerCategory $category)
    {
        // Check if category has ledgers
        if ($category->ledgers()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete category with associated transactions.']);
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully!');
    }
}
