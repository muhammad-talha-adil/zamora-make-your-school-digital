<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use App\Models\Fee\FeeHead;
use App\Models\Month;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeeStructureItemController extends Controller
{
    /**
     * Store a newly created fee structure item.
     */
    public function store(Request $request, FeeStructure $structure)
    {
        $validated = $request->validate([
            'fee_head_id' => 'required|exists:fee_heads,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'nullable|in:monthly,yearly,once',
            'is_optional' => 'nullable|boolean',
            'applicable_on_admission' => 'nullable|boolean',
            'starts_from_month_id' => 'nullable|exists:months,id',
            'ends_at_month_id' => 'nullable|exists:months,id',
            'notes' => 'nullable|string',
        ]);

        $validated['fee_structure_id'] = $structure->id;
        
        // Auto-set frequency from FeeHead if not provided
        if (empty($validated['frequency'])) {
            $feeHead = FeeHead::find($validated['fee_head_id']);
            $validated['frequency'] = $feeHead?->default_frequency ?? 'monthly';
        }
        
        $validated['is_optional'] = $validated['is_optional'] ?? false;
        $validated['applicable_on_admission'] = $validated['applicable_on_admission'] ?? true;

        FeeStructureItem::create($validated);

        return back()->with('success', 'Fee item added successfully.');
    }

    /**
     * Update the specified fee structure item.
     */
    public function update(Request $request, FeeStructureItem $item)
    {
        $validated = $request->validate([
            'fee_head_id' => 'required|exists:fee_heads,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'nullable|in:monthly,yearly,once',
            'is_optional' => 'nullable|boolean',
            'applicable_on_admission' => 'nullable|boolean',
            'starts_from_month_id' => 'nullable|exists:months,id',
            'ends_at_month_id' => 'nullable|exists:months,id',
            'notes' => 'nullable|string',
        ]);

        // Auto-set frequency from FeeHead if not provided
        if (empty($validated['frequency'])) {
            $feeHead = FeeHead::find($validated['fee_head_id']);
            $validated['frequency'] = $feeHead?->default_frequency ?? 'monthly';
        }
        
        $validated['is_optional'] = $validated['is_optional'] ?? false;
        $validated['applicable_on_admission'] = $validated['applicable_on_admission'] ?? true;

        $item->update($validated);

        return back()->with('success', 'Fee item updated successfully.');
    }

    /**
     * Remove the specified fee structure item.
     */
    public function destroy(FeeStructureItem $item)
    {
        $item->delete();

        return back()->with('success', 'Fee item removed successfully.');
    }

    /**
     * Get fee heads for the structure's campus/scope.
     */
    public function getAvailableFeeHeads(Request $request, FeeStructure $structure)
    {
        // Get all active fee heads
        $feeHeads = FeeHead::active()
            ->ordered()
            ->get()
            ->map(function ($head) {
                return [
                    'id' => $head->id,
                    'name' => $head->name,
                    'code' => $head->code,
                    'category' => $head->category->value,
                    'is_recurring' => $head->is_recurring,
                    'default_frequency' => $head->default_frequency?->value,
                ];
            });

        return response()->json($feeHeads);
    }
}
