<?php

namespace App\Http\Controllers\Fee;

use App\Http\Controllers\Controller;
use App\Models\Fee\FeeHead;
use App\Models\Fee\FeeStructure;
use App\Models\Fee\FeeStructureItem;
use Illuminate\Http\Request;

class FeeStructureItemController extends Controller
{
    /**
     * Store a newly created fee structure item.
     */
    public function store(Request $request, FeeStructure $feeStructure)
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

        // The same head twice in one structure bills it twice on every voucher.
        $alreadyCharged = $feeStructure->items()
            ->where('fee_head_id', $validated['fee_head_id'])
            ->exists();

        if ($alreadyCharged) {
            return back()->withErrors([
                'fee_head_id' => 'This fee head is already charged in this structure.',
            ]);
        }

        $validated['fee_structure_id'] = $feeStructure->id;

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
     *
     * The structure is taken even though the item alone identifies the row: the
     * route declares it, and a method that leaves it out has the route's
     * arguments handed to it in the wrong order.
     */
    public function update(Request $request, FeeStructure $feeStructure, FeeStructureItem $item)
    {
        $this->assertBelongsToStructure($feeStructure, $item);

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

        // A field the form did not send keeps the value it already had. Falling
        // back to a fixed default here silently cleared the flags on every edit.
        $validated['is_optional'] = $request->boolean('is_optional', (bool) $item->is_optional);
        $validated['applicable_on_admission'] = $request->boolean(
            'applicable_on_admission',
            (bool) $item->applicable_on_admission
        );

        $item->update($validated);

        return back()->with('success', 'Fee item updated successfully.');
    }

    /**
     * Remove the specified fee structure item.
     */
    public function destroy(FeeStructure $feeStructure, FeeStructureItem $item)
    {
        $this->assertBelongsToStructure($feeStructure, $item);

        $item->delete();

        return back()->with('success', 'Fee item removed successfully.');
    }

    /**
     * An item may only be reached through the structure that owns it.
     */
    private function assertBelongsToStructure(FeeStructure $feeStructure, FeeStructureItem $item): void
    {
        abort_if($item->fee_structure_id !== $feeStructure->id, 404);
    }

    /**
     * Get fee heads for the structure's campus/scope.
     */
    public function getAvailableFeeHeads(Request $request, FeeStructure $feeStructure)
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
