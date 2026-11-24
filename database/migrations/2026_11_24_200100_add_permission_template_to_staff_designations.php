<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A designation is a job title plus a default permission set. The set is
     * copied onto a new staff member's own permissions when they are created;
     * the designation stays a reference, never a live link.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('staff_designations', 'permission_template')) {
            Schema::table('staff_designations', function (Blueprint $table) {
                $table->json('permission_template')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        Schema::table('staff_designations', function (Blueprint $table) {
            $table->dropColumn('permission_template');
        });
    }
};
