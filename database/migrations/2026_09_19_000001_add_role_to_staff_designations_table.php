<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every real staff member hired through `StaffController::storeStaff()` got a
 * working login and zero Spatie roles — every screen 403s them. A designation
 * now optionally names the Spatie role (by name, not id — Spatie resolves
 * roles by name via `syncRoles()`/`assignRole()`) that hiring somebody into it
 * should grant automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_designations', function (Blueprint $table) {
            if (! Schema::hasColumn('staff_designations', 'role')) {
                $table->string('role')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_designations', function (Blueprint $table) {
            if (Schema::hasColumn('staff_designations', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
