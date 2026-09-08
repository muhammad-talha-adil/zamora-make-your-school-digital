<?php

namespace App\Http\Requests\Fee;

/**
 * Rules for editing a fee structure.
 *
 * Identical to creating one — the same columns are written — so the rules are
 * inherited rather than restated, which is how they drifted apart before.
 */
class UpdateFeeStructureRequest extends StoreFeeStructureRequest {}
