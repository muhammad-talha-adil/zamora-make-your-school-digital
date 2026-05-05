<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'label',
        'scope_level',
        'is_active',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $key): bool
    {
        return $this->permissions->where('key', $key)->isNotEmpty();
    }

    /**
     * Check if role has any permission in a module
     */
    public function hasModulePermission(string $module): bool
    {
        return $this->permissions->where('module', $module)->isNotEmpty();
    }

    /**
     * Check if role has all permissions in a module
     */
    public function hasAllModulePermissions(string $module): bool
    {
        $modulePermissions = $this->permissions->where('module', $module);

        return $modulePermissions->count() > 0 &&
               $modulePermissions->count() === Permission::where('module', $module)->count();
    }
}
