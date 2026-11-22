<?php

namespace App\Http\Requests\Fee;

/**
 * Rules for editing a fee structure.
 *
 * Identical to creating one — the same columns are written — so the rules are
 * inherited rather than restated, which is how they drifted apart before.
 */
class UpdateFeeStructureRequest extends StoreFeeStructureRequest
{
    /**
     * The edit screen sends no `items` (charges change one at a time); a
     * caller that does send them states the whole set, which cannot be empty.
     *
     * @return array<int, string>
     */
    protected function itemsRules(): array
    {
        return ['sometimes', 'array'];
    }
}
