<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A menu row's visibility can now also be driven by a permission, the
     * same way `role` already works — a staff member granted an extra
     * permission from the Staff Permissions page (outside their role) gets
     * the matching sidebar entry automatically, instead of reaching the page
     * with no link to it.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('permission')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('permission');
        });
    }
};
