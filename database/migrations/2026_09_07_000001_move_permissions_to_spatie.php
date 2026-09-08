<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Completes the move to spatie/laravel-permission.
 *
 * Two starting points have to be handled:
 *
 *  - A fresh install, where the earlier migrations create `roles` and
 *    `permissions` with this project's own columns (`slug`, `key`) and none of
 *    Spatie's pivot tables exist.
 *  - The live database, which already carried Spatie's tables and columns
 *    fully populated from an earlier setup, alongside a half-finished custom
 *    layer whose own pivots were never filled. Application code read those
 *    empty custom tables, so every role and permission check returned false.
 *
 * Each step below is therefore guarded, and the migration ends with the same
 * schema either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addSpatieColumns();
        $this->createSpatieTables();
        $this->copyCustomPivots();

        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');

        // `slug` and `key` duplicated `name`, which is the identifier Spatie
        // checks against. SQLite refuses to drop a column an index still
        // references, so the index goes first.
        $this->dropIndexIfExists('roles', 'slug');
        $this->dropIndexIfExists('permissions', 'key');

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'slug')) {
                $table->dropColumn('slug');
            }
            // Spatie's models do not filter soft deletes, so a "deleted" role
            // would still grant access. Drop the column rather than leave that
            // trap in place.
            if (Schema::hasColumn('roles', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'key')) {
                $table->dropColumn('key');
            }
            if (Schema::hasColumn('permissions', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });

        // `module` and `label` are informational on a permission; a permission
        // created by Spatie itself supplies neither.
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module')->nullable()->change();
            $table->string('label')->nullable()->change();
        });

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable();
            $table->softDeletes();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('key')->nullable();
            $table->softDeletes();
        });

        DB::statement('UPDATE roles SET slug = name');
        DB::statement('UPDATE permissions SET "key" = name');

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (DB::table('role_has_permissions')->get() as $row) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $row->role_id,
                'permission_id' => $row->permission_id,
            ]);
        }

        foreach (DB::table('model_has_roles')->where('model_type', User::class)->get() as $row) {
            DB::table('user_roles')->insertOrIgnore([
                'user_id' => $row->model_id,
                'role_id' => $row->role_id,
                'is_active' => true,
            ]);
        }

        $this->forgetPermissionCache();
    }

    /**
     * Drops a column's unique index, tolerating its absence.
     *
     * The index is named by Laravel's convention on both engines, but only
     * exists when the column was created with one.
     */
    private function dropIndexIfExists(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropUnique([$column]);
            });
        } catch (Throwable) {
            // No unique index on this column; nothing to remove.
        }
    }

    /**
     * Adds the `name` / `guard_name` pair Spatie identifies records by.
     */
    private function addSpatieColumns(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('permissions', 'guard_name')) {
                $table->string('guard_name')->default('web')->after('name');
            }
        });

        // On a fresh install `name` starts empty and `key` holds the identifier.
        if (Schema::hasColumn('permissions', 'key')) {
            DB::statement('UPDATE permissions SET name = "key" WHERE name IS NULL OR name = \'\'');
        }

        DB::table('roles')->whereNull('guard_name')->update(['guard_name' => 'web']);
        DB::table('permissions')->whereNull('guard_name')->update(['guard_name' => 'web']);
    }

    /**
     * Creates Spatie's pivot tables when they are not already present.
     */
    private function createSpatieTables(): void
    {
        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->primary(
                    ['permission_id', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->primary(
                    ['role_id', 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
            });
        }
    }

    /**
     * Moves anything the custom pivots hold into the Spatie tables.
     */
    private function copyCustomPivots(): void
    {
        if (Schema::hasTable('role_permissions')) {
            foreach (DB::table('role_permissions')->get() as $row) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $row->role_id,
                    'permission_id' => $row->permission_id,
                ]);
            }
        }

        if (Schema::hasTable('user_roles')) {
            foreach (DB::table('user_roles')->whereNull('deleted_at')->where('is_active', true)->get() as $row) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $row->role_id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $row->user_id,
                ]);
            }
        }
    }

    private function forgetPermissionCache(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
