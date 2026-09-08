<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * A single ability, e.g. `fee.voucher.generate`.
 *
 * Extends Spatie's model, keeping the `module` / `label` / `description`
 * columns this project uses to group and present permissions.
 *
 * `name` is the identifier checked in code. The old `key` column duplicated
 * it and has been dropped.
 */
class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'module',
        'label',
        'description',
    ];
}
