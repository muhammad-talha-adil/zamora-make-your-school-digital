<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Application role.
 *
 * Extends Spatie's model so the package's assignment, checking and caching
 * machinery applies, while keeping the columns this project adds on top:
 * a human label, and the level a role operates at.
 *
 * `name` is the identifier used everywhere (`developer`, `campus_admin`, ...).
 * The old `slug` column duplicated it and has been dropped.
 */
class Role extends SpatieRole
{
    /** Highest scope a role can act within. */
    public const SCOPE_SYSTEM = 'SYSTEM';

    public const SCOPE_SCHOOL = 'SCHOOL';

    public const SCOPE_CAMPUS = 'CAMPUS';

    public const SCOPE_SELF = 'SELF';

    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'scope_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether the role holds any permission belonging to a module.
     *
     * Used to decide if a module should appear at all for this role, before
     * checking the individual abilities inside it.
     */
    public function hasModulePermission(string $module): bool
    {
        return $this->permissions->where('module', $module)->isNotEmpty();
    }

    /**
     * Whether the role holds every permission in a module.
     */
    public function hasAllModulePermissions(string $module): bool
    {
        $held = $this->permissions->where('module', $module)->count();

        return $held > 0 && $held === Permission::where('module', $module)->count();
    }
}
