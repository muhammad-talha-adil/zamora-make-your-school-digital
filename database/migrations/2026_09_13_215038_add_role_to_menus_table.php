<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A nullable `role` restricts a menu entry to a single role (e.g. the
     * developer-only Subscription page). Null means "visible to everyone",
     * matching every menu seeded before this column existed.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('role')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
